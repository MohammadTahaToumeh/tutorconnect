<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Message;
use App\Models\TutorProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ChatController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->load([
            'tutorProfile.helpSubjects:id,name',
            'tutorProfile.subjects:id,name',
            'tutorProfile.paymentMethods:id,name',
            'tutorProfile.availabilities:id,tutor_profile_id,weekday,start_time,end_time,mode,is_available',
        ]);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'vorname' => $user->vorname,
            'nachname' => $user->nachname,
            'email' => $user->email,
            'help_subjects' => $user->tutorProfile?->helpSubjects ?? [],
            'tutor_profile' => $user->tutorProfile,
        ]);
    }

    public function conversations(Request $request): JsonResponse
    {
        $currentUser = $request->user();

        $messages = Message::query()
            ->where(function ($query) use ($currentUser) {
                $query->where('sender_id', $currentUser->id)
                    ->orWhere('receiver_id', $currentUser->id);
            })
            ->with([
                'booking.subject:id,name',
            ])
            ->latest()
            ->get();

        $conversations = [];

        foreach ($messages as $message) {
            $otherUserId = $message->sender_id === $currentUser->id
                ? $message->receiver_id
                : $message->sender_id;

            if (isset($conversations[$otherUserId])) {
                continue;
            }

            $otherUser = User::with([
                'tutorProfile.user:id,name,vorname,nachname,email,studiengang,semester',
                'tutorProfile.helpSubjects:id,name',
                'tutorProfile.subjects:id,name',
                'tutorProfile.paymentMethods:id,name',
                'tutorProfile.availabilities:id,tutor_profile_id,weekday,start_time,end_time,mode,is_available',
            ])->find($otherUserId);

            if (!$otherUser) {
                continue;
            }

            $conversations[$otherUserId] = [
                'partner' => $this->formatPartner($otherUser),
                'last_message' => [
                    'id' => $message->id,
                    'message' => $message->message,
                    'created_at' => $message->created_at,
                    'is_mine' => $message->sender_id === $currentUser->id,
                ],
            ];
        }

        return response()->json(array_values($conversations));
    }

    public function searchTutors(Request $request): JsonResponse
    {
        $user = $request->user();
        $search = trim((string) $request->query('search', ''));

        $tutors = TutorProfile::with([
            'user:id,name,vorname,nachname,email,studiengang,semester',
            'helpSubjects:id,name',
            'subjects:id,name',
            'paymentMethods:id,name',
            'availabilities:id,tutor_profile_id,weekday,start_time,end_time,mode,is_available',
        ])
            ->where('is_active', true)
            ->where('user_id', '!=', $user->id)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->whereHas('user', function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('vorname', 'like', "%{$search}%")
                            ->orWhere('nachname', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                        ->orWhereHas('subjects', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->limit(10)
            ->get()
            ->map(fn (TutorProfile $tutorProfile) => $this->formatTutor($tutorProfile));

        return response()->json($tutors);
    }

    public function showTutor(TutorProfile $tutorProfile): JsonResponse
    {
        $tutorProfile->load([
            'user:id,name,vorname,nachname,email,studiengang,semester',
            'helpSubjects:id,name',
            'subjects:id,name',
            'paymentMethods:id,name',
            'availabilities:id,tutor_profile_id,weekday,start_time,end_time,mode,is_available',
        ]);

        return response()->json($this->formatTutor($tutorProfile));
    }

    public function messages(Request $request, TutorProfile $tutorProfile): JsonResponse
    {
        return $this->messagesWithUser($request, $tutorProfile->user);
    }

    public function sendMessage(Request $request, TutorProfile $tutorProfile): JsonResponse
    {
        return $this->sendMessageToUser($request, $tutorProfile->user);
    }

    public function messagesWithUser(Request $request, User $user): JsonResponse
    {
        $currentUser = $request->user();

        if ($user->id === $currentUser->id) {
            return response()->json([
                'message' => 'Du kannst keinen Chat mit dir selbst öffnen.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!$this->canOpenChatWithUser($currentUser->id, $user)) {
            return response()->json([
                'message' => 'Dieser Chat kann nicht geöffnet werden.',
            ], Response::HTTP_FORBIDDEN);
        }

        $afterId = (int) $request->query('after_id', 0);

        $messages = $this->messagesQueryBetween($currentUser->id, $user->id)
            ->when($afterId > 0, function ($query) use ($afterId) {
                $query->where('id', '>', $afterId);
            })
            ->with([
                'sender:id,name,vorname,nachname,email',
                'receiver:id,name,vorname,nachname,email',
                'booking.student:id,name,vorname,nachname,email',
                'booking.tutorProfile.user:id,name,vorname,nachname,email',
                'booking.subject:id,name',
                'booking.paymentMethod:id,name',
            ])
            ->orderBy('created_at')
            ->get()
            ->map(fn (Message $message) => $this->formatMessage($message, $currentUser->id));

        return response()->json($messages);
    }

    public function sendMessageToUser(Request $request, User $user): JsonResponse
    {
        $currentUser = $request->user();

        if ($user->id === $currentUser->id) {
            return response()->json([
                'message' => 'Du kannst dir selbst keine Nachricht senden.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!$this->canOpenChatWithUser($currentUser->id, $user)) {
            return response()->json([
                'message' => 'Du kannst nur bestehende Chats mit normalen Nutzern fortsetzen.',
            ], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $message = Message::create([
            'sender_id' => $currentUser->id,
            'receiver_id' => $user->id,
            'message' => $validated['message'],
        ]);

        $message->load([
            'sender:id,name,vorname,nachname,email',
            'receiver:id,name,vorname,nachname,email',
        ]);

        return response()->json(
            $this->formatMessage($message, $currentUser->id),
            Response::HTTP_CREATED
        );
    }

    public function bookingOptions(Request $request, TutorProfile $tutorProfile): JsonResponse
    {
        $validated = $request->validate([
            'mode' => ['required', 'string', 'in:online,presence,in_person'],
            'request_type' => ['required', 'string', 'in:paid,exchange,free'],
        ]);

        $mode = $this->normalizeMode($validated['mode']);
        $requestType = $validated['request_type'];
        $now = Carbon::now();
        $firstDate = Carbon::today();
        $lastDate = $firstDate->copy()->addDays(59);

        $tutorAvailabilities = $tutorProfile->availabilities()
            ->where('is_available', true)
            ->get()
            ->filter(fn ($availability) => $this->availabilitySupportsMode($availability->mode, $mode))
            ->values();

        $currentUser = $request->user();
        $currentTutorProfile = null;
        $currentUserAvailabilities = collect();

        if ($requestType === 'exchange') {
            $currentUser->loadMissing('tutorProfile.availabilities');
            $currentTutorProfile = $currentUser->tutorProfile;

            if (! $currentTutorProfile) {
                return response()->json([
                    'mode' => $mode,
                    'request_type' => $requestType,
                    'dates' => [],
                ]);
            }

            $currentUserAvailabilities = $currentTutorProfile->availabilities
                ->where('is_available', true)
                ->filter(fn ($availability) => $this->availabilitySupportsMode($availability->mode, $mode))
                ->values();
        }

        $tutorBookings = Booking::query()
            ->where('tutor_profile_id', $tutorProfile->id)
            ->whereBetween('requested_date', [$firstDate->toDateString(), $lastDate->toDateString()])
            ->whereIn('status', ['open', 'accepted'])
            ->get(['requested_date', 'start_time', 'end_time']);

        $currentUserBookings = collect();

        if ($requestType === 'exchange' && $currentTutorProfile) {
            $currentUserBookings = Booking::query()
                ->whereBetween('requested_date', [$firstDate->toDateString(), $lastDate->toDateString()])
                ->whereIn('status', ['open', 'accepted'])
                ->where(function ($query) use ($currentUser, $currentTutorProfile) {
                    $query->where('student_id', $currentUser->id)
                        ->orWhere('tutor_profile_id', $currentTutorProfile->id);
                })
                ->get(['requested_date', 'start_time', 'end_time']);
        }

        $dates = [];

        for ($dayOffset = 0; $dayOffset < 60; $dayOffset++) {
            $date = $firstDate->copy()->addDays($dayOffset);

            $slots = $this->availableSlotsForDate(
                $date,
                $mode,
                $requestType,
                $now,
                $tutorAvailabilities,
                $currentUserAvailabilities,
                $tutorBookings,
                $currentUserBookings
            );

            if (count($slots) === 0) {
                continue;
            }

            $dates[] = [
                'date' => $date->toDateString(),
                'label' => $this->dateOptionLabel($date),
                'slots' => $slots,
            ];
        }

        return response()->json([
            'mode' => $mode,
            'request_type' => $requestType,
            'dates' => $dates,
        ]);
    }

    public function slots(Request $request, TutorProfile $tutorProfile): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'mode' => ['nullable', 'string', 'in:online,presence,in_person'],
            'request_type' => ['nullable', 'string', 'in:paid,exchange,free'],
        ]);

        $date = Carbon::parse($validated['date'])->startOfDay();
        $mode = $this->normalizeMode($validated['mode'] ?? 'online');
        $requestType = $validated['request_type'] ?? 'paid';
        $currentUser = $request->user();

        $tutorAvailabilities = $tutorProfile->availabilities()
            ->where('is_available', true)
            ->get()
            ->filter(fn ($availability) => $this->availabilitySupportsMode($availability->mode, $mode))
            ->values();

        $currentTutorProfile = null;
        $currentUserAvailabilities = collect();

        if ($requestType === 'exchange') {
            $currentUser->loadMissing('tutorProfile.availabilities');
            $currentTutorProfile = $currentUser->tutorProfile;

            if (! $currentTutorProfile) {
                return response()->json([]);
            }

            $currentUserAvailabilities = $currentTutorProfile->availabilities
                ->where('is_available', true)
                ->filter(fn ($availability) => $this->availabilitySupportsMode($availability->mode, $mode))
                ->values();
        }

        $tutorBookings = Booking::query()
            ->where('tutor_profile_id', $tutorProfile->id)
            ->whereDate('requested_date', $date->toDateString())
            ->whereIn('status', ['open', 'accepted'])
            ->get(['requested_date', 'start_time', 'end_time']);

        $currentUserBookings = collect();

        if ($requestType === 'exchange' && $currentTutorProfile) {
            $currentUserBookings = Booking::query()
                ->whereDate('requested_date', $date->toDateString())
                ->whereIn('status', ['open', 'accepted'])
                ->where(function ($query) use ($currentUser, $currentTutorProfile) {
                    $query->where('student_id', $currentUser->id)
                        ->orWhere('tutor_profile_id', $currentTutorProfile->id);
                })
                ->get(['requested_date', 'start_time', 'end_time']);
        }

        $slots = $this->availableSlotsForDate(
            $date,
            $mode,
            $requestType,
            Carbon::now(),
            $tutorAvailabilities,
            $currentUserAvailabilities,
            $tutorBookings,
            $currentUserBookings
        );

        return response()->json($slots);
    }

    private function availableSlotsForDate(
        Carbon $date,
        string $mode,
        string $requestType,
        Carbon $now,
        $tutorAvailabilities,
        $currentUserAvailabilities,
        $tutorBookings,
        $currentUserBookings
    ): array {
        $weekday = strtolower($date->englishDayOfWeek);

        $dayTutorAvailabilities = $tutorAvailabilities
            ->where('weekday', $weekday)
            ->values();

        if ($dayTutorAvailabilities->isEmpty()) {
            return [];
        }

        $dayCurrentUserAvailabilities = collect();

        if ($requestType === 'exchange') {
            $dayCurrentUserAvailabilities = $currentUserAvailabilities
                ->where('weekday', $weekday)
                ->values();

            if ($dayCurrentUserAvailabilities->isEmpty()) {
                return [];
            }
        }

        $dayTutorBookings = $this->bookingsForDate($tutorBookings, $date);
        $dayCurrentUserBookings = $this->bookingsForDate($currentUserBookings, $date);

        $slotsByKey = [];

        foreach ($dayTutorAvailabilities as $availability) {
            $cursor = Carbon::parse($date->toDateString() . ' ' . $availability->start_time);
            $availabilityEnd = Carbon::parse($date->toDateString() . ' ' . $availability->end_time);

            while ($cursor->copy()->addHour()->lte($availabilityEnd)) {
                $slotStart = $cursor->copy();
                $slotEnd = $cursor->copy()->addHour();
                $cursor->addHour();

                if ($slotStart->lte($now)) {
                    continue;
                }

                if (
                    $requestType === 'exchange'
                    && ! $dayCurrentUserAvailabilities->contains(function ($ownAvailability) use ($date, $slotStart, $slotEnd) {
                        $ownStart = Carbon::parse($date->toDateString() . ' ' . $ownAvailability->start_time);
                        $ownEnd = Carbon::parse($date->toDateString() . ' ' . $ownAvailability->end_time);

                        return $slotStart->gte($ownStart) && $slotEnd->lte($ownEnd);
                    })
                ) {
                    continue;
                }

                if ($this->bookingsOverlapSlot($dayTutorBookings, $date, $slotStart, $slotEnd)) {
                    continue;
                }

                if (
                    $requestType === 'exchange'
                    && $this->bookingsOverlapSlot($dayCurrentUserBookings, $date, $slotStart, $slotEnd)
                ) {
                    continue;
                }

                $key = $slotStart->format('H:i') . '|' . $slotEnd->format('H:i');

                $slotsByKey[$key] = [
                    'start_time' => $slotStart->format('H:i'),
                    'end_time' => $slotEnd->format('H:i'),
                    'label' => $slotStart->format('H:i') . ' - ' . $slotEnd->format('H:i'),
                    'mode' => $mode,
                ];
            }
        }

        $slots = array_values($slotsByKey);

        usort($slots, fn (array $a, array $b) => strcmp($a['start_time'], $b['start_time']));

        return $slots;
    }

    private function bookingsForDate($bookings, Carbon $date)
    {
        return $bookings
            ->filter(function (Booking $booking) use ($date) {
                return Carbon::parse($booking->requested_date)->toDateString() === $date->toDateString();
            })
            ->values();
    }

    private function bookingsOverlapSlot($bookings, Carbon $date, Carbon $slotStart, Carbon $slotEnd): bool
    {
        return $bookings->contains(function (Booking $booking) use ($date, $slotStart, $slotEnd) {
            $bookingStart = Carbon::parse($date->toDateString() . ' ' . $booking->start_time);
            $bookingEnd = Carbon::parse($date->toDateString() . ' ' . $booking->end_time);

            return $slotStart->lt($bookingEnd) && $slotEnd->gt($bookingStart);
        });
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

    private function dateOptionLabel(Carbon $date): string
    {
        $weekday = match (strtolower($date->englishDayOfWeek)) {
            'monday' => 'Montag',
            'tuesday' => 'Dienstag',
            'wednesday' => 'Mittwoch',
            'thursday' => 'Donnerstag',
            'friday' => 'Freitag',
            'saturday' => 'Samstag',
            'sunday' => 'Sonntag',
            default => '',
        };

        return $weekday . ', ' . $date->format('d.m.Y');
    }

    private function canOpenChatWithUser(int $currentUserId, User $otherUser): bool
    {
        $otherUser->loadMissing('tutorProfile');

        if ($otherUser->tutorProfile) {
            return true;
        }

        return $this->messagesQueryBetween($currentUserId, $otherUser->id)->exists();
    }

    private function messagesQueryBetween(int $firstUserId, int $secondUserId)
    {
        return Message::query()
            ->where(function ($query) use ($firstUserId, $secondUserId) {
                $query->where('sender_id', $firstUserId)
                    ->where('receiver_id', $secondUserId);
            })
            ->orWhere(function ($query) use ($firstUserId, $secondUserId) {
                $query->where('sender_id', $secondUserId)
                    ->where('receiver_id', $firstUserId);
            });
    }

    private function formatPartner(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name ?: trim(($user->vorname ?? '') . ' ' . ($user->nachname ?? '')),
            'vorname' => $user->vorname,
            'nachname' => $user->nachname,
            'email' => $user->email,
            'tutor_profile' => $user->tutorProfile
                ? $this->formatTutor($user->tutorProfile)
                : null,
        ];
    }

    private function formatTutor(TutorProfile $tutorProfile): array
    {
        $tutorProfile->loadMissing([
            'helpSubjects:id,name',
            'subjects:id,name',
            'paymentMethods:id,name',
            'availabilities:id,tutor_profile_id,weekday,start_time,end_time,mode,is_available',
        ]);

        return [
            'id' => $tutorProfile->id,
            'user_id' => $tutorProfile->user_id,
            'name' => $tutorProfile->user?->name,
            'vorname' => $tutorProfile->user?->vorname,
            'nachname' => $tutorProfile->user?->nachname,
            'description' => $tutorProfile->description,
            'price_per_hour' => $tutorProfile->price_per_hour,
            'teaching_mode' => $tutorProfile->teaching_mode,
            'help_subjects' => $tutorProfile->helpSubjects->map(fn ($subject) => [
                'id' => $subject->id,
                'name' => $subject->name,
            ])->values(),
            'subjects' => $tutorProfile->subjects->map(fn ($subject) => [
                'id' => $subject->id,
                'name' => $subject->name,
            ])->values(),
            'payment_methods' => $this->availablePaymentMethods($tutorProfile)->map(fn ($paymentMethod) => [
                'id' => $paymentMethod->id,
                'name' => $paymentMethod->name,
            ])->values(),
            'paypal_ready' => $tutorProfile->paypalReady(),
            'availabilities' => $tutorProfile->availabilities->map(fn ($availability) => [
                'id' => $availability->id,
                'weekday' => $availability->weekday,
                'start_time' => $availability->start_time,
                'end_time' => $availability->end_time,
                'mode' => $availability->mode,
                'is_available' => (bool) $availability->is_available,
            ])->values(),
        ];
    }

    private function formatMessage(Message $message, int $currentUserId): array
    {
        return [
            'id' => $message->id,
            'sender_id' => $message->sender_id,
            'receiver_id' => $message->receiver_id,
            'booking_id' => $message->booking_id,
            'message' => $message->message,
            'created_at' => $message->created_at,
            'is_mine' => $message->sender_id === $currentUserId,
            'sender' => $message->sender,
            'receiver' => $message->receiver,
            'booking' => $message->booking
                ? $this->formatBookingForChat($message->booking)
                : null,
        ];
    }

    private function formatBookingForChat(Booking $booking): array
    {
        $booking->loadMissing([
            'student:id,name,vorname,nachname,email',
            'tutorProfile.user:id,name,vorname,nachname,email',
            'subject:id,name',
            'paymentMethod:id,name',
        ]);

        return [
            'id' => $booking->id,
            'student_id' => $booking->student_id,
            'student_name' => $this->displayName($booking->student),
            'tutor_profile_id' => $booking->tutor_profile_id,
            'tutor_user_id' => $booking->tutorProfile?->user_id,
            'tutor_name' => $this->displayName($booking->tutorProfile?->user),
            'subject_id' => $booking->subject_id,
            'subject_name' => $booking->subject?->name,
            'payment_method_id' => $booking->payment_method_id,
            'payment_method_name' => $booking->paymentMethod?->name,
            'requested_date' => $booking->requested_date
                ? Carbon::parse($booking->requested_date)->toDateString()
                : null,
            'start_time' => $this->shortTime($booking->start_time),
            'end_time' => $this->shortTime($booking->end_time),
            'mode' => $booking->mode,
            'mode_label' => $this->modeLabel($booking->mode),
            'request_type' => $booking->request_type,
            'request_type_label' => $this->requestTypeLabel($booking->request_type),
            'exchange_offer' => $booking->exchange_offer,
            'message' => $booking->message,
            'status' => $booking->status,
            'status_label' => $this->statusLabel($booking->status),
            'payment_amount' => $booking->payment_amount,
            'payment_currency' => $booking->payment_currency,
            'payment_status' => $booking->payment_status,
        ];
    }

    private function availablePaymentMethods(TutorProfile $tutorProfile)
    {
        return $tutorProfile->paymentMethods
            ->filter(function ($paymentMethod) use ($tutorProfile) {
                $isPayPal = str_contains(strtolower((string) $paymentMethod->name), 'paypal');

                return ! $isPayPal || $tutorProfile->paypalReady();
            })
            ->values();
    }

    private function displayName($user): string
    {
        if (! $user) {
            return 'Unbekannt';
        }

        return $user->name ?: trim(($user->vorname ?? '') . ' ' . ($user->nachname ?? '')) ?: 'Unbekannt';
    }

    private function shortTime($value): string
    {
        if (! $value) {
            return '';
        }

        return substr((string) $value, 0, 5);
    }

    private function modeLabel(?string $mode): string
    {
        return match ($mode) {
            'online' => 'Online',
            'presence', 'in_person' => 'Vor Ort',
            'both' => 'Online & vor Ort',
            default => 'Flexibel',
        };
    }

    private function requestTypeLabel(?string $requestType): string
    {
        return match ($requestType) {
            'paid' => 'Bezahlte Nachhilfe',
            'exchange' => 'Tauschleistung',
            'free' => 'Kostenlos',
            default => 'Anfrage',
        };
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            'open' => 'Offen',
            'accepted' => 'Angenommen',
            'declined', 'rejected' => 'Abgelehnt',
            'cancelled' => 'Abgesagt',
            'completed' => 'Abgeschlossen',
            default => $status ?: 'Offen',
        };
    }
}
