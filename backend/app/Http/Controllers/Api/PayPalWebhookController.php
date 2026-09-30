<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\TutorProfile;
use App\Services\PayPalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class PayPalWebhookController extends Controller
{
    public function __construct(
        private readonly PayPalService $paypal
    ) {
    }

    public function handle(Request $request): JsonResponse
    {
        $event = $request->all();
        $headers = collect($request->headers->all())
            ->mapWithKeys(fn ($value, $key) => [strtolower($key) => is_array($value) ? ($value[0] ?? '') : $value])
            ->all();

        try {
            if (! $this->paypal->verifyWebhook($headers, $event)) {
                return response()->json([
                    'message' => 'Ungültige PayPal-Webhook-Signatur.',
                ], Response::HTTP_UNAUTHORIZED);
            }

            $this->processEvent($event);

            return response()->json(['ok' => true]);
        } catch (Throwable $exception) {
            Log::error('PayPal webhook failed.', [
                'event_id' => $event['id'] ?? null,
                'event_type' => $event['event_type'] ?? null,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Webhook konnte nicht verarbeitet werden.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function processEvent(array $event): void
    {
        $type = (string) ($event['event_type'] ?? '');
        $resource = $event['resource'] ?? [];

        if ($type === 'PAYMENT.CAPTURE.COMPLETED') {
            $captureId = data_get($resource, 'id');
            $orderId = data_get($resource, 'supplementary_data.related_ids.order_id');

            $booking = Booking::query()
                ->when($orderId, fn ($query) => $query->where('paypal_order_id', $orderId))
                ->when(! $orderId && $captureId, fn ($query) => $query->where('paypal_capture_id', $captureId))
                ->first();

            if ($booking) {
                $booking->update([
                    'payment_status' => 'paid',
                    'paypal_capture_id' => $captureId ?: $booking->paypal_capture_id,
                    'paid_at' => $booking->paid_at ?: now(),
                ]);
            }

            return;
        }

        if ($type === 'PAYMENT.CAPTURE.REFUNDED') {
            $captureId = data_get($resource, 'supplementary_data.related_ids.capture_id');

            if (! $captureId) {
                foreach ($resource['links'] ?? [] as $link) {
                    if (($link['rel'] ?? null) !== 'up' || empty($link['href'])) {
                        continue;
                    }

                    $captureId = basename((string) parse_url((string) $link['href'], PHP_URL_PATH));
                    break;
                }
            }

            $booking = $captureId
                ? Booking::where('paypal_capture_id', $captureId)->first()
                : null;

            if ($booking) {
                $booking->update([
                    'payment_status' => 'refunded',
                    'refunded_at' => now(),
                ]);
            }

            return;
        }

        if ($type === 'PAYMENT.CAPTURE.DENIED') {
            $captureId = data_get($resource, 'id');
            $orderId = data_get($resource, 'supplementary_data.related_ids.order_id');

            Booking::query()
                ->when($orderId, fn ($query) => $query->where('paypal_order_id', $orderId))
                ->when(! $orderId && $captureId, fn ($query) => $query->where('paypal_capture_id', $captureId))
                ->update(['payment_status' => 'failed']);

            return;
        }

        if ($type === 'MERCHANT.ONBOARDING.COMPLETED') {
            $merchantId = data_get($resource, 'merchant_id')
                ?: data_get($resource, 'merchantIdInPayPal');
            $trackingId = data_get($resource, 'tracking_id');

            $profile = TutorProfile::query()
                ->when($merchantId, fn ($query) => $query->where('paypal_merchant_id', $merchantId))
                ->when(! $merchantId && $trackingId, fn ($query) => $query->where('paypal_tracking_id', $trackingId))
                ->first();

            if ($profile) {
                $profile->update(['paypal_onboarding_status' => 'checking']);
            }

            return;
        }

        if ($type === 'MERCHANT.PARTNER-CONSENT.REVOKED') {
            $merchantId = data_get($resource, 'merchant_id')
                ?: data_get($resource, 'merchantIdInPayPal');

            if ($merchantId) {
                TutorProfile::where('paypal_merchant_id', $merchantId)->update([
                    'paypal_onboarding_status' => 'revoked',
                    'paypal_payments_receivable' => false,
                    'paypal_email_confirmed' => false,
                    'paypal_connected_at' => null,
                ]);
            }
        }
    }
}
