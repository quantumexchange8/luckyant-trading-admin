<?php

namespace App\Services\Data;

use App\Models\TradingUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateTradingUser
{
    public function execute(User $user, $group, $data): TradingUser
    {
        return $this->storeNewUser($user, $group, $data);
    }

    public function storeNewUser(User $user, $group, $data): TradingUser
    {
        $tradingUser = new TradingUser();
        $tradingUser->user_id = $user->id;
        $tradingUser->name = $data['name'];
        $tradingUser->meta_login = $data['login'];
        $tradingUser->meta_group = $group->value;
        $tradingUser->account_type = $group->id;
        $tradingUser->leverage = $data['leverage'];

        DB::transaction(function () use ($tradingUser) {
            $tradingUser->save();
        });

        return $tradingUser;
    }
}
