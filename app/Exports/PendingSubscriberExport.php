<?php

namespace App\Exports;

use App\Models\User;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
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
    * @return Collection
    */
    public function collection(): Collection
    {
        $records = $this->query->select([
            'id',
            'user_id',
            'trading_account_id',
            'meta_login',
            'initial_meta_balance',
            'master_id',
            'master_meta_login',
            'created_at',
            'status',
        ])
            ->orderByDesc('created_at')
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
                'date' => Carbon::parse($record->created_at)->format('Y-m-d H:i:s'),
                'name' => $record->user->name,
                'email' => $record->user->email,
                'trading_account' => $record->meta_login,
                'first_leader' => $record->first_leader_name,
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
