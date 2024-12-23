<?php

namespace App\Exports;

use App\Models\User;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
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
     * @return Collection
     */
    public function collection(): Collection
    {
        $records = $this->query
            ->orderByDesc('termination_date')
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
                'termination_date' => Carbon::parse($record->termination_date)->format('Y-m-d H:i:s'),
                'name' => $record->user->name,
                'email' => $record->user->email,
                'first_leader' => $record->first_leader_name,
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
