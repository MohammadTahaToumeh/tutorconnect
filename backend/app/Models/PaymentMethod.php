<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function tutorProfiles(): BelongsToMany
    {
        return $this->belongsToMany(
            TutorProfile::class,
            'tutor_profile_payment_method'
        )->withTimestamps();
    }
}