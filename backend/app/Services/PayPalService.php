<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\TutorProfile;
use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class PayPalService
{
    public function isConfigured(): bool
    {
        return filled($this->clientId())
            && filled($this->clientSecret());
    }

    public function missingConfiguration(): array
    {
        $missing = [];

        if (! filled($this->clientId())) {
            $missing[] = 'PAYPAL_CLIENT_ID';
        }

        if (! filled($this->clientSecret())) {
            $missing[] = 'PAYPAL_CLIENT_SECRET';
        }


        return $missing;
    }

    public function createSellerReferral(User $user, TutorProfile $tutorProfile): array
    {
        $this->ensureConfigured();

        $trackingId = $tutorProfile->paypal_tracking_id
            ?: 'tutorconnect-tutor-'.$tutorProfile->id.'-'.Str::lower(Str::random(12));

        $body = [
            'tracking_id' => $trackingId,
            'operations' => [
                [
                    'operation' => 'API_INTEGRATION',
                    'api_integration_preference' => [
                        'rest_api_integration' => [
                            'integration_method' => 'PAYPAL',
                            'integration_type' => 'THIRD_PARTY',
                            'third_party_details' => [
                                'features' => ['PAYMENT', 'REFUND'],
                            ],
                        ],
                    ],
                ],
            ],
            'products' => ['EXPRESS_CHECKOUT'],
            'legal_consents' => [
                [
                    'type' => 'SHARE_DATA_CONSENT',
                    'granted' => true,
                ],
            ],
            'partner_config_override' => [
                'return_url' => $this->frontendUrl('/profile?paypal_onboarding=return'),
                'return_url_description' => 'Zurück zu TutorConnect',
            ],
        ];

        $response = $this->request('POST', '/v2/customer/partner-referrals', $body);

        return [
            'tracking_id' => $trackingId,
            'referral_id' => $this->extractReferralId($response),
            'action_url' => $this->link($response, 'action_url'),
            'raw' => $response,
        ];
    }

    public function sellerStatus(string $sellerMerchantId): array
    {
        $this->ensureConfigured();

        return $this->request(
            'GET',
            '/v1/customer/partners/'.urlencode($this->partnerMerchantId()).'/merchant-integrations/'.urlencode($sellerMerchantId)
        );
    }

    public function createOrder(Booking $booking): array
    {
        $this->ensureConfigured();

        $amount = number_format((float) $booking->payment_amount, 2, '.', '');
        $currency = strtoupper((string) ($booking->payment_currency ?: 'EUR'));
        $subject = $booking->subject?->name ?: 'Nachhilfe';

        $body = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => 'booking-'.$booking->id,
                    'custom_id' => 'booking-'.$booking->id,
                    'description' => 'TutorConnect · '.$subject,
                    'amount' => [
                        'currency_code' => $currency,
                        'value' => $amount,
                    ],
                ],
            ],
            'payment_source' => [
                'paypal' => [
                    'experience_context' => [
                        'brand_name' => 'TutorConnect',
                        'locale' => 'de-DE',
                        'shipping_preference' => 'NO_SHIPPING',
                        'user_action' => 'PAY_NOW',
                        'return_url' => $this->frontendUrl('/dashboard?paypal=return&booking='.$booking->id),
                        'cancel_url' => $this->frontendUrl('/dashboard?paypal=cancelled&booking='.$booking->id),
                    ],
                ],
            ],
        ];

        return $this->request(
            'POST',
            '/v2/checkout/orders',
            $body,
            null,
            'booking-'.$booking->id.'-'.Str::uuid()
        );
    }

    public function captureOrder(Booking $booking): array
    {
        $this->ensureConfigured();

        $orderId = (string) $booking->paypal_order_id;
        if ($orderId === '') {
            throw new RuntimeException('PayPal-Bestellung fehlt.');
        }

        try {
            return $this->request(
                'POST',
                '/v2/checkout/orders/'.urlencode($orderId).'/capture',
                [],
                null,
                'capture-booking-'.$booking->id.'-'.$orderId,
                true
            );
        } catch (RuntimeException $exception) {
            // Falls PayPal bereits erfasst hat, aber die Browser-Antwort verloren ging,
            // holen wir den Order-Status erneut. So ist der Rücksprung idempotent.
            $order = $this->showOrder($booking);

            if (($order['status'] ?? null) === 'COMPLETED' && $this->captureInfo($order)) {
                return $order;
            }

            throw $exception;
        }
    }

    public function showOrder(Booking $booking): array
    {
        $this->ensureConfigured();

        $orderId = (string) $booking->paypal_order_id;

        if ($orderId === '') {
            throw new RuntimeException('PayPal-Bestellung fehlt.');
        }

        return $this->request(
            'GET',
            '/v2/checkout/orders/'.urlencode($orderId)
        );
    }

    public function refundCapture(Booking $booking): array
    {
        $this->ensureConfigured();

        $captureId = (string) $booking->paypal_capture_id;

        if ($captureId === '') {
            throw new RuntimeException('PayPal-Zahlung kann nicht zurückerstattet werden, weil die Transaktionsdaten fehlen.');
        }

        return $this->request(
            'POST',
            '/v2/payments/captures/'.urlencode($captureId).'/refund',
            [],
            null,
            'refund-booking-'.$booking->id,
            true
        );
    }

    public function verifyWebhook(array $headers, array $event): bool
    {
        $webhookId = (string) config('services.paypal.webhook_id');

        if ($webhookId === '') {
            return false;
        }

        $body = [
            'auth_algo' => $headers['paypal-auth-algo'] ?? '',
            'cert_url' => $headers['paypal-cert-url'] ?? '',
            'transmission_id' => $headers['paypal-transmission-id'] ?? '',
            'transmission_sig' => $headers['paypal-transmission-sig'] ?? '',
            'transmission_time' => $headers['paypal-transmission-time'] ?? '',
            'webhook_id' => $webhookId,
            'webhook_event' => $event,
        ];

        $response = $this->request('POST', '/v1/notifications/verify-webhook-signature', $body);

        return strtoupper((string) ($response['verification_status'] ?? '')) === 'SUCCESS';
    }

    public function approvalUrl(array $order): ?string
    {
        return $this->link($order, 'payer-action')
            ?: $this->link($order, 'approve');
    }

    public function captureInfo(array $order): ?array
    {
        $capture = data_get($order, 'purchase_units.0.payments.captures.0');

        return is_array($capture) ? $capture : null;
    }

    private function request(
        string $method,
        string $path,
        array $body = [],
        ?string $sellerMerchantId = null,
        ?string $requestId = null,
        bool $forceEmptyObject = false
    ): array {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if (filled(config('services.paypal.bn_code'))) {
            $headers['PayPal-Partner-Attribution-Id'] = (string) config('services.paypal.bn_code');
        }

        if ($sellerMerchantId) {
            $headers['PayPal-Auth-Assertion'] = $this->authAssertion($sellerMerchantId);
        }

        if ($requestId) {
            $headers['PayPal-Request-Id'] = $requestId;
        }

        $client = Http::baseUrl($this->baseUrl())
            ->withToken($this->accessToken())
            ->withHeaders($headers)
            ->acceptJson()
            ->timeout(25)
            ->retry(2, 300, throw: false);

        $response = match (strtoupper($method)) {
            'GET' => $client->get($path),
            'POST' => $forceEmptyObject
                ? $client->withBody('{}', 'application/json')->post($path)
                : $client->post($path, $body),
            default => throw new RuntimeException('Nicht unterstützte PayPal-HTTP-Methode.'),
        };

        if ($response->failed()) {
            throw new RuntimeException($this->errorMessage($response));
        }

        return $response->json() ?: [];
    }

    private function accessToken(): string
    {
        $this->ensureConfigured();

        $cacheKey = 'paypal_access_token_'.sha1($this->mode().'|'.$this->clientId());
        $cached = Cache::get($cacheKey);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $response = Http::asForm()
            ->withBasicAuth($this->clientId(), $this->clientSecret())
            ->acceptJson()
            ->timeout(20)
            ->post($this->baseUrl().'/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if ($response->failed()) {
            throw new RuntimeException($this->errorMessage($response));
        }

        $token = (string) $response->json('access_token');
        $expiresIn = max(60, (int) $response->json('expires_in', 300) - 60);

        if ($token === '') {
            throw new RuntimeException('PayPal hat keinen Access Token zurückgegeben.');
        }

        Cache::put($cacheKey, $token, now()->addSeconds($expiresIn));

        return $token;
    }

    private function authAssertion(string $sellerMerchantId): string
    {
        $header = $this->base64UrlEncode(json_encode(['alg' => 'none'], JSON_UNESCAPED_SLASHES));
        $payload = $this->base64UrlEncode(json_encode([
            'iss' => $this->clientId(),
            'payer_id' => $sellerMerchantId,
        ], JSON_UNESCAPED_SLASHES));

        return $header.'.'.$payload.'.';
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function extractReferralId(array $response): ?string
    {
        $self = $this->link($response, 'self');

        if (! $self) {
            return null;
        }

        $path = (string) parse_url($self, PHP_URL_PATH);

        return $path !== '' ? basename($path) : null;
    }

    private function link(array $response, string $rel): ?string
    {
        foreach ($response['links'] ?? [] as $link) {
            if (($link['rel'] ?? null) === $rel && filled($link['href'] ?? null)) {
                return (string) $link['href'];
            }
        }

        return null;
    }

    private function errorMessage(Response $response): string
    {
        $issue = $response->json('details.0.issue')
            ?: $response->json('name')
            ?: 'PAYPAL_ERROR';

        $description = $response->json('details.0.description')
            ?: $response->json('message')
            ?: 'Unbekannter PayPal-Fehler.';

        $debugId = $response->json('debug_id');

        return trim('PayPal: '.$issue.' – '.$description.($debugId ? ' (Debug-ID: '.$debugId.')' : ''));
    }

    private function ensureConfigured(): void
    {
        if ($this->isConfigured()) {
            return;
        }

        throw new RuntimeException(
            'PayPal ist noch nicht vollständig konfiguriert. Fehlend: '.implode(', ', $this->missingConfiguration())
        );
    }

    private function mode(): string
    {
        return strtolower((string) config('services.paypal.mode', 'sandbox')) === 'live'
            ? 'live'
            : 'sandbox';
    }

    private function baseUrl(): string
    {
        return $this->mode() === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    private function clientId(): string
    {
        return (string) config('services.paypal.client_id');
    }

    private function clientSecret(): string
    {
        return (string) config('services.paypal.client_secret');
    }

    private function partnerMerchantId(): string
    {
        return (string) config('services.paypal.partner_merchant_id');
    }

    private function frontendUrl(string $path): string
    {
        $base = rtrim((string) config('services.paypal.frontend_url', 'http://localhost:5173'), '/');

        return $base.'/'.ltrim($path, '/');
    }
}
