<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Message;
use App\Services\BookingLifecycleService;
use App\Services\PayPalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class PayPalController extends Controller
{
    public function __construct(
        private readonly PayPalService $paypal,
        private readonly BookingLifecycleService $bookingLifecycle
    ) {
    }

    public function startOnboarding(Request $request): JsonResponse
    {
        $user = $request->user();
        $tutorProfile = $user->tutorProfile;

        if (! $tutorProfile) {
            return response()->json([
                'message' => 'Erstelle zuerst dein Tutorprofil und speichere es.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $referral = $this->paypal->createSellerReferral($user, $tutorProfile);

            $tutorProfile->update([
                'paypal_tracking_id' => $referral['tracking_id'],
                'paypal_referral_id' => $referral['referral_id'],
                'paypal_onboarding_status' => 'pending',
            ]);

            return response()->json([
                'action_url' => $referral['action_url'],
                'paypal' => $this->paypalState($tutorProfile->fresh()),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => $exception->getMessage(),
            ], Response::HTTP_BAD_GATEWAY);
        }
    }

    public function completeOnboarding(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'merchantId' => ['nullable', 'string', 'max:255'],
            'merchantIdInPayPal' => ['required', 'string', 'max:255'],
            'permissionsGranted' => ['nullable'],
            'consentStatus' => ['nullable'],
            'accountStatus' => ['nullable', 'string', 'max:255'],
            'isEmailConfirmed' => ['nullable'],
        ]);

        $tutorProfile = $request->user()->tutorProfile;

        if (! $tutorProfile) {
            return response()->json([
                'message' => 'Tutorprofil wurde nicht gefunden.',
            ], Response::HTTP_NOT_FOUND);
        }

        if (
            filled($validated['merchantId'] ?? null)
            && filled($tutorProfile->paypal_tracking_id)
            && (string) $validated['merchantId'] !== (string) $tutorProfile->paypal_tracking_id
        ) {
            return response()->json([
                'message' => 'Die PayPal-Rückmeldung gehört nicht zu diesem Tutorprofil.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $tutorProfile->update([
            'paypal_merchant_id' => $validated['merchantIdInPayPal'],
            'paypal_onboarding_status' => 'checking',
        ]);

        return $this->refreshOnboarding($request);
    }

    public function refreshOnboarding(Request $request): JsonResponse
    {
        $tutorProfile = $request->user()->tutorProfile;

        if (! $tutorProfile) {
            return response()->json([
                'message' => 'Tutorprofil wurde nicht gefunden.',
            ], Response::HTTP_NOT_FOUND);
        }

        if (! $tutorProfile->paypal_merchant_id) {
            return response()->json([
                'message' => 'Es wurde noch kein PayPal-Konto verbunden.',
                'paypal' => $this->paypalState($tutorProfile),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $status = $this->paypal->sellerStatus((string) $tutorProfile->paypal_merchant_id);
            $paymentsReceivable = (bool) ($status['payments_receivable'] ?? false);
            $emailConfirmed = (bool) ($status['primary_email_confirmed'] ?? false);
            $connected = $paymentsReceivable && $emailConfirmed;

            $tutorProfile->update([
                'paypal_payments_receivable' => $paymentsReceivable,
                'paypal_email_confirmed' => $emailConfirmed,
                'paypal_onboarding_status' => $connected ? 'connected' : 'action_required',
                'paypal_connected_at' => $connected ? now() : null,
            ]);

            return response()->json([
                'message' => $connected
                    ? 'PayPal wurde erfolgreich verbunden.'
                    : 'PayPal ist verbunden, aber das Konto ist noch nicht vollständig zahlungsbereit.',
                'paypal' => $this->paypalState($tutorProfile->fresh()),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => $exception->getMessage(),
                'paypal' => $this->paypalState($tutorProfile),
            ], Response::HTTP_BAD_GATEWAY);
        }
    }

    public function createOrder(Request $request, Booking $booking): JsonResponse
    {
        $booking->load([
            'student:id,name,vorname,nachname,email',
            'tutorProfile.user:id,name,vorname,nachname,email',
            'subject:id,name',
            'paymentMethod:id,name',
        ]);

        if ((int) $booking->student_id !== (int) $request->user()->id) {
            return response()->json([
                'message' => 'Du darfst diese Buchung nicht bezahlen.',
            ], Response::HTTP_FORBIDDEN);
        }

        if ($booking->status !== 'accepted') {
            return response()->json([
                'message' => 'Bezahlt werden kann erst, nachdem der Tutor den Termin angenommen hat.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($this->bookingLifecycle->hasAppointmentStarted($booking)) {
            return response()->json([
                'message' => 'Die PayPal-Zahlung muss vor Beginn des Termins abgeschlossen werden.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! $this->isPayPalBooking($booking)) {
            return response()->json([
                'message' => 'Für diese Buchung wurde PayPal nicht als Zahlungsart ausgewählt.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($booking->payment_status === 'paid') {
            return response()->json([
                'message' => 'Diese Buchung wurde bereits bezahlt.',
                'booking' => $booking,
            ]);
        }

        if (! $booking->tutorProfile?->paypalReady()) {
            return response()->json([
                'message' => 'Das PayPal-Konto des Tutors ist noch nicht zahlungsbereit.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ((float) $booking->payment_amount <= 0) {
            return response()->json([
                'message' => 'Für diese Buchung ist kein gültiger Zahlungsbetrag gespeichert.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $order = $this->paypal->createOrder($booking);
            $approvalUrl = $this->paypal->approvalUrl($order);

            if (! $approvalUrl) {
                return response()->json([
                    'message' => 'PayPal hat keinen Freigabe-Link zurückgegeben.',
                ], Response::HTTP_BAD_GATEWAY);
            }

            $booking->update([
                'paypal_order_id' => $order['id'] ?? null,
                'payment_status' => 'pending',
            ]);

            return response()->json([
                'order_id' => $order['id'] ?? null,
                'approve_url' => $approvalUrl,
                'booking' => $booking->fresh(),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => $exception->getMessage(),
            ], Response::HTTP_BAD_GATEWAY);
        }
    }

    public function captureOrder(Request $request, Booking $booking): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['nullable', 'string', 'max:255'],
        ]);

        $booking->load([
            'student:id,name,vorname,nachname,email',
            'tutorProfile.user:id,name,vorname,nachname,email',
            'subject:id,name',
            'paymentMethod:id,name',
        ]);

        if ((int) $booking->student_id !== (int) $request->user()->id) {
            return response()->json([
                'message' => 'Du darfst diese Zahlung nicht abschließen.',
            ], Response::HTTP_FORBIDDEN);
        }

        if ($booking->payment_status === 'paid') {
            return response()->json([
                'message' => 'Zahlung wurde bereits bestätigt.',
                'booking' => $booking,
            ]);
        }

        if (
            filled($validated['order_id'] ?? null)
            && (string) $validated['order_id'] !== (string) $booking->paypal_order_id
        ) {
            return response()->json([
                'message' => 'Die PayPal-Bestellnummer stimmt nicht mit dieser Buchung überein.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $order = $this->paypal->captureOrder($booking);
            $capture = $this->paypal->captureInfo($order);

            if (($order['status'] ?? null) !== 'COMPLETED' || ! $capture) {
                return response()->json([
                    'message' => 'PayPal hat die Zahlung noch nicht als abgeschlossen bestätigt.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $booking = DB::transaction(function () use ($booking, $capture) {
                $booking->update([
                    'payment_status' => 'paid',
                    'paypal_capture_id' => $capture['id'] ?? null,
                    'paid_at' => now(),
                ]);

                Message::create([
                    'sender_id' => $booking->student_id,
                    'receiver_id' => $booking->tutorProfile->user_id,
                    'booking_id' => $booking->id,
                    'message' => '💳 PayPal-Zahlung bestätigt: '.$this->formatMoney($booking).'.',
                ]);

                return $booking->fresh()->load([
                    'student:id,name,vorname,nachname,email',
                    'tutorProfile.user:id,name,vorname,nachname,email',
                    'subject:id,name',
                    'paymentMethod:id,name',
                    'review:id,booking_id,student_id,tutor_profile_id,rating,comment',
                ]);
            });

            return response()->json([
                'message' => 'Zahlung erfolgreich. PayPal hat die Transaktion bestätigt.',
                'booking' => $booking,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => $exception->getMessage(),
            ], Response::HTTP_BAD_GATEWAY);
        }
    }

    private function isPayPalBooking(Booking $booking): bool
    {
        return str_contains(strtolower((string) $booking->paymentMethod?->name), 'paypal');
    }

    private function formatMoney(Booking $booking): string
    {
        return number_format((float) $booking->payment_amount, 2, ',', '.').' '.strtoupper((string) $booking->payment_currency);
    }

    private function paypalState($tutorProfile): array
    {
        $configured = $this->paypal->isConfigured();

        return [
            'configured' => $configured,
            'missing_configuration' => $this->paypal->missingConfiguration(),
            'status' => $configured ? 'connected' : 'not_connected',
            'connected' => $configured,
            'payments_receivable' => $configured,
            'email_confirmed' => $configured,
        ];
    }
}
