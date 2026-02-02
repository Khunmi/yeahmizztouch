<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2 style="color: #1a1a1a; margin: 0 0 20px 0;">New Booking Received</h2>

    <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 24px;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px 0; color: #6b7280; width: 120px;">Client</td>
                <td style="padding: 8px 0;"><strong>{{ $appointment->client->name }}</strong></td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6b7280;">Email</td>
                <td style="padding: 8px 0;">{{ $appointment->client->email }}</td>
            </tr>
            @if($appointment->client->phone)
            <tr>
                <td style="padding: 8px 0; color: #6b7280;">Phone</td>
                <td style="padding: 8px 0;">{{ $appointment->client->phone }}</td>
            </tr>
            @endif
            <tr>
                <td colspan="2" style="padding: 12px 0;"><hr style="border: none; border-top: 1px solid #e5e7eb;"></td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6b7280;">Service</td>
                <td style="padding: 8px 0;">{{ $appointment->service->name }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6b7280;">Date</td>
                <td style="padding: 8px 0;">{{ $appointment->formatted_date }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6b7280;">Time</td>
                <td style="padding: 8px 0;">{{ $appointment->formatted_time }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6b7280;">Paid</td>
                <td style="padding: 8px 0;">{{ $appointment->formatted_total_paid }}</td>
            </tr>
        </table>
    </div>

    <p style="margin-top: 20px; font-size: 14px;">
        <a href="{{ route('admin.appointments.show', $appointment) }}" style="color: #2563eb;">View in Admin Dashboard →</a>
    </p>
</body>
</html>
