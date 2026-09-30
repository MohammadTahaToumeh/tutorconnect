<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Models\StudyProgram;
use App\Models\Subject;
use App\Models\TutorProfile;
use App\Services\PayPalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ProfileController extends Controller
{
    public function __construct(
        private readonly PayPalService $paypal
    ) {
    }

    public function options(Request $request): JsonResponse
    {
        $tutorProfile = $this->loadTutorProfile($request->user()->id);

        return response()->json([
            'user' => $request->user(),
            'tutorProfile' => $tutorProfile,
            'paypal' => $this->paypalState($tutorProfile),
            'studyPrograms' => StudyProgram::orderBy('name')->get(),
            'subjects' => Subject::with('studyProgram')->orderBy('name')->get(),
            'paymentMethods' => PaymentMethod::orderBy('name')->get(),
            'weekdays' => $this->weekdays(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (TutorProfile::where('user_id', $request->user()->id)->exists()) {
            return response()->json([
                'message' => 'Für diesen Benutzer existiert bereits ein Tutor-Profil.',
            ], Response::HTTP_CONFLICT);
        }

        $profile = $this->persistProfile($request);

        return response()->json([
            'message' => 'Profil erfolgreich erstellt.',
            'tutorProfile' => $profile,
            'paypal' => $this->paypalState($profile),
        ], Response::HTTP_CREATED);
    }

    public function update(Request $request): JsonResponse
    {
        if (!TutorProfile::where('user_id', $request->user()->id)->exists()) {
            return response()->json([
                'message' => 'Es wurde noch kein Tutor-Profil erstellt.',
            ], Response::HTTP_NOT_FOUND);
        }

        $profile = $this->persistProfile($request);

        return response()->json([
            'message' => 'Profil erfolgreich gespeichert.',
            'tutorProfile' => $profile,
            'paypal' => $this->paypalState($profile),
        ]);
    }

    private function persistProfile(Request $request): TutorProfile
    {
        $validated = $request->validate([
            'description' => ['nullable', 'string', 'max:2000'],
            'price_per_hour' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'teaching_mode' => ['required', 'in:online,in_person,both'],
            'subjects' => ['nullable', 'array'],
            'subjects.*' => ['integer', 'exists:subjects,id'],
            'help_subjects' => ['nullable', 'array'],
            'help_subjects.*' => ['integer', 'exists:subjects,id'],
            'payment_methods' => ['nullable', 'array'],
            'payment_methods.*' => ['integer', 'exists:payment_methods,id'],
            'locations' => ['nullable', 'array'],
            'locations.*' => ['nullable', 'string', 'max:255'],
            'availability' => ['nullable', 'array'],
            'availability.*.active' => ['nullable', 'boolean'],
            'availability.*.start_time' => ['nullable', 'date_format:H:i'],
            'availability.*.end_time' => ['nullable', 'date_format:H:i'],
        ]);

        $userId = $request->user()->id;

        $subjectIds = $this->cleanIds($validated['subjects'] ?? []);
        $paymentMethodIds = $this->cleanIds($validated['payment_methods'] ?? []);
        $helpSubjectIds = $this->cleanIds($validated['help_subjects'] ?? []);

        $hasExchangePayment = PaymentMethod::whereIn('id', $paymentMethodIds)
            ->whereRaw('LOWER(name) LIKE ?', ['%tausch%'])
            ->exists();

        if ($hasExchangePayment && count($helpSubjectIds) === 0) {
            throw ValidationException::withMessages([
                'help_subjects' => 'Bitte wähle mindestens ein Fach aus, in dem du selbst Hilfe brauchst, wenn du Tauschleistung anbietest.',
            ]);
        }

        DB::transaction(function () use ($validated, $userId, $subjectIds, $paymentMethodIds, $helpSubjectIds, $hasExchangePayment): void {
            $profile = TutorProfile::updateOrCreate(
                ['user_id' => $userId],
                [
                    'description' => $validated['description'] ?? null,
                    'price_per_hour' => $validated['price_per_hour'],
                    'teaching_mode' => $validated['teaching_mode'],
                    'is_active' => true,
                ]
            );

            $profile->subjects()->sync($subjectIds);
            $profile->paymentMethods()->sync($paymentMethodIds);

            $profile->helpSubjects()->sync($hasExchangePayment ? $helpSubjectIds : []);

            $profile->locations()->delete();

            foreach ($validated['locations'] ?? [] as $location) {
                $location = trim((string) $location);

                if ($location !== '') {
                    $profile->locations()->create([
                        'location' => $location,
                    ]);
                }
            }

            $profile->availabilities()->delete();

            foreach (array_keys($this->weekdays()) as $day) {
                $dayData = $validated['availability'][$day] ?? null;

                if (!$dayData || empty($dayData['active'])) {
                    continue;
                }

                $profile->availabilities()->create([
                    'weekday' => $day,
                    'start_time' => $dayData['start_time'] ?? '08:00',
                    'end_time' => $dayData['end_time'] ?? '18:00',
                    'mode' => $validated['teaching_mode'],
                    'is_available' => true,
                ]);
            }
        });

        return $this->loadTutorProfile($userId);
    }

    private function loadTutorProfile(int $userId): ?TutorProfile
    {
        return TutorProfile::with([
            'user',
            'subjects.studyProgram',
            'helpSubjects.studyProgram',
            'paymentMethods',
            'locations',
            'availabilities',
            'reviews.student:id,name,vorname,nachname',
            'reviews.booking.subject:id,name',
            'completedBookings.subject',
            'completedBookings.student',
        ])->where('user_id', $userId)->first();
    }


    private function paypalState(?TutorProfile $tutorProfile): array
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

    private function cleanIds(array $ids): array
    {
        return array_values(array_unique(array_filter(
            array_map(fn ($id) => (int) $id, $ids),
            fn ($id) => $id > 0
        )));
    }

    private function weekdays(): array
    {
        return [
            'monday' => 'Montag',
            'tuesday' => 'Dienstag',
            'wednesday' => 'Mittwoch',
            'thursday' => 'Donnerstag',
            'friday' => 'Freitag',
        ];
    }
}
