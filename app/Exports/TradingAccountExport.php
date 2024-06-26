<?php

namespace App\Exports;

use App\Models\Subscription;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TradingAccountExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */

    private $tradingListing;

    public function __construct($tradingListing)
    {
        $this->tradingListing = $tradingListing;
    }

    public function collection()
    {
        $records = $this->tradingListing->get();
        $result = array();
        foreach($records as $tradings){
            $subscription_amount = Subscription::where('meta_login', $tradings->meta_login)
                ->where('status', 'Active')
                ->sum('meta_balance');

            $result[] = array(
                'trading_account' => $tradings->meta_login,
                'trading_user_name' => $tradings->tradingUser->name,
                'balance' => $tradings->balance,
                'demo_fund' => $tradings->demo_fund ?? 0,
                'subscription_amount' => $subscription_amount ?? 0,
                'margin_leverage' => $tradings->margin_leverage,
                'equity' => $tradings->equity,
                'user' => $tradings->user->name,
                'email' => $tradings->user->email,
            );
        }

        return collect($result);
    }

    public function headings(): array
    {
        return [
            'Trading Account',
            'Trading User Name',
            'Balance',
            'Demo Fund',
            'Subscription Amount',
            'Margin Level',
            'Equity',
            'User Name',
            'User Email',
        ];
    }
}
