<?php

namespace App\Exports;

use App\Models\Country;
use App\Models\PammSubscription;
use App\Models\SubscriptionBatch;
use App\Models\User;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MemberListingExport implements FromCollection, WithHeadings
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

        $userHierarchyLists = $records->pluck('hierarchyList')
            ->filter()
            ->flatMap(fn($list) => explode('-', trim($list, '-')))
            ->unique()
            ->toArray();

        $leaders = User::whereIn('id', $userHierarchyLists)
            ->where('leader_status', 1)
            ->get()
            ->keyBy('id');

        // Attach the first leader details
        $records->each(function ($user) use ($leaders) {
            $userService = new UserService();
            $firstLeader = $userService->getFirstLeader($user?->hierarchyList, $leaders);

            $user->first_leader_name = $firstLeader?->name;
        });

        $result = [];

        foreach ($records as $record) {
            // Check if $record is an array and has the necessary properties
            $real_fund = SubscriptionBatch::where('user_id', $record->id)
                ->where('status', 'Active')
                ->sum('real_fund');

            $demo_fund = SubscriptionBatch::where('user_id', $record->id)
                ->where('status', 'Active')
                ->sum('demo_fund');

            $total_group_deposit = SubscriptionBatch::whereIn('user_id', $record->getChildrenIds())
                ->where('status', 'Active')
                ->sum('meta_balance');

            $total_group_pamm_subscription = PammSubscription::whereIn('user_id', $record->getChildrenIds())
                ->where('status', 'Active')
                ->sum('subscription_amount');

            $result[] = [
                'name' => $record->name,
                'email' => $record->email,
                'phone' => $record->phone,
                'created_at' => Carbon::parse($record->created_at)->format('Y-m-d'),
                'first_leader' => $record->first_leader_name,
                'upline_email' => $record->upline->email ?? '',
                'cash_wallet_balance' => $record->wallets->where('type', 'cash_wallet')->first()->balance ?? 0,
                'bonus_wallet_balance' => $record->wallets->where('type', 'bonus_wallet')->first()->balance ?? 0,
                'e_wallet_balance' => $record->wallets->where('type', 'e_wallet')->first()->balance ?? 0,
                'country' => $record->ofCountry->name ?? '',
                'rank' => $record->rank->name,
                'kyc_approval' => $record->kyc_approval,
                'total_deposit' => $record->active_pamm_sum_subscription_amount + $record->active_copy_trade_sum_meta_balance,
                'real_fund' => $real_fund,
                'demo_fund' => $demo_fund,
                'total_group_deposit' => $total_group_deposit + $total_group_pamm_subscription,
            ];
        }

        return collect($result);
    }

    public function headings(): array
    {
        return [
            'Name',
            'Email',
            'Contact Number',
            'Joining Date',
            'First Leader',
            'Upline Email',
            'Cash Balance',
            'Bonus Balance',
            'eWallet Balance',
            'Country',
            'Ranking',
            'Status',
            'Total Deposit',
            'Real Fund',
            'Demo Fund',
            'Total Group Deposit',
        ];
    }
}
