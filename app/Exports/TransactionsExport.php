<?php

namespace App\Exports;

use App\Models\Country;
use App\Models\Transaction;
use App\Models\WalletLog;
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
            $country = Country::find($record->user->country);
            $from = $record->from_wallet ? $record->from_wallet->name : ($record->from_meta_login ?? $record->to_meta_login ?? '-');
            $to = $record->to_wallet ? $record->to_wallet->name : ($record->to_meta_login ?? $record->from_meta_login ?? '-');
            $profit = WalletLog::where('user_id', $record->user_id)->where('category', 'profit')->sum('amount');
            $bonus = WalletLog::where('user_id', $record->user_id)->where('category', 'bonus')->sum('amount');

            $first_leader = $record->user->getFirstLeader()->name ?? $record->user->top_leader->name ?? 'LuckyAnt Trading';

            $result[] = array(
                'name' => $record->user->name,
                'email' => $record->user->email,
                'first_leader' => $first_leader,
                'country' => $country->name,
                'type' => $record->transaction_type,
                'fund_type' => $record->fund_type,
                'from' => $from,
                'to' => $to,
                'transaction_id' => $record->transaction_number,
                'txn_hash' => $record->txn_hash,
                'to_wallet_address' => $record->to_wallet_address,
                'payment_method' => $record->payment_method,
                'payment_account_name' => $record->setting_payment->payment_account_name ?? '',
                'payment_account_no' => $record->setting_payment->account_no ?? '',
                'date' => Carbon::parse($record->created_at)->format('Y-m-d'),
                'amount' =>  number_format((float)$record->amount, 2, '.', ''),
                'payment_charges' =>  number_format((float)$record->transaction_charges, 2, '.', ''),
                'transaction_amount' =>  number_format((float)$record->transaction_amount, 2, '.', ''),
                'profit' =>  number_format((float)$profit, 2, '.', ''),
                'bonus' =>  number_format((float)$bonus, 2, '.', ''),
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
            'Payment Account Name',
            'Payment Account No',
            'Date',
            'Amount',
            'Payment Charges',
            'Transaction Amount',
            'Profit',
            'Bonus',
            'Status',
            'Remarks',
        ];
    }
}
