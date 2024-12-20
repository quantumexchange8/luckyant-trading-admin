<?php

namespace App\Exports;

use App\Models\Subscription;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TradingAccountExport implements FromCollection, WithHeadings
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
        $records = $this->query->get();

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
        $records->each(function ($account) use ($leaders) {
            $userService = new UserService();
            $firstLeader = $userService->getFirstLeader($account->user?->hierarchyList, $leaders);

            $account->first_leader_name = $firstLeader?->name;
        });

        $result = array();

        foreach($records as $record){
            $result[] = array(
                'user' => $record->user?->name,
                'email' => $record->user?->email,
                'first_leader' => $record->first_leader_name,
                'trading_account' => $record->meta_login,
                'trading_user_name' => $record->tradingUser?->name,
                'group' => $record->accountType?->name,
                'balance' => $record->balance ?? 0,
                'demo_fund' => $record->demo_fund ?? 0,
                'active_copy_trade' => $record->active_copy_trade_sum_meta_balance ?? 0,
                'active_pamm' => $record->active_pamm_sum_subscription_amount ?? 0,
                'margin_leverage' => $record->margin_leverage,
                'equity' => $record->equity,
                'status' => $record->tradingUser->acc_status,
            );
        }

        return collect($result);
    }

    public function headings(): array
    {
        return [
            'Name',
            'Email',
            'First Leader',
            'Trading Account',
            'Trading User Name',
            'Group',
            'Balance',
            'Demo Fund',
            'Active Copy Trading Fund',
            'Active PAMM Fund',
            'Margin Leverage',
            'Equity',
            'Status',
        ];
    }
}
