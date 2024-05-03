<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PendingRenewalExport implements FromCollection, WithHeadings
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
                'first_leader' => $first_leader,
                'trading_account' => $record->subscription->meta_login,
                'master' => $record->subscription->master->tradingUser->name ?? '-',
                'master_trading_account' => $record->subscription->master->meta_login,
                'subscription_number' => $record->subscription->subscription_number,
                'amount' => $record->subscription->meta_balance,
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
            'Status'
        ];
    }
}
