<?php

namespace App\Services;

use App\Models\Appointment;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\BookingConfirmation;
use App\Mail\BookingCancellation;
use App\Mail\AdminNewBookingAlert;

/**
 * NotificationService
 * 
 * Handles all email notifications.
 * Emails are queued for reliability.
 */
class NotificationService
{
    /**
     * Send booking confirmation to client.
     */
    public function sendBookingConfirmation(Appointment $appointment): void
    {
        try {
            Mail::to($appointment->client->email)
                ->queue(new BookingConfirmation($appointment));
            
            Log::info('Booking confirmation email queued', [
                'appointment_id' => $appointment->id,
                'email' => $appointment->client->email
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to queue booking confirmation', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send cancellation notice to client.
     */
    public function sendBookingCancellation(Appointment $appointment): void
    {
        try {
            Mail::to($appointment->client->email)
                ->queue(new BookingCancellation($appointment));
            
            Log::info('Cancellation email queued', [
                'appointment_id' => $appointment->id,
                'email' => $appointment->client->email
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to queue cancellation email', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send new booking alert to admin.
     */
    public function sendAdminNewBookingAlert(Appointment $appointment): void
    {
        $adminEmail = config('salon.admin_email');
        
        if (!$adminEmail) {
            Log::warning('Admin email not configured');
            return;
        }

        try {
            Mail::to($adminEmail)
                ->queue(new AdminNewBookingAlert($appointment));
            
            Log::info('Admin new booking alert queued', [
                'appointment_id' => $appointment->id,
                'admin_email' => $adminEmail
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to queue admin alert', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
