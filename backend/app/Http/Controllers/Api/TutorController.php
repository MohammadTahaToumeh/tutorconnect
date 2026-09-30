<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TutorProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TutorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tutors = TutorProfile::with([
            'user:id,name,vorname,nachname',
            'subjects:id,study_program_id,name',
            'subjects.studyProgram:id,name',
            'helpSubjects:id,study_program_id,name',
            'helpSubjects.studyProgram:id,name',
            'paymentMethods:id,name',
            'locations:id,tutor_profile_id,location',
            'availabilities:id,tutor_profile_id,weekday,start_time,end_time,mode,is_available',
            'reviews:id,tutor_profile_id,rating',
        ])
            ->where('is_active', true)
            ->where('user_id', '!=', $request->user()->id)
            ->orderBy('id')
            ->get()
            ->each(fn (TutorProfile $tutorProfile) => $this->hideUnavailablePayPal($tutorProfile));

        return response()->json($tutors);
    }

    public function show(TutorProfile $tutorProfile): JsonResponse
    {
        if (! $tutorProfile->is_active) {
            return response()->json([
                'message' => 'Tutorprofil wurde nicht gefunden.',
            ], Response::HTTP_NOT_FOUND);
        }

        $tutorProfile->load([
            'user:id,name,vorname,nachname,email,studiengang,semester',
            'subjects:id,study_program_id,name',
            'subjects.studyProgram:id,name',
            'helpSubjects:id,study_program_id,name',
            'helpSubjects.studyProgram:id,name',
            'paymentMethods:id,name',
            'locations:id,tutor_profile_id,location',
            'availabilities:id,tutor_profile_id,weekday,start_time,end_time,mode,is_available',
            'reviews:id,tutor_profile_id,student_id,rating,comment,created_at',
            'reviews.student:id,name,vorname,nachname',
            'completedBookings:id,tutor_profile_id,status',
        ]);

        $this->hideUnavailablePayPal($tutorProfile);

        return response()->json($tutorProfile);
    }

    private function hideUnavailablePayPal(TutorProfile $tutorProfile): void
    {
        if (! $tutorProfile->relationLoaded('paymentMethods')) {
            return;
        }

        $tutorProfile->setRelation(
            'paymentMethods',
            $tutorProfile->paymentMethods
                ->filter(function ($paymentMethod) use ($tutorProfile) {
                    $isPayPal = str_contains(strtolower((string) $paymentMethod->name), 'paypal');

                    return ! $isPayPal || $tutorProfile->paypalReady();
                })
                ->values()
        );
    }
}
