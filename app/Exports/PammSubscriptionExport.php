<?php

namespace App\Exports;

use App\Models\User;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PammSubscriptionExport implements FromCollection, WithHeadings
{
    private $query;

    public function g__construct($query)
    {
        $this->query = $query;
    }
    /**
    * @return Collection
    */
    public function collection(): Collection
    {
        $records = $this->query->select([
            'id',
            'user_id',
            'meta_login',
            'subscription_number',
            'subscription_amount',
            'master_id',
            'master_meta_login',
            'type',
            'strategy_type',
            'approval_date',
            'termination_date',
            'created_at',
            'status',
        ])
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
                'request_date' => Carbon::parse($record->created_at)->format('Y-m-d H:i:s'),
                'name' => $record->user->name,
                'email' => $record->user->email,
                'first_leader' => $record->first_leader_name,
                'trading_account' => $record->meta_login ?? '-',
                'master' => $record->master->tradingUser->name ?? '-',
                'type' => strtoupper($record->master->type),
                'strategy_type' => $record->strategy_type,
                'master_trading_account' => $record->master_meta_login,
                'subscription_number' => $record->subscription_number,
                'subscription_package' => $record->package->amount ?? null,
                'subscription_product' => $record->subscription_package_product,
                'fund_size' => $record->subscription_amount,
                'status' => $record->status,
                'approval_date' => $record->approval_date ? Carbon::parse($record->approval_date)->format('Y-m-d H:i:s') : '',
                'termination_date' => $record->termination_date ? Carbon::parse($record->termination_date)->format('Y-m-d H:i:s') : '',
            );
        }

        return collect($result);
    }

    public function headings(): array
    {
        return [
            'Request Date',
            'Name',
            'Email',
            'First Leader',
            'Live Account',
            'Master',
            'Type',
            'Strategy',
            'Master Account',
            'Subscription Number',
            'Subscription Package',
            'Subscription Product',
            'Fund Size',
            'Status',
            'Approval Date',
            'Termination Date',
        ];
    }
}
