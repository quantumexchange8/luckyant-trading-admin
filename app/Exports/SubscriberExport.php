<?php

namespace App\Exports;

use App\Models\Subscriber;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SubscriberExport implements FromCollection, WithHeadings, ShouldQueue
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
            $first_leader = $record->user->getFirstLeader()->name ?? $record->user->top_leader->name ?? 'LuckyAnt Trading';

            $result[] = array(
                'date' => Carbon::parse($record->created_at)->format('Y-m-d H:i:s'),
                'name' => $record->user->name,
                'email' => $record->user->email,
                'trading_account' => $record->meta_login,
                'first_leader' => $first_leader,
                'master' => $record->master->tradingUser->name ?? 'LuckyAnt Trading',
                'master_trading_account' => $record->master_meta_login,
                'copy_trade_balance' => $record->subscription->meta_balance,
                'approval_date' =>  Carbon::parse($record->approval_date)->format('Y-m-d'),
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
            'Approval Date',
            'Status'
        ];
    }
}
