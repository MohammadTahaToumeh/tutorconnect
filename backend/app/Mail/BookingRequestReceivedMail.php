<?php

namespace App\Mail;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Mail\Mailable;

class BookingRequestReceivedMail extends Mailable
{
    public function __construct(
        public Booking $booking,
        public string $dashboardUrl
    ) {
    }

    public function build(): self
    {
        $this->booking->loadMissing([
            'student:id,name,vorname,nachname,email',
            'tutorProfile.user:id,name,vorname,nachname,email',
            'subject:id,name',
            'paymentMethod:id,name',
        ]);

        return $this
            ->subject('Neue Nachhilfe-Anfrage auf TutorConnect')
            ->view('emails.booking-request-received')
            ->with([
                'booking' => $this->booking,
                'dashboardUrl' => $this->dashboardUrl,
                'studentName' => $this->displayName($this->booking->student),
                'tutorName' => $this->displayName($this->booking->tutorProfile?->user),
                'subjectName' => $this->booking->subject?->name ?? 'Fach nicht angegeben',
                'paymentMethodName' => $this->booking->paymentMethod?->name ?? 'Keine Auswahl',
                'dateLabel' => $this->dateLabel($this->booking->requested_date),
                'startTimeLabel' => $this->timeLabel($this->booking->start_time),
                'endTimeLabel' => $this->timeLabel($this->booking->end_time),
                'modeLabel' => $this->modeLabel($this->booking->mode),
                'requestTypeLabel' => $this->requestTypeLabel($this->booking->request_type),
                'statusLabel' => $this->statusLabel($this->booking->status),
            ]);
    }

    private function displayName($user): string
    {
        if (! $user) {
            return 'Unbekannt';
        }

        return $user->name ?: trim(($user->vorname ?? '') . ' ' . ($user->nachname ?? '')) ?: 'Unbekannt';
    }

    private function dateLabel($value): string
    {
        if (! $value) {
            return 'Datum nicht angegeben';
        }

        return Carbon::parse($value)->locale('de')->translatedFormat('l, d.m.Y');
    }

    private function timeLabel($value): string
    {
        if (! $value) {
            return '--:--';
        }

        return substr((string) $value, 0, 5);
    }

    private function modeLabel(?string $mode): string
    {
        return match ($mode) {
            'online' => 'Online',
            'presence', 'in_person' => 'Vor Ort',
            'both' => 'Online & vor Ort',
            default => 'Flexibel',
        };
    }

    private function requestTypeLabel(?string $requestType): string
    {
        return match ($requestType) {
            'paid' => 'Bezahlte Nachhilfe',
            'exchange' => 'Tauschleistung',
            'free' => 'Kostenlos',
            default => 'Anfrage',
        };
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            'open' => 'Offen',
            'accepted' => 'Angenommen',
            'declined', 'rejected' => 'Abgelehnt',
            'completed' => 'Abgeschlossen',
            default => $status ?: 'Offen',
        };
    }
}
