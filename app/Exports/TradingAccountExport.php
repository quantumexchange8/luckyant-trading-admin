<?php

namespace App\Exports;

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
            $result[] = array(
                'trading_account' => $tradings->meta_login,
                'trading_user_name' => $tradings->tradingUser->name,
                'balance' => $tradings->balance,
                'margin_leverage' => $tradings->margin_leverage,
                'equity' => $tradings->equity,
                'user' => $tradings->user->name,
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
            'Margin Level',
            'Equity',
            'User Name',
        ];
    }
}
