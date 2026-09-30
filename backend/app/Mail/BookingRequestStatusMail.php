<?php

namespace App\Mail;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Mail\Mailable;

class BookingRequestStatusMail extends Mailable
{
    public function __construct(
        public Booking $booking,
        public string $status,
        public string $chatUrl
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
            ->subject($this->subjectLine())
            ->view('emails.booking-request-status')
            ->with([
                'booking' => $this->booking,
                'chatUrl' => $this->chatUrl,
                'status' => $this->status,
                'statusLabel' => $this->statusLabel($this->status),
                'statusTitle' => $this->statusTitle($this->status),
                'statusIntro' => $this->statusIntro($this->status),
                'studentName' => $this->displayName($this->booking->student),
                'tutorName' => $this->displayName($this->booking->tutorProfile?->user),
                'subjectName' => $this->booking->subject?->name ?? 'Fach nicht angegeben',
                'paymentMethodName' => $this->booking->paymentMethod?->name ?? 'Keine Auswahl',
                'dateLabel' => $this->dateLabel($this->booking->requested_date),
                'startTimeLabel' => $this->timeLabel($this->booking->start_time),
                'endTimeLabel' => $this->timeLabel($this->booking->end_time),
                'modeLabel' => $this->modeLabel($this->booking->mode),
                'requestTypeLabel' => $this->requestTypeLabel($this->booking->request_type),
            ]);
    }

    private function subjectLine(): string
    {
        return match ($this->status) {
            'accepted' => 'Deine Nachhilfe-Anfrage wurde angenommen',
            'cancelled' => 'Dein Nachhilfe-Termin wurde abgesagt',
            default => 'Deine Nachhilfe-Anfrage wurde abgelehnt',
        };
    }

    private function statusTitle(string $status): string
    {
        return match ($status) {
            'accepted' => 'Anfrage angenommen',
            'cancelled' => 'Termin abgesagt',
            default => 'Anfrage abgelehnt',
        };
    }

    private function statusIntro(string $status): string
    {
        return match ($status) {
            'accepted' => 'Deine Nachhilfe-Anfrage wurde vom Tutor angenommen. Du kannst die Details im Chat sehen.',
            'cancelled' => 'Der bereits angenommene Nachhilfe-Termin wurde vom Tutor abgesagt. Die Änderung wurde auch im Chat gespeichert.',
            default => 'Deine Nachhilfe-Anfrage wurde vom Tutor abgelehnt. Du kannst im Chat weiterschreiben oder eine andere Anfrage stellen.',
        };
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'accepted' => 'Angenommen',
            'cancelled' => 'Abgesagt',
            default => 'Abgelehnt',
        };
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
}
