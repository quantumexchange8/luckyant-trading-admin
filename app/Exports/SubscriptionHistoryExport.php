<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Payment;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class SubscriptionHistoryExport implements FromCollection, WithHeadings
{
    private $historySubscriber;

    public function __construct($historySubscriber)
    {
        $this->query = $historySubscriber;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection(): \Illuminate\Support\Collection
    {
        $records = $this->query->get();
        $result = array();
        foreach($records as $subscriptions){
            
            $result[] = array(
                'date' => Carbon::parse($subscriptions->created_at)->format('Y-m-d'),
                'name' => $subscriptions->user->name,
                'email' => $subscriptions->user->email,
                'trading_account' => $subscriptions->meta_login,
                'first_leader' => User::where('id', User::find($subscriptions->user_id)->top_leader_id)->value('username') ?? 'LuckyAnt Trading',
                'master' => $subscriptions->master->tradingUser->name ?? 'LuckyAnt Trading',
                'master_trading_account' => $subscriptions->master->meta_login,
                'subscription_id' => $subscriptions->subscription_number,
                'subscription_period' => $subscriptions->subscription_period,
                'copy_trade_balance' => $subscriptions->meta_balance,
                'approval_date' =>  Carbon::parse($subscriptions->approval_date)->format('Y-m-d'),
                'expired_date' =>  Carbon::parse($subscriptions->expired_date)->format('Y-m-d'),
                'termination_date' =>  Carbon::parse($subscriptions->termination_date)->format('Y-m-d'),
                'remarks' =>  $subscriptions->remarks,
                'status' => $subscriptions->status,
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
            'Master Trading Account',
            'Subscription Number',
            'Subscription Period',
            'Copy Trade Balance',
            'Approval Date',
            'Expired Date',
            'Termination Date',
            'Remarks',
            'Status',
        ];
    }
}
