<?php

namespace App\Exports;

use App\Models\Payment;
use Carbon\Carbon;
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
     * @return \Illuminate\Support\Collection
     */
    public function collection(): \Illuminate\Support\Collection
    {
        $records = $this->query->get();
        $result = array();
        foreach($records as $deposits){
            
            $result[] = array(
                'name' => $deposits->user->name,
                'email' => $deposits->user->email,
                'category' => $deposits->category,
                'asset' => $deposits->to_wallet->name,
                'type' => $deposits->transaction_type,
                'fund_type' => $deposits->fund_type,
                'transaction_id' => $deposits->transaction_number,
                'txn_hash' => $deposits->txn_hash,
                'to_wallet_address' => $deposits->to_wallet_address,
                'from_meta_login' => $deposits->from_meta_login,
                'to_meta_login' => $deposits->to_meta_login,
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
            'Category',
            'asset',
            'Transaction Type',
            'Fund Type',
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
