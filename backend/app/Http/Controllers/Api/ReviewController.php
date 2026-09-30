<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\TutorProfile;
use App\Services\BookingLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReviewController extends Controller
{
    public function __construct(
        private readonly BookingLifecycleService $bookingLifecycle
    ) {
    }

    /**
     * Reviews received by the current user's own tutor profile.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $tutorProfile = $user->tutorProfile;

        if (! $tutorProfile) {
            return response()->json($this->emptyPayload());
        }

        return response()->json($this->reviewPayload($tutorProfile));
    }

    /**
     * Public review overview for a tutor profile.
     * Inactive profiles stay private unless the owner opens the profile.
     */
    public function forTutor(Request $request, TutorProfile $tutorProfile): JsonResponse
    {
        $isOwner = (int) $tutorProfile->user_id === (int) $request->user()->id;

        if (! $tutorProfile->is_active && ! $isOwner) {
            return response()->json([
                'message' => 'Tutorprofil wurde nicht gefunden.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json($this->reviewPayload($tutorProfile));
    }

    /**
     * Completed bookings of the current user (as student) that are
     * still waiting for a review. Used to drive the automatic prompt.
     */
    public function pending(Request $request): JsonResponse
    {
        $this->bookingLifecycle->completeExpiredAcceptedBookings();

        $user = $request->user();

        $bookings = Booking::with([
            'tutorProfile.user:id,name,vorname,nachname',
            'subject:id,name',
        ])
            ->where('student_id', $user->id)
            ->where('status', 'completed')
            ->whereDoesntHave('review')
            ->orderByDesc('updated_at')
            ->get();

        return response()->json($bookings);
    }

    private function reviewPayload(TutorProfile $tutorProfile): array
    {
        $tutorProfile->loadMissing('user:id,name,vorname,nachname');

        $reviews = $tutorProfile->reviews()
            ->with([
                'student:id,name,vorname,nachname',
                'booking:id,subject_id,requested_date,start_time,end_time,mode',
                'booking.subject:id,name',
            ])
            ->latest()
            ->get();

        $count = $reviews->count();
        $average = $count > 0
            ? round((float) $reviews->avg('rating'), 1)
            : null;

        $distribution = [];

        foreach (range(1, 5) as $star) {
            $distribution[$star] = $reviews->where('rating', $star)->count();
        }

        return [
            'tutor_profile_id' => $tutorProfile->id,
            'tutor_name' => $this->displayName($tutorProfile->user),
            'average' => $average,
            'count' => $count,
            'distribution' => $distribution,
            'reviews' => $reviews->map(function ($review) {
                return [
                    'id' => $review->id,
                    'rating' => (int) $review->rating,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at,
                    'student' => $review->student
                        ? [
                            'id' => $review->student->id,
                            'name' => $this->displayName($review->student),
                            'vorname' => $review->student->vorname,
                            'nachname' => $review->student->nachname,
                        ]
                        : null,
                    'booking' => $review->booking
                        ? [
                            'id' => $review->booking->id,
                            'requested_date' => $review->booking->requested_date,
                            'start_time' => $review->booking->start_time,
                            'end_time' => $review->booking->end_time,
                            'mode' => $review->booking->mode,
                            'subject' => $review->booking->subject
                                ? [
                                    'id' => $review->booking->subject->id,
                                    'name' => $review->booking->subject->name,
                                ]
                                : null,
                        ]
                        : null,
                ];
            })->values(),
        ];
    }

    private function emptyPayload(): array
    {
        return [
            'tutor_profile_id' => null,
            'tutor_name' => '',
            'average' => null,
            'count' => 0,
            'distribution' => [
                1 => 0,
                2 => 0,
                3 => 0,
                4 => 0,
                5 => 0,
            ],
            'reviews' => [],
        ];
    }

    private function displayName($user): string
    {
        if (! $user) {
            return 'Unbekannt';
        }

        return $user->name
            ?: trim(($user->vorname ?? '') . ' ' . ($user->nachname ?? ''))
            ?: 'Unbekannt';
    }
}
