<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'study_program_id',
        'name',
        'description',
    ];

    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class);
    }

    public function tutorProfiles(): BelongsToMany
    {
        return $this->belongsToMany(
            TutorProfile::class,
            'tutor_profile_subjects'
        )->withTimestamps();
    }


    public function helpTutorProfiles(): BelongsToMany
    {
        return $this->belongsToMany(
            TutorProfile::class,
            'tutor_profile_help_subjects'
        )->withTimestamps();
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}