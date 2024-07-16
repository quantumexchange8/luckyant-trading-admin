<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PammSubscriptionExport implements FromCollection, WithHeadings
{
    private $query;

    public function __construct($query)
    {
        $this->query = $query;
    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
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
                'trading_account' => $record->meta_login ?? '-',
                'master' => $record->master->tradingUser->name ?? '-',
                'type' => $record->master->type,
                'master_trading_account' => $record->master_meta_login,
                'subscription_number' => $record->subscription_number,
                'subscription_package' => $record->package->amount,
                'subscription_product' => $record->subscription_package_product,
                'fund_size' => $record->subscription_amount,
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
            'Type',
            'Master Account',
            'Subscription Number',
            'Subscription Package',
            'Subscription Product',
            'Fund Size',
            'Status'
        ];
    }
}
