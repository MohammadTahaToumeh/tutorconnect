<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudyProgram;
use Illuminate\Http\JsonResponse;

class StudyProgramController extends Controller
{
    public function index(): JsonResponse
    {
        $studyPrograms = StudyProgram::with([
            'subjects' => function ($query) {
                $query->orderBy('name');
            },
        ])
            ->orderBy('name')
            ->get();

        return response()->json($studyPrograms);
    }
}