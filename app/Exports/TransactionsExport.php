<?php

namespace App\Exports;

use App\Models\Country;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WalletLog;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TransactionsExport implements FromCollection, WithHeadings
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
        $records->each(function ($transaction) use ($leaders) {
            $userService = new UserService();
            $firstLeader = $userService->getFirstLeader($transaction->user?->hierarchyList, $leaders);

            $transaction->first_leader_name = $firstLeader?->name;
        });

        $result = array();
        foreach($records as $record){
            $country = Country::find($record->user->country);

            if ($record->transaction_type == 'Transfer') {
                $from = $record->from_wallet ? $record->from_wallet->user->name : '-';
                $to = $record->to_wallet ? $record->to_wallet->user->name : '-';
            } else {
                $from = $record->from_wallet ? $record->from_wallet->name : ($record->from_meta_login ?? $record->to_meta_login ?? '-');
                $to = $record->to_wallet ? $record->to_wallet->name : ($record->to_meta_login ?? $record->from_meta_login ?? '-');
            }

            $result[] = array(
                'name' => $record->user->name,
                'email' => $record->user->email,
                'first_leader' => $record->first_leader_name,
                'country' => $country->name,
                'type' => $record->transaction_type,
                'fund_type' => $record->fund_type,
                'from' => $from,
                'to' => $record->transaction_type == 'Withdrawal' ? $record->to_wallet_address : $to,
                'transaction_id' => $record->transaction_number,
                'txn_hash' => $record->txn_hash,
                'to_wallet_address' => $record->to_wallet_address,
                'payment_method' => $record->payment_method,
                'payment_platform_name' => $record->payment_account->payment_platform_name ?? '',
                'bank_sub_branch' => $record->payment_account->bank_sub_branch ?? '',
                'payment_account_name' => $record->setting_payment->payment_account_name ?? '',
                'payment_account_no' => $record->setting_payment->account_no ?? '',
                'date' => Carbon::parse($record->created_at)->format('Y-m-d'),
                'amount' =>  number_format((float)$record->amount, 2, '.', ''),
                'payment_charges' =>  number_format((float)$record->transaction_charges, 2, '.', ''),
                'transaction_amount' =>  number_format((float)$record->transaction_amount, 2, '.', ''),
                'conversion_amount' =>  number_format((float)$record->conversion_amount, 2, '.', ''),
                'profit' =>  number_format((float)$record->profitAmount, 2, '.', ''),
                'bonus' =>  number_format((float)$record->bonusAmount, 2, '.', ''),
                'status' => $record->status,
                'approval_at' => !empty($record->approval_at) ? $record->approval_at : null,
                'remarks' => $record->remarks,
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
            'Country',
            'Transaction Type',
            'Fund Type',
            'From',
            'To',
            'Transaction ID',
            'Transaction Hash',
            'To Wallet Address',
            'Payment Method',
            'Bank / Crypto',
            'Bank Branch',
            'Full Name',
            'Payment Account No',
            'Date',
            'Amount',
            'Payment Charges',
            'Transaction Amount',
            'Conversion Amount',
            'Profit',
            'Bonus',
            'Status',
            'Approval Date',
            'Remarks',
        ];
    }
}
