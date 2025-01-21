<?php

namespace App\Services\Data;

use App\Models\AccountType;
use App\Models\TradingAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateTradingAccount
{
    public function execute(User $user, $group, $data): TradingAccount
    {
        return $this->storeNewAccount($user, $group, $data);
    }

    public function storeNewAccount(User $user, $group, $data): TradingAccount
    {
        $accountType = AccountType::firstWhere('name', $group);

        $tradingAccount = new TradingAccount();
        $tradingAccount->user_id = $user->id;
        $tradingAccount->meta_login = $data['login'];
        $tradingAccount->account_type = $accountType->id;
        $tradingAccount->margin_leverage = $data['leverage'];

        DB::transaction(function () use ($tradingAccount) {
            $tradingAccount->save();
        });

        return $tradingAccount;
    }
}
