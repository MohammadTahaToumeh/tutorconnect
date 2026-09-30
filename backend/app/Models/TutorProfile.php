<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TutorProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'description',
        'price_per_hour',
        'teaching_mode',
        'is_active',
        'paypal_merchant_id',
        'paypal_tracking_id',
        'paypal_referral_id',
        'paypal_onboarding_status',
        'paypal_payments_receivable',
        'paypal_email_confirmed',
        'paypal_connected_at',
    ];

    protected $hidden = [
        'paypal_merchant_id',
        'paypal_tracking_id',
        'paypal_referral_id',
    ];

    protected function casts(): array
    {
        return [
            'price_per_hour' => 'decimal:2',
            'is_active' => 'boolean',
            'paypal_payments_receivable' => 'boolean',
            'paypal_email_confirmed' => 'boolean',
            'paypal_connected_at' => 'datetime',
        ];
    }


    public function paypalReady(): bool
    {
        return filled(config('services.paypal.client_id'))
            && filled(config('services.paypal.client_secret'));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(
            Subject::class,
            'tutor_profile_subjects'
        )->withTimestamps();
    }


    public function helpSubjects(): BelongsToMany
    {
        return $this->belongsToMany(
            Subject::class,
            'tutor_profile_help_subjects'
        )->withTimestamps();
    }

    public function paymentMethods(): BelongsToMany
    {
        return $this->belongsToMany(
            PaymentMethod::class,
            'tutor_profile_payment_method'
        )->withTimestamps();
    }

    public function locations(): HasMany
    {
        return $this->hasMany(TutorLocation::class);
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(TutorAvailability::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function completedBookings(): HasMany
    {
        return $this->hasMany(Booking::class)
            ->where('status', 'completed');
    }
}