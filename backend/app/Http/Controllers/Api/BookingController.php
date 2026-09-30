<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\BookingRequestReceivedMail;
use App\Mail\BookingRequestStatusMail;
use App\Models\Booking;
use App\Models\Message;
use App\Models\Review;
use App\Models\TutorProfile;
use App\Services\BookingLifecycleService;
use App\Services\PayPalService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingLifecycleService $bookingLifecycle,
        private readonly PayPalService $paypal
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $this->bookingLifecycle->completeExpiredAcceptedBookings();

        $user = $request->user();

        $asStudent = Booking::with([
            'tutorProfile.user:id,name,vorname,nachname,email',
            'subject:id,name',
            'paymentMethod:id,name',
            'review:id,booking_id,student_id,tutor_profile_id,rating,comment',
        ])
            ->where('student_id', $user->id)
            ->latest()
            ->get();

        $tutorProfile = $user->tutorProfile;

        $asTutor = collect();

        if ($tutorProfile) {
            $asTutor = Booking::with([
                'student:id,name,vorname,nachname,email',
                'tutorProfile.user:id,name,vorname,nachname,email',
                'subject:id,name',
                'paymentMethod:id,name',
                'review:id,booking_id,student_id,tutor_profile_id,rating,comment',
            ])
                ->where('tutor_profile_id', $tutorProfile->id)
                ->latest()
                ->get();
        }

        return response()->json([
            'as_student' => $asStudent,
            'as_tutor' => $asTutor,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('tutorProfile.subjects:id,name');

        $validated = $request->validate([
            'tutor_profile_id' => ['required', 'integer', 'exists:tutor_profiles,id'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'requested_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'mode' => ['required', 'string', 'in:online,presence,in_person,both'],    
            'request_type' => ['required', 'string', 'in:paid,exchange,free'],
            'exchange_offer' => ['nullable', 'string', 'max:1000'],
            'message' => ['nullable', 'string', 'max:5000'],
        ]);

        $requestedStart = Carbon::parse(
            $validated['requested_date'] . ' ' . $validated['start_time'],
            BookingLifecycleService::TIMEZONE
        );

        if ($requestedStart->lte(Carbon::now(BookingLifecycleService::TIMEZONE))) {
            return response()->json([
                'message' => 'Der Termin muss in der Zukunft liegen.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $tutorProfile = TutorProfile::with([
            'helpSubjects:id,name',
            'subjects:id',
            'paymentMethods:id,name',
            'availabilities',
        ])->findOrFail($validated['tutor_profile_id']);

        $selectedPaymentMethod = ! empty($validated['payment_method_id'])
            ? $tutorProfile->paymentMethods->firstWhere('id', $validated['payment_method_id'])
            : null;

        if ($validated['request_type'] === 'paid' && ! $selectedPaymentMethod) {
            return response()->json([
                'message' => 'Bitte wähle eine Zahlungsart aus.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($this->isPayPalMethod($selectedPaymentMethod?->name) && ! $tutorProfile->paypalReady()) {
            return response()->json([
                'message' => 'PayPal ist für diesen Tutor noch nicht vollständig verbunden.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (($validated['request_type'] ?? null) === 'exchange') {
            if (! $this->tutorAcceptsExchange($tutorProfile)) {
                return response()->json([
                    'message' => 'Dieser Tutor bietet keine Tauschleistung an.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if (! $user->tutorProfile) {
                return response()->json([
                    'message' => 'Tauschleistung ist nur möglich, wenn du selbst ein Tutorprofil hast.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if (! $this->canOfferExchangeToTutor($user, $tutorProfile)) {
                return response()->json([
                    'message' => 'Tauschleistung ist nur möglich, wenn du Tutor in einem Fach bist, in dem die andere Person Hilfe braucht.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if (empty($validated['exchange_offer'])) {
                return response()->json([
                    'message' => 'Bitte beschreibe deine Tauschleistung.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        if ($tutorProfile->user_id === $user->id) {
            return response()->json([
                'message' => 'Du kannst bei dir selbst keine Nachhilfe anfragen.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (
            !empty($validated['subject_id'])
            && !$tutorProfile->subjects->contains('id', $validated['subject_id'])
        ) {
            return response()->json([
                'message' => 'Dieser Tutor bietet dieses Fach nicht an.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (
            !empty($validated['payment_method_id'])
            && !$tutorProfile->paymentMethods->contains('id', $validated['payment_method_id'])
        ) {
            return response()->json([
                'message' => 'Dieser Tutor bietet diese Zahlungsart nicht an.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!$this->slotIsAvailable($tutorProfile, $validated, $user)) {
            return response()->json([
                'message' => 'Diese Uhrzeit ist für diesen Tutor nicht verfügbar.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $paymentAmount = $this->paymentAmount($tutorProfile, $validated);
        $paymentStatus = $validated['request_type'] === 'paid' ? 'unpaid' : 'not_required';

        $booking = Booking::create([
            'student_id' => $user->id,
            'tutor_profile_id' => $tutorProfile->id,
            'subject_id' => $validated['subject_id'] ?? null,
            'payment_method_id' => $validated['payment_method_id'] ?? null,
            'message' => $validated['message'] ?? null,
            'requested_date' => $validated['requested_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'mode' => $validated['mode'],
            'request_type' => $validated['request_type'],
            'exchange_offer' => $validated['exchange_offer'] ?? null,
            'status' => 'open',
            'payment_amount' => $paymentAmount,
            'payment_currency' => strtoupper((string) config('services.paypal.currency', 'EUR')),
            'payment_status' => $paymentStatus,
        ]);

        $booking->load($this->bookingRelations());

        Message::create([
            'sender_id' => $user->id,
            'receiver_id' => $tutorProfile->user_id,
            'booking_id' => $booking->id,
            'message' => $this->bookingMessagePreview($booking),
        ]);

        $this->sendBookingRequestEmail($booking);

        return response()->json($booking, Response::HTTP_CREATED);
    }

    public function accept(Request $request, Booking $booking): JsonResponse
    {
        return $this->changeStatus($request, $booking, 'accepted');
    }

    public function decline(Request $request, Booking $booking): JsonResponse
    {
        return $this->changeStatus($request, $booking, 'declined');
    }

    public function cancel(Request $request, Booking $booking): JsonResponse
    {
        $user = $request->user();
        $booking->load($this->bookingRelations());

        if (! $this->currentUserOwnsBookingAsTutor($user, $booking)) {
            return response()->json([
                'message' => 'Du darfst diesen Termin nicht absagen.',
            ], Response::HTTP_FORBIDDEN);
        }

        if ($booking->status !== 'accepted') {
            return response()->json([
                'message' => 'Nur angenommene Termine können abgesagt werden.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($this->bookingLifecycle->hasAppointmentStarted($booking)) {
            return response()->json([
                'message' => 'Der Termin hat bereits begonnen und kann nicht mehr abgesagt werden.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($booking->payment_status === 'paid' && $this->isPayPalBooking($booking)) {
            try {
                $refund = $this->paypal->refundCapture($booking);
                $refundStatus = strtoupper((string) ($refund['status'] ?? ''));

                if (! in_array($refundStatus, ['COMPLETED', 'PENDING'], true)) {
                    throw new \RuntimeException('PayPal hat die Rückerstattung nicht bestätigt.');
                }

                $booking->payment_status = $refundStatus === 'COMPLETED'
                    ? 'refunded'
                    : 'refund_pending';
                $booking->refunded_at = $refundStatus === 'COMPLETED' ? now() : null;
            } catch (Throwable $exception) {
                report($exception);

                return response()->json([
                    'message' => 'Der Termin wurde nicht abgesagt, weil die automatische PayPal-Rückerstattung fehlgeschlagen ist: '.$exception->getMessage(),
                ], Response::HTTP_BAD_GATEWAY);
            }
        }

        $booking->status = 'cancelled';
        $booking->save();

        $booking->refresh()->load($this->bookingRelations());

        $this->createStatusChatMessage($booking, 'cancelled');
        $this->sendBookingStatusEmail($booking, 'cancelled');

        return response()->json($booking);
    }

    public function review(Request $request, Booking $booking): JsonResponse
    {
        $this->bookingLifecycle->completeExpiredAcceptedBookings();
        $booking->refresh();

        $user = $request->user();
        $booking->load($this->bookingRelations());

        if ((int) $booking->student_id !== (int) $user->id) {
            return response()->json([
                'message' => 'Du darfst diesen Termin nicht bewerten.',
            ], Response::HTTP_FORBIDDEN);
        }

        if ($booking->status !== 'completed') {
            return response()->json([
                'message' => 'Nur abgeschlossene Termine können bewertet werden.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($booking->review()->exists()) {
            return response()->json([
                'message' => 'Du hast diesen Termin bereits bewertet.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $review = Review::create([
            'student_id' => $user->id,
            'tutor_profile_id' => $booking->tutor_profile_id,
            'booking_id' => $booking->id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
        ]);

        $review->load('student:id,name,vorname,nachname');

        return response()->json($review, Response::HTTP_CREATED);
    }

    private function changeStatus(Request $request, Booking $booking, string $status): JsonResponse
    {
        $user = $request->user();
        $booking->load($this->bookingRelations());

        if (! $this->currentUserOwnsBookingAsTutor($user, $booking)) {
            return response()->json([
                'message' => 'Du darfst diese Anfrage nicht verwalten.',
            ], Response::HTTP_FORBIDDEN);
        }

        if ($booking->status !== 'open') {
            return response()->json([
                'message' => 'Diese Anfrage wurde bereits bearbeitet.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($status === 'accepted' && $this->bookingLifecycle->hasAppointmentStarted($booking)) {
            return response()->json([
                'message' => 'Dieser Termin hat bereits begonnen und kann nicht mehr angenommen werden.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $booking->update([
            'status' => $status,
        ]);

        $booking->refresh()->load($this->bookingRelations());

        $this->createStatusChatMessage($booking, $status);
        $this->sendBookingStatusEmail($booking, $status);

        return response()->json($booking);
    }

    private function currentUserOwnsBookingAsTutor($user, Booking $booking): bool
    {
        $user->loadMissing('tutorProfile');

        return $user->tutorProfile
            && (int) $user->tutorProfile->id === (int) $booking->tutor_profile_id;
    }

    private function createStatusChatMessage(Booking $booking, string $status): void
    {
        $tutorUser = $booking->tutorProfile?->user;
        $student = $booking->student;

        if (! $tutorUser || ! $student) {
            return;
        }

        Message::create([
            'sender_id' => $tutorUser->id,
            'receiver_id' => $student->id,
            'booking_id' => $booking->id,
            'message' => $this->statusChatText($booking, $status),
        ]);
    }

    private function statusChatText(Booking $booking, string $status): string
    {
        $subject = $booking->subject?->name ?? 'Fach nicht angegeben';
        $date = $this->formatDate($booking->requested_date);
        $time = $this->formatTime($booking->start_time) . ' - ' . $this->formatTime($booking->end_time) . ' Uhr';

        if ($status === 'accepted') {
            $paymentHint = $this->isPayPalBooking($booking)
                ? ' Bezahle jetzt sicher über PayPal im Dashboard.'
                : '';

            return "✅ Anfrage angenommen: {$subject} am {$date} von {$time}.{$paymentHint}";
        }

        return match ($status) {
            'cancelled' => "⚠️ Termin abgesagt: {$subject} am {$date} von {$time}.",
            default => "❌ Anfrage abgelehnt: {$subject} am {$date} von {$time}.",
        };
    }

    private function sendBookingStatusEmail(Booking $booking, string $status): void
    {
        $receiver = $booking->student;

        if (! $receiver?->email) {
            return;
        }

        $frontendUrl = env('FRONTEND_URL') ?: config('app.url');
        $chatUrl = rtrim((string) $frontendUrl, '/') . '/chat';

        try {
            Mail::to($receiver->email)->send(
                new BookingRequestStatusMail($booking, $status, $chatUrl)
            );
        } catch (\Throwable $exception) {
            Log::warning('Booking status email could not be sent.', [
                'booking_id' => $booking->id,
                'receiver_id' => $receiver->id,
                'status' => $status,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function bookingMessagePreview(Booking $booking): string
    {
        return '📅 Neue Nachhilfe-Anfrage: '
            . ($booking->subject?->name ?? 'Fach nicht angegeben')
            . ' am '
            . $this->formatDate($booking->requested_date)
            . ' von '
            . $this->formatTime($booking->start_time)
            . ' bis '
            . $this->formatTime($booking->end_time)
            . '.';
    }

    private function sendBookingRequestEmail(Booking $booking): void
    {
        $receiver = $booking->tutorProfile?->user;

        if (! $receiver?->email) {
            return;
        }

        $frontendUrl = env('FRONTEND_URL') ?: config('app.url');
        $dashboardUrl = rtrim((string) $frontendUrl, '/') . '/dashboard';

        try {
            Mail::to($receiver->email)->send(
                new BookingRequestReceivedMail($booking, $dashboardUrl)
            );
        } catch (\Throwable $exception) {
            Log::warning('Booking request email could not be sent.', [
                'booking_id' => $booking->id,
                'receiver_id' => $receiver->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function bookingRelations(): array
    {
        return [
            'student:id,name,vorname,nachname,email',
            'tutorProfile.user:id,name,vorname,nachname,email',
            'subject:id,name',
            'paymentMethod:id,name',
        ];
    }

    private function formatDate($value): string
    {
        if (! $value) {
            return 'Datum nicht angegeben';
        }

        return Carbon::parse($value)->format('d.m.Y');
    }

    private function formatTime($value): string
    {
        if (! $value) {
            return '--:--';
        }

        return substr((string) $value, 0, 5);
    }

    private function paymentAmount(TutorProfile $tutorProfile, array $data): float
    {
        if (($data['request_type'] ?? null) !== 'paid') {
            return 0.0;
        }

        $start = Carbon::parse($data['requested_date'].' '.$data['start_time'], BookingLifecycleService::TIMEZONE);
        $end = Carbon::parse($data['requested_date'].' '.$data['end_time'], BookingLifecycleService::TIMEZONE);
        $minutes = max(1, $start->diffInMinutes($end));

        return round((float) $tutorProfile->price_per_hour * ($minutes / 60), 2);
    }

    private function isPayPalMethod(?string $name): bool
    {
        return str_contains(strtolower((string) $name), 'paypal');
    }

    private function isPayPalBooking(Booking $booking): bool
    {
        $booking->loadMissing('paymentMethod:id,name');

        return $this->isPayPalMethod($booking->paymentMethod?->name);
    }

    private function tutorAcceptsExchange(TutorProfile $tutorProfile): bool
    {
        return $tutorProfile->paymentMethods->contains(function ($paymentMethod) {
            return str_contains(strtolower((string) $paymentMethod->name), 'tausch');
        });
    }

    private function canOfferExchangeToTutor($user, TutorProfile $tutorProfile): bool
    {
        $myTutorSubjectIds = $user->tutorProfile?->subjects
            ?->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all() ?? [];

        if (count($myTutorSubjectIds) === 0) {
            return false;
        }

        $otherHelpSubjectIds = $tutorProfile->helpSubjects
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all() ?? [];

        if (count($otherHelpSubjectIds) === 0) {
            return false;
        }

        return count(array_intersect($myTutorSubjectIds, $otherHelpSubjectIds)) > 0;
    }

    private function slotIsAvailable(TutorProfile $tutorProfile, array $data, $user): bool
    {
        $date = Carbon::parse($data['requested_date'], BookingLifecycleService::TIMEZONE)->startOfDay();
        $weekday = strtolower($date->englishDayOfWeek);

        $start = Carbon::parse($date->toDateString() . ' ' . $data['start_time'], BookingLifecycleService::TIMEZONE);
        $end = Carbon::parse($date->toDateString() . ' ' . $data['end_time'], BookingLifecycleService::TIMEZONE);
        $requestedMode = $this->normalizeMode($data['mode']);

        if ($start->lte(Carbon::now(BookingLifecycleService::TIMEZONE))) {
            return false;
        }

        $hasTutorAvailability = $tutorProfile->availabilities
            ->where('weekday', $weekday)
            ->where('is_available', true)
            ->contains(function ($availability) use ($start, $end, $requestedMode) {
                if (! $this->availabilitySupportsMode($availability->mode, $requestedMode)) {
                    return false;
                }

                $availableStart = Carbon::parse($start->toDateString() . ' ' . $availability->start_time, BookingLifecycleService::TIMEZONE);
                $availableEnd = Carbon::parse($start->toDateString() . ' ' . $availability->end_time, BookingLifecycleService::TIMEZONE);

                return $start->gte($availableStart) && $end->lte($availableEnd);
            });

        if (! $hasTutorAvailability) {
            return false;
        }

        if ($data['request_type'] === 'exchange') {
            $user->loadMissing('tutorProfile.availabilities');

            if (! $user->tutorProfile) {
                return false;
            }

            $hasOwnAvailability = $user->tutorProfile->availabilities
                ->where('weekday', $weekday)
                ->where('is_available', true)
                ->contains(function ($availability) use ($start, $end, $requestedMode) {
                    if (! $this->availabilitySupportsMode($availability->mode, $requestedMode)) {
                        return false;
                    }

                    $availableStart = Carbon::parse($start->toDateString() . ' ' . $availability->start_time);
                    $availableEnd = Carbon::parse($start->toDateString() . ' ' . $availability->end_time);

                    return $start->gte($availableStart) && $end->lte($availableEnd);
                });

            if (! $hasOwnAvailability) {
                return false;
            }
        }

        $tutorHasConflict = Booking::query()
            ->where('tutor_profile_id', $tutorProfile->id)
            ->whereDate('requested_date', $date->toDateString())
            ->whereIn('status', ['open', 'accepted'])
            ->where(function ($query) use ($data) {
                $query->where('start_time', '<', $data['end_time'])
                    ->where('end_time', '>', $data['start_time']);
            })
            ->exists();

        if ($tutorHasConflict) {
            return false;
        }

        if ($data['request_type'] === 'exchange') {
            $currentTutorProfileId = $user->tutorProfile->id;

            $currentUserHasConflict = Booking::query()
                ->whereDate('requested_date', $date->toDateString())
                ->whereIn('status', ['open', 'accepted'])
                ->where(function ($query) use ($user, $currentTutorProfileId) {
                    $query->where('student_id', $user->id)
                        ->orWhere('tutor_profile_id', $currentTutorProfileId);
                })
                ->where(function ($query) use ($data) {
                    $query->where('start_time', '<', $data['end_time'])
                        ->where('end_time', '>', $data['start_time']);
                })
                ->exists();

            if ($currentUserHasConflict) {
                return false;
            }
        }

        return true;
    }

    private function availabilitySupportsMode(?string $availabilityMode, string $requestedMode): bool
    {
        $availabilityMode = $this->normalizeMode($availabilityMode);
        $requestedMode = $this->normalizeMode($requestedMode);

        return $availabilityMode === 'both' || $availabilityMode === $requestedMode;
    }

    private function normalizeMode(?string $mode): string
    {
        return match ($mode) {
            'in_person' => 'presence',
            'online' => 'online',
            'presence' => 'presence',
            'both' => 'both',
            default => 'online',
        };
    }

}
