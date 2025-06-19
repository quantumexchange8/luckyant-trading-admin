<?php

namespace App\Exports;

use App\Models\PammSubscription;
use App\Models\SubscriptionBatch;
use App\Models\User;
use App\Services\UserService;
use Carbon\Carbon;
use DB;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MemberListingExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected $ids;

    public function __construct($ids)
    {
        $this->ids = $ids;
    }

    public function query()
    {
        return User::query()
            ->with([
                'upline:id,name,email',
                'ofCountry:id,name',
                'rank:id,name',
                'wallets',
            ])
            ->withSum('active_pamm', 'subscription_amount')
            ->withSum('active_copy_trade', 'meta_balance')
            ->whereIn('id', $this->ids)
            ->latest();
    }

    public function getFirstLeaderFromHierarchy($hierarchyList): ?array
    {
        // Get the hierarchy IDs
        $ids = array_reverse(array_filter(explode('-', trim($hierarchyList, '-'))));

        // Fetch all leaders once (those with leader_status = 1)
        $leaders = User::whereIn('id', $ids)
            ->where('leader_status', 1)
            ->get()
            ->keyBy('id');

        // Iterate through the hierarchy list to find the first leader
        foreach ($ids as $id) {
            if (isset($leaders[$id])) {
                return [
                    'name' => $leaders[$id]->name,
                    'email' => $leaders[$id]->email,
                ];
            }
        }

        // Return null if no leader is found
        return null;
    }

    public function headings(): array
    {
        return [
            'Name',
            'Email',
            'Username',
            'DOB',
            'Gender',
            'Contact Number',
            'Joining Date',
            'First Leader Name',
            'First Leader Email',
            'Upline Name',
            'Upline Email',
            'Cash Wallet',
            'Bonus Wallet',
            'eWallet',
            'Address',
            'Country',
            'Nationality',
            'IC/Passport Number',
            'Rank',
            'Up Rank Status',
            'Status',
            'Active Fund',
            'Group Active Fund',
        ];
    }

    public function map($user): array
    {
        // Get the hierarchy list and process the leader lookup
        $firstLeader = $this->getFirstLeaderFromHierarchy($user->hierarchyList);

        $total_group_deposit = SubscriptionBatch::whereIn('user_id', $user->getChildrenIds())
            ->where('status', 'Active')
            ->sum('meta_balance');

        $total_group_pamm_subscription = PammSubscription::whereIn('user_id', $user->getChildrenIds())
            ->where('status', 'Active')
            ->sum('subscription_amount');

        return [
            $user->name,
            $user->email,
            $user->username ?? '-',
            $user->dob ?? '-',
            $user->gender ?? '-',
            $user->phone,
            Carbon::parse($user->created_at)->format('Y-m-d'),
            $firstLeader['name'] ?? '-',
            $firstLeader['email'] ?? '-',
            $user->upline?->name,
            $user->upline?->email,
            $user->wallets->where('type', 'cash_wallet')->first()->balance ?? '0.00',
            $user->wallets->where('type', 'bonus_wallet')->first()->balance ?? '0.00',
            $user->wallets->where('type', 'e_wallet')->first()->balance ?? '0.00',
            $user->address_1,
            $user->ofCountry?->name,
            $user->nationality ?? '-',
            "'" . $user->identification_number ?? '-',
            $user->rank?->name,
            $user->rank_up_status,
            $user->kyc_approval,
            $user->active_pamm_sum_subscription_amount + $user->active_copy_trade_sum_meta_balance,
            $total_group_deposit + $total_group_pamm_subscription,
        ];
    }
}
