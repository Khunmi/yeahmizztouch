<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="color: #1a1a1a; margin: 0;">{{ config('salon.name') }}</h1>
    </div>

    <div style="background: #fef2f2; border: 1px solid #fca5a5; border-radius: 8px; padding: 20px; margin-bottom: 24px;">
        <h2 style="color: #991b1b; margin: 0 0 8px 0; font-size: 20px;">Appointment Cancelled</h2>
        <p style="margin: 0; color: #991b1b;">The following appointment has been cancelled.</p>
    </div>

    <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 24px; margin-bottom: 24px;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px 0; color: #6b7280;">Service</td>
                <td style="padding: 8px 0; text-align: right;">{{ $appointment->service->name }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6b7280;">Date</td>
                <td style="padding: 8px 0; text-align: right;">{{ $appointment->formatted_date }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6b7280;">Time</td>
                <td style="padding: 8px 0; text-align: right;">{{ $appointment->formatted_start_time }}</td>
            </tr>
        </table>
    </div>

    @if($appointment->cancellation_reason)
    <p style="color: #6b7280; font-size: 14px;">
        <strong>Reason:</strong> {{ $appointment->cancellation_reason }}
    </p>
    @endif

    <p style="color: #6b7280; font-size: 14px; text-align: center;">
        Would you like to book a new appointment? Visit our website to schedule.
    </p>

    <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 24px 0;">

    <p style="color: #9ca3af; font-size: 12px; text-align: center;">
        {{ config('salon.name') }}
    </p>
</body>
</html>
