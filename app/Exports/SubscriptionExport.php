<?php

namespace App\Exports;

use App\Models\User;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
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
     * @return Collection
     */
    public function collection(): Collection
    {
        $records = $this->query
            ->orderByDesc('approval_date')
            ->get();

        $userHierarchyLists = $records->pluck('user.hierarchyList')
            ->filter()
            ->flatMap(fn($list) => explode('-', trim($list, '-')))
            ->unique()
            ->toArray();

        $leaders = User::whereIn('id', $userHierarchyLists)
            ->where('leader_status', 1)
            ->get()
            ->keyBy('id');

        // Attach the first leader details
        $records->each(function ($subscriptionQuery) use ($leaders) {
            $userService = new UserService();
            $firstLeader = $userService->getFirstLeader($subscriptionQuery->user?->hierarchyList, $leaders);

            $subscriptionQuery->first_leader_name = $firstLeader?->name;
        });

        $result = array();
        foreach($records as $record){
            $result[] = array(
                'approval_date' => Carbon::parse($record->approval_date)->format('Y-m-d H:i:s'),
                'name' => $record->user->name ?? '-',
                'email' => $record->user->email ?? '-',
                'first_leader' => $record->first_leader_name,
                'trading_account' => $record->meta_login,
                'master' => $record->master->tradingUser->name ?? '-',
                'master_trading_account' => $record->master_meta_login,
                'account_type' => $record->master->tradingUser->from_account_type->name ?? '-',
                'subscription_number' => $record->subscription_number,
                'amount' => $record->meta_balance,
                'real_fund' => $record->real_fund,
                'demo_fund' => $record->demo_fund,
                'settlement_start_date' => $record->subscription
                    ? Carbon::parse($record->subscription->approval_date)->format('Y-m-d')
                    : null,
                'settlement_end_date' => $record->subscription
                    ? Carbon::parse($record->subscription->expired_date)->addDay()->format('Y-m-d')
                    : null,
                'termination_date' => $record->termination_date ? Carbon::parse($record->termination_date)->format('Y-m-d') : null,
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
            'Account Type',
            'Subscription Number',
            'Amount',
            'Real Fund',
            'Demo Fund',
            'Settlement Start Date',
            'Settlement End Date',
            'Termination Date',
            'Status'
        ];
    }
}
