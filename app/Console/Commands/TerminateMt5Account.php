<?php

namespace App\Console\Commands;
use App\Models\TradingAccount;
use App\Models\TradingUser;
use App\Models\TradePammInvestorAllocate;
use App\Models\Mt5DeleteLog;
use App\Services\MetaFiveService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Console\Command;

class TerminateMt5Account extends Command
{
    protected $signature = 'terminate:meta-login';

    protected $description = 'Terminate MT5 Accounts that are inactive and have 0 balance after 3 weeks';

    public function handle()
    {
        $metaService = new MetaFiveService();
        $connection = $metaService->getConnectionStatus();
        if ($connection != 0) {
            Log::error('Error, Unable to connect to MT5. ');
        }
        else{
            $end_date = Carbon::now()->format('Y-m-d H:i:s');
            $start_date = Carbon::now()->subWeeks(3)->format('Y-m-d H:i:s');

            $tradingList = TradingAccount::where('created_at', '<', $start_date)
                ->whereHas('tradingUser', function ($query) {
                    $query->where('acc_status', 'Active');
                })
                ->pluck('id')
                ->toArray();
            foreach ($tradingList as $tradingId)
            {
                $tradingAcc = TradingAccount::find($tradingId);
                $tradingUser = $tradingAcc->tradingUser;

                $metaAccountData = $metaService->getMetaAccount($tradingAcc->meta_login);
                $accBalance = $metaAccountData['balance'];
                if ($accBalance != 0){
                    continue;
                }

                $shouldContinue = false; 
                $metaDealData = $metaService->dealHistory($tradingAcc->meta_login, $start_date, $end_date);
                foreach ($metaDealData as $metaDeal) {
                    if (is_array($metaDeal) && !empty($metaDeal)) {
                        $shouldContinue = true;
                        break;
                    }
                }
                if ($shouldContinue) {
                    Log::debug("Detected deal.");
                    continue; 
                }

                $pammData = TradePammInvestorAllocate::where('meta_login', $tradingAcc->meta_login)
                ->whereBetween('created_at', [$start_date, $end_date])
                ->exists();
                if ($pammData) {
                    continue; 
                }

                $tradingUser->update([
                    'remarks' => 'Inactive Account',
                    'acc_status' => 'Deleted',
                ]);

                Mt5DeleteLog::create([
                    'user_id' => $tradingAcc->user_id,
                    'trading_account_id' => $tradingAcc->id,
                    'meta_login' => $tradingAcc->meta_login,
                    'type' => 'auto',
                    'account_created_at' => $tradingAcc->created_at,
                    'account_balance' => $accBalance,
                    'remarks' => 'Inactive account',
                ]);
                $metaService->deleteAccount($tradingAcc->meta_login);
            }
        }
    }
}
