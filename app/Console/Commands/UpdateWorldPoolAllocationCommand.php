<?php

namespace App\Console\Commands;

use App\Models\PammSubscription;
use App\Models\Subscription;
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

        if (!$today_pool) {
            $today_pool = WorldPoolAllocation::create([
                'allocation_date' => today(),
                'allocation_amount' => 0,
            ]);
        }

        $last_pool_amount = WorldPoolAllocation::whereDate('allocation_date', '<', today())
            ->latest()
            ->first()
            ->allocation_amount;

        $second_last_pool_amount = (float) $today_pool->allocation_amount ?? 0;

        $active_subscriptions_capital = Subscription::where('status', 'Active')
            ->sum('meta_balance');

        $active_pamm_capital = PammSubscription::with('master:id,involves_world_pool')
            ->where('status', 'Active')
            ->whereHas('master', function ($q) {
                $q->where('involves_world_pool', 1);
            })
            ->sum('subscription_amount');

        $total_extra_amount = (float) $last_pool_amount - ($second_last_pool_amount);

        $world_pool = ($active_pamm_capital + $active_subscriptions_capital + $total_extra_amount) / 10000 * 0.4;

        $today_pool->update([
            'world_pool_amount' => $world_pool,
        ]);
    }
}
