<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ExpireHeldAppointments;
use Illuminate\Console\Command;

class ExpireHoldsCommand extends Command
{
    protected $signature = 'appointments:expire-holds';
    protected $description = 'Expire held appointments that have passed their hold_expires_at time';

    public function handle(): int
    {
        $this->info('Dispatching ExpireHeldAppointments job...');
        
        ExpireHeldAppointments::dispatch();
        
        $this->info('Job dispatched successfully.');
        
        return Command::SUCCESS;
    }
}
