<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PendingSubscriberExport implements FromCollection, WithHeadings
{
    private $query;
    public function __construct($query)
    {
        $this->query = $query;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection(): \Illuminate\Support\Collection
    {
        $records = $this->query->get();
        $result = array();
        foreach($records as $record){
            $first_leader = $record->user->getFirstLeader()->name ?? $record->user->top_leader->name ?? '-';

            $result[] = array(
                'date' => Carbon::parse($record->created_at)->format('Y-m-d H:i:s'),
                'name' => $record->user->name,
                'email' => $record->user->email,
                'trading_account' => $record->meta_login,
                'first_leader' => $first_leader,
                'master' => $record->master->tradingUser->name,
                'master_trading_account' => $record->master_meta_login,
                'copy_trade_balance' => $record->initial_meta_balance,
                'status' => $record->status,
            );
        }

        return collect($result);
    }

    public function headings(): array
    {
        return [
            'Date',
            'Name',
            'Email',
            'Trading Account',
            'First Leader',
            'Master',
            'Master Account',
            'Copy Trade Balance',
            'Status'
        ];
    }
}
