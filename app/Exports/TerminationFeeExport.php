<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TerminationFeeExport implements FromCollection, WithHeadings
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
                'termination_date' => Carbon::parse($record->termination_date)->format('Y-m-d H:i:s'),
                'name' => $record->user->name,
                'email' => $record->user->email,
                'first_leader' => $first_leader,
                'trading_account' => $record->meta_login,
                'master' => $record->subscription->master->tradingUser->name ?? '-',
                'master_trading_account' => $record->master_meta_login,
                'subscription_number' => $record->subscription_number,
                'amount' => $record->subscription_batch_amount,
                'charges' => $record->penalty_amount,
                'return_amount' => $record->return_amount,
                'approval_date' => Carbon::parse($record->approval_date)->format('Y-m-d H:i:s'),
            );
        }

        return collect($result);
    }

    public function headings(): array
    {
        return [
            'Termination Date',
            'Name',
            'Email',
            'First Leader',
            'Live Account',
            'Master',
            'Master Account',
            'Subscription Number',
            'Amount',
            'Management Fee',
            'Return Amount',
            'Approval Date'
        ];
    }
}
