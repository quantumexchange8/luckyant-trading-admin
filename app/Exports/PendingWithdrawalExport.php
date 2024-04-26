<?php

namespace App\Exports;

use App\Models\Payment;
use Carbon\Carbon;
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
     * @return \Illuminate\Support\Collection
     */
    public function collection(): \Illuminate\Support\Collection
    {
        $records = $this->query->get();
        $result = array();
        foreach($records as $row){

            $result[] = array(
                'name' => $row->user->name,
                'email' => $row->user->email,
                'first_leader' => $row->user->getFirstLeader()->name ?? '-',
                'category' => $row->category,
                'asset' => $row->from_wallet->name,
                'type' => $row->transaction_type,
                'fund_type' => $row->fund_type,
                'transaction_id' => $row->transaction_number,
                'txn_hash' => $row->txn_hash,
                'to_wallet_address' => $row->to_wallet_address,
                'payment_method' => $row->payment_method,
                'payment_account_name' => $row->payment_account->payment_account_name ?? '',
                'account_number' => $row->payment_account->account_no ?? '',
                'date' => Carbon::parse($row->created_at)->format('Y-m-d'),
                'amount' =>  number_format((float)$row->amount, 2, '.', ''),
                'transaction_charges' =>  number_format((float)$row->transaction_charges, 2, '.', ''),
                'transaction_amount' =>  number_format((float)$row->transaction_amount, 2, '.', ''),
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
            'Payment Charges',
            'Withdrawal Amount',
            'Status',
            'Remarks',
        ];
    }
}
