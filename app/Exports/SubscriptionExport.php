<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SubscriptionExport implements FromCollection, WithHeadings
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
                'approval_date' => Carbon::parse($record->created_at)->format('Y-m-d H:i:s'),
                'name' => $record->user->name,
                'email' => $record->user->email,
                'first_leader' => $first_leader,
                'trading_account' => $record->meta_login,
                'master' => $record->master->tradingUser->name ?? '-',
                'master_trading_account' => $record->master_meta_login,
                'subscription_number' => $record->subscription_number,
                'amount' => $record->meta_balance,
                'real_fund' => $record->real_fund,
                'demo_fund' => $record->demo_fund,
                'termination_date' =>  Carbon::parse($record->approval_date)->format('Y-m-d'),
                'status' => $record->status,
            );
        }

        return collect($result);
    }

    public function headings(): array
    {
        return [
            'Approval Date',
            'Name',
            'Email',
            'First Leader',
            'Live Account',
            'Master',
            'Master Account',
            'Subscription Number',
            'Amount',
            'Real Fund',
            'Demo Fund',
            'Termination Date',
            'Status'
        ];
    }
}
