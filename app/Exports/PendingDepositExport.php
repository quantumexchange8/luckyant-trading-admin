<?php

namespace App\Exports;

use App\Models\Payment;
use App\Models\User;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PendingDepositExport implements FromCollection, WithHeadings
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
        foreach($records as $deposits){

            $result[] = array(
                'name' => $deposits->user->name,
                'email' => $deposits->user->email,
                'first_leader' => $row->first_leader_name,
                'category' => $deposits->category,
                'asset' => $deposits->to_wallet->name,
                'type' => $deposits->transaction_type,
                'fund_type' => $deposits->fund_type,
                'transaction_id' => $deposits->transaction_number,
                'txn_hash' => $deposits->txn_hash,
                'to_wallet_address' => $deposits->to_wallet_address,
                'payment_method' => $deposits->payment_method,
                'payment_account_name' => $deposits->setting_payment->payment_account_name ?? '',
                'account_number' => $deposits->setting_payment->account_no ?? '',
                'date' => Carbon::parse($deposits->created_at)->format('Y-m-d'),
                'amount' =>  number_format((float)$deposits->amount, 2, '.', ''),
                'status' => $deposits->status,
                'remarks' => $deposits->remarks,
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
            'Asset',
            'Transaction Type',
            'Fund Type',
            'Transaction ID',
            'Transaction Hash',
            'To Wallet Address',
            'Payment Method',
            'Payment Account Name',
            'Account',
            'Date',
            'Amount',
            'Status',
            'Remarks',
        ];
    }
}
