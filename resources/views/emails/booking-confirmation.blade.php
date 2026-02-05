<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="color: #1a1a1a; margin: 0;">{{ config('salon.name') }}</h1>
    </div>

    <div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 8px; padding: 20px; margin-bottom: 24px;">
        <h2 style="color: #166534; margin: 0 0 8px 0; font-size: 20px;">✓ Booking Confirmed</h2>
        <p style="margin: 0; color: #166534;">Your appointment has been successfully booked.</p>
    </div>

    <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 24px; margin-bottom: 24px;">
        <h3 style="margin: 0 0 16px 0; color: #1a1a1a;">Appointment Details</h3>
        
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px 0; color: #6b7280;">Confirmation #</td>
                <td style="padding: 8px 0; text-align: right; font-family: monospace;">{{ strtoupper(substr($appointment->uuid, 0, 8)) }}</td>
            </tr>
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
            <tr>
                <td style="padding: 8px 0; color: #6b7280;">Duration</td>
                <td style="padding: 8px 0; text-align: right;">{{ $appointment->service->formatted_duration }}</td>
            </tr>
        </table>
    </div>

    <div style="background: #fffbeb; border: 1px solid #fcd34d; border-radius: 8px; padding: 16px; margin-bottom: 24px;">
        <p style="margin: 0; color: #92400e; font-size: 14px;">
            <strong>Cancellation Policy:</strong> Please provide at least {{ config('salon.cancellation_hours') }} hours notice if you need to cancel or reschedule.
        </p>
    </div>

    <p style="color: #6b7280; font-size: 14px; text-align: center;">
        We look forward to seeing you!
    </p>

    <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 24px 0;">

    <p style="color: #9ca3af; font-size: 12px; text-align: center;">
        {{ config('salon.name') }}<br>
        This email was sent to {{ $appointment->client->email }}
    </p>
</body>
</html>
