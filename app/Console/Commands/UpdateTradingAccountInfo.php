<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TradingAccount;
use App\Services\MetaFiveService;

class UpdateTradingAccountInfo extends Command
{
    protected $signature = 'update:trading-account-info';

    protected $description = 'Update trading account info to latest';

    public function handle()
    {
        try {
            (new MetaFiveService())->getUserInfo(TradingAccount::all());
        } catch (\Exception $e) {
            \Log::error('Error fetching trading accounts: '. $e->getMessage());
        }
    }
}
