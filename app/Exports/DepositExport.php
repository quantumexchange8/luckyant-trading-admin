<?php

namespace App\Exports;

use App\Models\Payment;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DepositExport implements FromCollection, WithHeadings
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
                'transaction_id' => $deposits->transaction_number,
                'txn_hash' => $deposits->txn_hash,
                'to_wallet_address' => $deposits->to_wallet_address,
                'date' => Carbon::parse($deposits->created_at)->format('Y-m-d'),
                'amount' =>  number_format((float)$deposits->amount, 2, '.', ''),
                'status' => $deposits->status,
            );
        }

        return collect($result);
    }

    public function headings(): array
    {
        return [
            'Name',
            'Email',
            'Transaction ID',
            'Transaction Hash',
            'To Wallet Address',
            'Date',
            'Amount',
            'Status',
        ];
    }
}
