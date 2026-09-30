<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudyProgram;
use App\Models\Subject;
use App\Models\TutorProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;

        $tutors = TutorProfile::query()
            ->with([
                'user:id,name,vorname,nachname',
            ])
            ->where('is_active', true)
            ->when($userId, function ($query) use ($userId) {
                $query->where('user_id', '!=', $userId);
            })
            ->latest()
            ->limit(2)
            ->get();

        $subjects = Subject::query()
            ->with('studyProgram:id,name')
            ->limit(3)
            ->get();

        return response()->json([
            'statistics' => [
                'study_programs' => StudyProgram::count(),
                'subjects' => Subject::count(),
                'tutors' => TutorProfile::where('is_active', true)->count(),
            ],
            'subjects' => $subjects,
            'tutors' => $tutors,
        ]);
    }
}