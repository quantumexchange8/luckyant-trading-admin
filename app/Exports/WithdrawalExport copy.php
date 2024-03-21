<?php

namespace App\Exports;

use App\Models\Payment;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class WithdrawalExport implements FromCollection, WithHeadings
{
    private $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection(): \Illuminate\Support\Collection
    {
        $records = $this->query->get();
        $result = array();
        foreach($records as $withdrawal){
            $result[] = array(
                'name' => $withdrawal->user->name,
                'email' => $withdrawal->user->email,
                'category' => $withdrawal->category,
                'type' => $withdrawal->transaction_type,
                'transaction_id' => $withdrawal->transaction_number,
                'txn_hash' => $withdrawal->txn_hash,
                'to_wallet_address' => $withdrawal->to_wallet_address,
                'from_meta_login' => $withdrawal->from_meta_login,
                'payment_method' => $withdrawal->payment_method,
                'payment_account_name' => $withdrawal->payment_account->payment_account_name ?? '',
                'account_number' => $withdrawal->payment_account->account_no ?? '',
                'date' => Carbon::parse($withdrawal->created_at)->format('Y-m-d'),
                'amount' =>  number_format((float)$withdrawal->amount, 2, '.', ''),
                'status' => $withdrawal->status,
                'remarks' => $withdrawal->remarks,
            );
        }

        return collect($result);
    }

    public function headings(): array
    {
        return [
            'Name',
            'Email',
            'type',
            'transaction Type',
            'Transaction ID',
            'Transaction Hash',
            'To Wallet Address',
            'From Trading Account',
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
