<?php

namespace App\Exports;

use App\Models\Payment;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InternalTransferExport implements FromCollection, WithHeadings
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
                'transfer_from' => $withdrawal->from_wallet->name ?? '',
                'transfer_to' => $withdrawal->to_wallet->name ?? '',
                'type' => $withdrawal->transaction_type,
                'transaction_id' => $withdrawal->transaction_number,
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
            'Transfer From Wallet',
            'Transfer To Wallet',
            'Transaction ID',
            'Date',
            'Amount',
            'Status',
            'Remarks',
        ];
    }
}
