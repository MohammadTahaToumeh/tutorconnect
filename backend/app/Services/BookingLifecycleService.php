<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Message;
use Carbon\Carbon;

class BookingLifecycleService
{
    public const TIMEZONE = 'Europe/Berlin';

    public function completeExpiredAcceptedBookings(): int
    {
        $now = Carbon::now(self::TIMEZONE);

        $bookings = Booking::with([
            'student:id,name,vorname,nachname,email',
            'tutorProfile.user:id,name,vorname,nachname,email',
            'subject:id,name',
        ])
            ->where('status', 'accepted')
            ->whereNotNull('requested_date')
            ->whereNotNull('end_time')
            ->whereDate('requested_date', '<=', $now->toDateString())
            ->get();

        $completed = 0;

        foreach ($bookings as $booking) {
            $endsAt = $this->appointmentEndsAt($booking);

            if (! $endsAt || $endsAt->gt($now)) {
                continue;
            }

            $updated = Booking::query()
                ->whereKey($booking->id)
                ->where('status', 'accepted')
                ->update(['status' => 'completed']);

            if ($updated === 0) {
                continue;
            }

            $booking->refresh()->load([
                'student:id,name,vorname,nachname,email',
                'tutorProfile.user:id,name,vorname,nachname,email',
                'subject:id,name',
            ]);

            $this->createCompletionChatMessage($booking);
            $completed++;
        }

        return $completed;
    }

    public function hasAppointmentStarted(Booking $booking): bool
    {
        $startsAt = $this->appointmentStartsAt($booking);

        return $startsAt
            ? $startsAt->lte(Carbon::now(self::TIMEZONE))
            : false;
    }

    public function appointmentStartsAt(Booking $booking): ?Carbon
    {
        return $this->appointmentDateTime($booking, $booking->start_time);
    }

    public function appointmentEndsAt(Booking $booking): ?Carbon
    {
        return $this->appointmentDateTime($booking, $booking->end_time);
    }

    private function appointmentDateTime(Booking $booking, $time): ?Carbon
    {
        if (! $booking->requested_date || ! $time) {
            return null;
        }

        $date = Carbon::parse($booking->requested_date, self::TIMEZONE)->toDateString();

        return Carbon::parse($date . ' ' . (string) $time, self::TIMEZONE);
    }

    private function createCompletionChatMessage(Booking $booking): void
    {
        $tutorUser = $booking->tutorProfile?->user;
        $student = $booking->student;

        if (! $tutorUser || ! $student) {
            return;
        }

        $subject = $booking->subject?->name ?? 'Fach nicht angegeben';
        $date = Carbon::parse($booking->requested_date, self::TIMEZONE)->format('d.m.Y');

        Message::create([
            'sender_id' => $tutorUser->id,
            'receiver_id' => $student->id,
            'booking_id' => $booking->id,
            'message' => "🎓 Termin beendet: {$subject} am {$date}. Du kannst den Termin jetzt bewerten.",
        ]);
    }
}
