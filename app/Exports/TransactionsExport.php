<?php

namespace App\Exports;

use App\Models\Transaction;
use Carbon\Carbon;
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
     * @return \Illuminate\Support\Collection
     */
    public function collection(): \Illuminate\Support\Collection
    {
        $records = $this->query->get();
        $result = array();
        foreach($records as $record){
            $result[] = array(
                'name' => $record->user->name,
                'email' => $record->user->email,
                'category' => $record->category,
                'type' => $record->transaction_type,
                'fund_type' => $record->fund_type,
                'transaction_id' => $record->transaction_number,
                'txn_hash' => $record->txn_hash,
                'to_wallet_address' => $record->to_wallet_address,
                'from_meta_login' => $record->from_meta_login,
                'to_meta_login' => $record->to_meta_login,
                'payment_method' => $record->payment_method,
                'payment_account_name' => $record->setting_payment->payment_account_name ?? '',
                'account_number' => $record->setting_payment->account_no ?? '',
                'date' => Carbon::parse($record->created_at)->format('Y-m-d'),
                'amount' =>  number_format((float)$record->amount, 2, '.', ''),
                'status' => $record->status,
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
            'type',
            'Transaction Type',
            'Fund Type',
            'Transaction Type',
            'Transaction ID',
            'Transaction Hash',
            'To Wallet Address',
            'From Trading Account',
            'To Trading Account',
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
