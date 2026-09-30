<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RegistrationController extends Controller
{
    private const CODE_TTL_MINUTES = 10;

    public function options(): JsonResponse
    {
        return response()->json([
            'study_programs' => StudyProgram::query()
                ->orderBy('name')
                ->get(['id', 'name']),
            'semesters' => range(1, 14),
            'roles' => [
                'Nachhilfe bekommen',
                'Nachhilfe anbieten',
                'Beides',
            ],
        ]);
    }

    public function sendCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vorname' => ['required', 'string', 'max:255'],
            'nachname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'studiengang' => ['required', 'string', 'max:255'],
            'semester' => ['required', 'string', 'max:50'],
            'rolle' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $email = strtolower(trim($validated['email']));

        if (!str_ends_with($email, '@stud.hs-bochum.de')) {
            throw ValidationException::withMessages([
                'email' => 'Bitte verwenden Sie Ihre studentische Hochschul-E-Mail der Hochschule Bochum.',
            ]);
        }

        if (User::where('email', $email)->exists()) {
            return response()->json([
                'message' => 'Diese E-Mail ist bereits registriert. Bitte melden Sie sich an.',
            ], Response::HTTP_CONFLICT);
        }

        $code = (string) random_int(100000, 999999);
        $cacheKey = $this->cacheKey($email);

        Cache::put($cacheKey, [
            'code_hash' => Hash::make($code),
            'registration' => [
                'vorname' => $validated['vorname'],
                'nachname' => $validated['nachname'],
                'email' => $email,
                'studiengang' => $validated['studiengang'],
                'semester' => $validated['semester'],
                'rolle' => $validated['rolle'],
                'password_hash' => Hash::make($validated['password']),
            ],
        ], now()->addMinutes(self::CODE_TTL_MINUTES));

        try {
            Mail::raw(
                "Ihr Bestätigungscode für TutorConnect lautet: {$code}",
                function ($message) use ($email): void {
                    $message
                        ->to($email)
                        ->subject('TutorConnect Bestätigungscode');
                }
            );
        } catch (Throwable $exception) {
            Cache::forget($cacheKey);
            report($exception);

            return response()->json([
                'message' => 'Der Bestätigungscode konnte nicht gesendet werden.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'message' => 'Der Bestätigungscode wurde gesendet.',
            'email' => $email,
        ]);
    }

    public function verifyCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
        ]);

        $email = strtolower(trim($validated['email']));
        $cacheKey = $this->cacheKey($email);
        $pending = Cache::get($cacheKey);

        if (!$pending) {
            return response()->json([
                'message' => 'Der Bestätigungscode ist abgelaufen. Bitte registrieren Sie sich erneut.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!Hash::check((string) $validated['code'], $pending['code_hash'])) {
            return response()->json([
                'message' => 'Der eingegebene Code ist falsch.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (User::where('email', $email)->exists()) {
            Cache::forget($cacheKey);

            return response()->json([
                'message' => 'Diese E-Mail ist bereits registriert. Bitte melden Sie sich an.',
            ], Response::HTTP_CONFLICT);
        }

        $registration = $pending['registration'];

        $user = DB::transaction(function () use ($registration): User {
            $user = new User();
            $user->vorname = $registration['vorname'];
            $user->nachname = $registration['nachname'];
            $user->name = trim($registration['vorname'].' '.$registration['nachname']);
            $user->email = $registration['email'];
            $user->studiengang = $registration['studiengang'];
            $user->semester = $registration['semester'];
            $user->rolle = $registration['rolle'];
            $user->password = $registration['password_hash'];
            $user->email_verified_at = now();
            $user->save();

            return $user;
        });

        Cache::forget($cacheKey);

        return response()->json([
            'user' => $user,
            'token' => $user->createToken('tutorconnect')->plainTextToken,
        ], Response::HTTP_CREATED);
    }

    private function cacheKey(string $email): string
    {
        return 'tutorconnect_registration:'.sha1($email);
    }
}
