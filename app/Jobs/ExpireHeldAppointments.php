<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpireHeldAppointments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $expiredCount = 0;

        Appointment::query()
            ->expiredHolds()
            ->chunkById(100, function ($appointments) use (&$expiredCount) {
                foreach ($appointments as $appointment) {
                    try {
                        DB::transaction(function () use ($appointment) {
                            $appointment->lockForUpdate();
                            
                            // Double-check it's still expired and held
                            if ($appointment->status !== AppointmentStatus::Held) {
                                return;
                            }
                            
                            if (!$appointment->isHoldExpired()) {
                                return;
                            }

                            $appointment->update([
                                'status' => AppointmentStatus::CancelledBySystem,
                                'cancellation_reason' => 'Hold expired without payment',
                                'cancelled_at' => CarbonImmutable::now(),
                            ]);
                        });

                        $expiredCount++;
                        
                        Log::info('Expired held appointment', [
                            'appointment_id' => $appointment->id,
                        ]);
                    } catch (\Throwable $e) {
                        Log::error('Failed to expire appointment', [
                            'appointment_id' => $appointment->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        Log::info('Expired held appointments job completed', [
            'expired_count' => $expiredCount,
        ]);
    }
}
