<?php

namespace App\Console\Commands;

use App\Models\SlotHold;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReleaseExpiredHolds extends Command
{
    protected $signature = 'booking:release-expired-holds';
    protected $description = 'Release expired slot holds to make time slots available again';

    public function handle(): int
    {
        $count = SlotHold::cleanupExpired();

        if ($count > 0) {
            Log::info("Released {$count} expired slot holds");
            $this->info("Released {$count} expired slot holds.");
        }

        return Command::SUCCESS;
    }
}
