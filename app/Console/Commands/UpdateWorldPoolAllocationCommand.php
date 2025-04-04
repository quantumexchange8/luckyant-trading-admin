<?php

namespace App\Console\Commands;

use App\Models\WorldPoolAllocation;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateWorldPoolAllocationCommand extends Command
{
    protected $signature = 'update:world-pool-allocation';

    protected $description = 'Update World Pool Allocation';

    public function handle(): void
    {
        $today_pool = WorldPoolAllocation::whereDate('allocation_date', today())->first();

        $last_pool_amount = WorldPoolAllocation::whereDate('allocation_date', Carbon::yesterday())->first();

        if (!$today_pool) {
            WorldPoolAllocation::create([
                'allocation_date' => today(),
                'allocation_amount' => $last_pool_amount->allocation_amount,
            ]);
        }
    }
}
