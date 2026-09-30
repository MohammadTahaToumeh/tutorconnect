<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $statusTitle }}</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#111827;">
    <div style="max-width:640px;margin:0 auto;padding:28px 14px;">
        <div style="background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid #e5e7eb;box-shadow:0 14px 35px rgba(15,23,42,0.08);">
            <div style="background:{{ $status === 'accepted' ? '#0f766e' : ($status === 'cancelled' ? '#9a6700' : '#991b1b') }};color:#ffffff;padding:24px 28px;">
                <div style="font-size:13px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#fef3c7;">TutorConnect</div>
                <h1 style="margin:8px 0 0;font-size:24px;line-height:1.25;">{{ $statusTitle }}</h1>
                <p style="margin:10px 0 0;color:#f3f4f6;font-size:15px;line-height:1.5;">{{ $statusIntro }}</p>
            </div>

            <div style="padding:26px 28px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
                    <tr>
                        <td style="padding:10px 0;color:#6b7280;font-size:13px;width:38%;">Status</td>
                        <td style="padding:10px 0;font-weight:700;font-size:15px;">{{ $statusLabel }}</td>
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
                </table>

                @if(!empty($booking->message))
                    <div style="margin-top:18px;padding:16px;border-radius:14px;background:#f9fafb;border:1px solid #e5e7eb;">
                        <div style="font-size:13px;color:#6b7280;font-weight:700;margin-bottom:6px;">Deine Nachricht / Thema</div>
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
                    <a href="{{ $chatUrl }}" style="display:inline-block;background:#f59e0b;color:#111827;text-decoration:none;font-weight:800;padding:13px 22px;border-radius:999px;font-size:15px;">Zum Chat</a>
                </div>

                <p style="margin:22px 0 0;color:#6b7280;font-size:13px;line-height:1.5;text-align:center;">Die Änderung wurde auch im Chat zwischen dir und dem Tutor gespeichert.</p>
            </div>
        </div>
    </div>
</body>
</html>
