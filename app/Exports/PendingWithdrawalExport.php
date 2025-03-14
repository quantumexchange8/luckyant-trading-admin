<?php

namespace App\Exports;

use App\Models\User;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PendingWithdrawalExport implements FromCollection, WithHeadings
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
        foreach($records as $row){
            $active_copy_trade_fund = User::find($row->user_id)->active_copy_trade()->sum('meta_balance');

            $active_pamm_fund = User::find($row->user_id)->active_pamm()->sum('subscription_amount');

            $result[] = array(
                'name' => $row->user->name,
                'email' => $row->user->email,
                'first_leader' => $row->first_leader_name,
                'category' => $row->category,
                'from' => $row->from_wallet->name,
                'type' => $row->transaction_type,
                'fund_type' => $row->fund_type,
                'transaction_id' => $row->transaction_number,
                'txn_hash' => $row->txn_hash,
                'to_wallet_address' => $row->to_wallet_address,
                'payment_method' => $row->payment_method,
                'payment_platform_name' => $row->payment_account?->payment_platform_name,
                'bank_region' => $row->payment_account?->bank_region,
                'bank_sub_branch' => $row->payment_account?->bank_sub_branch,
                'payment_account_name' => $row->payment_account?->payment_account_name,
                'account_number' => $row->payment_account?->account_no,
                'date' => Carbon::parse($row->created_at)->format('Y-m-d'),
                'amount' =>  number_format((float)$row->amount, 2, '.', ''),
                'transaction_charges' =>  number_format((float)$row->transaction_charges, 2, '.', ''),
                'transaction_amount' =>  number_format((float)$row->transaction_amount, 2, '.', ''),
                'conversion_amount' =>  number_format((float)$row->conversion_amount, 2, '.', ''),
                'profit' =>  number_format((float)$row->profitAmount, 2, '.', ''),
                'bonus' =>  number_format((float)$row->bonusAmount, 2, '.', ''),
                'active_fund' =>  $active_copy_trade_fund + $active_pamm_fund,
                'status' => $row->status,
                'remarks' => $row->remarks,
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
            'Category',
            'From',
            'Transaction Type',
            'Fund Type',
            'Transaction ID',
            'Transaction Hash',
            'To Wallet Address',
            'Payment Method',
            'Bank / Crypto',
            'Region of Bank',
            'Bank Branch',
            'Full Name',
            'Account',
            'Date',
            'Amount',
            'Payment Charges',
            'Transaction Amount',
            'Conversion Amount',
            'Profit',
            'Bonus',
            'Active Fund',
            'Status',
            'Remarks',
        ];
    }
}
