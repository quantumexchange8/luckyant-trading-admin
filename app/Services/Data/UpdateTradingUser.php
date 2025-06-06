<?php

namespace App\Services\Data;

use App\Models\AccountType;
use App\Models\TradingUser;
use Illuminate\Support\Facades\DB;

class UpdateTradingUser
{
    public function execute($meta_login, $data): TradingUser
    {
        return $this->updateTradingUser($meta_login, $data);
    }

    public function updateTradingUser($meta_login, $data): TradingUser
    {
        $accountType = AccountType::firstWhere('name', $data['group']);

        $tradingUser = TradingUser::with('from_account_type')
            ->firstWhere('meta_login', $meta_login);

        if ($tradingUser->acc_status === "Active" && $tradingUser->from_account_type->slug != 'virtual') {
            $tradingUser->name = $data['name'];
            $tradingUser->company = $data['company'];
            $tradingUser->leverage = $data['leverage'];
            $tradingUser->balance = $data['balance'];
            $tradingUser->credit = $data['credit'];
            $tradingUser->rights = $data['rights'];
            $tradingUser->meta_group = $accountType->name;
            $tradingUser->account_type = $accountType->id;

            if ($data['rights'] == 5) {
                $tradingUser->allow_trade = false;
            } elseif ($data['rights'] == 1) {
                $tradingUser->allow_trade = true;
            }

            DB::transaction(function () use ($tradingUser) {
                $tradingUser->save();
            });
        }

        return $tradingUser;
    }
}
