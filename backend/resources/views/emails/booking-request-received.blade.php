<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Neue Nachhilfe-Anfrage</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#111827;">
    <div style="max-width:640px;margin:0 auto;padding:28px 14px;">
        <div style="background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid #e5e7eb;box-shadow:0 14px 35px rgba(15,23,42,0.08);">
            <div style="background:#0f172a;color:#ffffff;padding:24px 28px;">
                <div style="font-size:13px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#fbbf24;">TutorConnect</div>
                <h1 style="margin:8px 0 0;font-size:24px;line-height:1.25;">Neue Nachhilfe-Anfrage</h1>
                <p style="margin:10px 0 0;color:#d1d5db;font-size:15px;line-height:1.5;">{{ $studentName }} möchte eine Session bei dir anfragen.</p>
            </div>

            <div style="padding:26px 28px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
                    <tr>
                        <td style="padding:10px 0;color:#6b7280;font-size:13px;width:38%;">Anfragender</td>
                        <td style="padding:10px 0;font-weight:700;font-size:15px;">{{ $studentName }}</td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0;color:#6b7280;font-size:13px;border-top:1px solid #f3f4f6;">Tutor</td>
                        <td style="padding:10px 0;font-weight:700;font-size:15px;border-top:1px solid #f3f4f6;">{{ $tutorName }}</td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0;color:#6b7280;font-size:13px;border-top:1px solid #f3f4f6;">Fach</td>
                        <td style="padding:10px 0;font-weight:700;font-size:15px;border-top:1px solid #f3f4f6;">{{ $subjectName }}</td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0;color:#6b7280;font-size:13px;border-top:1px solid #f3f4f6;">Termin</td>
                        <td style="padding:10px 0;font-weight:700;font-size:15px;border-top:1px solid #f3f4f6;">{{ $dateLabel }} · {{ $startTimeLabel }}–{{ $endTimeLabel }} Uhr</td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0;color:#6b7280;font-size:13px;border-top:1px solid #f3f4f6;">Modus</td>
                        <td style="padding:10px 0;font-weight:700;font-size:15px;border-top:1px solid #f3f4f6;">{{ $modeLabel }}</td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0;color:#6b7280;font-size:13px;border-top:1px solid #f3f4f6;">Art</td>
                        <td style="padding:10px 0;font-weight:700;font-size:15px;border-top:1px solid #f3f4f6;">{{ $requestTypeLabel }}</td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0;color:#6b7280;font-size:13px;border-top:1px solid #f3f4f6;">Zahlung / Tausch</td>
                        <td style="padding:10px 0;font-weight:700;font-size:15px;border-top:1px solid #f3f4f6;">{{ $paymentMethodName }}</td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0;color:#6b7280;font-size:13px;border-top:1px solid #f3f4f6;">Status</td>
                        <td style="padding:10px 0;font-weight:700;font-size:15px;border-top:1px solid #f3f4f6;">{{ $statusLabel }}</td>
                    </tr>
                </table>

                @if(!empty($booking->message))
                    <div style="margin-top:18px;padding:16px;border-radius:14px;background:#f9fafb;border:1px solid #e5e7eb;">
                        <div style="font-size:13px;color:#6b7280;font-weight:700;margin-bottom:6px;">Nachricht / Thema</div>
                        <div style="font-size:15px;line-height:1.55;color:#111827;">{{ $booking->message }}</div>
                    </div>
                @endif

                @if(!empty($booking->exchange_offer))
                    <div style="margin-top:14px;padding:16px;border-radius:14px;background:#fff7ed;border:1px solid #fed7aa;">
                        <div style="font-size:13px;color:#9a3412;font-weight:700;margin-bottom:6px;">Tauschvorschlag</div>
                        <div style="font-size:15px;line-height:1.55;color:#111827;">{{ $booking->exchange_offer }}</div>
                    </div>
                @endif

                <div style="text-align:center;margin-top:28px;">
                    <a href="{{ $dashboardUrl }}" style="display:inline-block;background:#f59e0b;color:#111827;text-decoration:none;font-weight:800;padding:13px 22px;border-radius:999px;font-size:15px;">Im Tutor-Dashboard verwalten</a>
                </div>

                <p style="margin:22px 0 0;color:#6b7280;font-size:13px;line-height:1.5;text-align:center;">Du kannst die Anfrage im Tutor-Dashboard annehmen, ablehnen oder später weiter bearbeiten.</p>
            </div>
        </div>
    </div>
</body>
</html>
