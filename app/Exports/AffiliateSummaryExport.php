<?php

namespace App\Exports;

use App\Models\User;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AffiliateSummaryExport implements FromCollection, WithHeadings
{
    private $summaries;

    public function __construct($summaries)
    {
        $this->summaries = $summaries;
    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection(): \Illuminate\Support\Collection
    {
        $records = $this->summaries->get();
        $result = [];

        foreach ($records as $record) {
            $bonusWalletTransactions = $this->calculateWalletTransactions($record, 'bonus_wallet');
            $eWalletTransactions = $this->calculateWalletTransactions($record, 'e_wallet');

            $result[] = [
                'name' => $record->name,
                'email' => $record->email,
                'created_at' => Carbon::parse($record->created_at)->format('Y-m-d'),
                'first_leader' => $record->getFirstLeader()->name ?? '',
                'profit' => $record->walletLogs->where('category', 'profit')->sum('amount') ?? 0,
                'bonus_in' => $bonusWalletTransactions['in'] ?? 0,
                'bonus_out' => $bonusWalletTransactions['out'] ?? 0,
                'e_wallet_in' => $eWalletTransactions['in'] ?? 0,
                'e_wallet_out' => $eWalletTransactions['out'] ?? 0,
                'total_funding' => $record->transactions->where('category', 'wallet')->where('transaction_type', 'Deposit')->where('status', 'Success')->sum('transaction_amount') ?? 0,
                'total_withdrawal' => $record->transactions->where('category', 'wallet')->where('transaction_type', 'Withdrawal')->where('status', 'Success')->sum('transaction_amount') ?? 0,
                'total_demo_fund' => $record->transactions->where('category', 'trading_account')->where('transaction_type', 'Deposit')->where('fund_type', 'DemoFund')->where('status', 'Success')->sum('transaction_amount') ?? 0,
            ];
        }

        return collect($result);
    }

    public function headings(): array
    {
        return [
            'Name',
            'Email',
            'Joining Date',
            'First Leader',
            'Profit In',
            'Bonus Wallet In',
            'Bonus Wallet Out',
            'E-Wallet In',
            'E-Wallet Out',
            'Total Funding',
            'Total Withdrawal',
            'Total Demo Fund',
        ];
    }

    protected function calculateWalletTransactions($user, $walletType): array
    {
        $wallet = $user->wallets->where('type', $walletType)->first();

        $walletIn = $user->walletLogs->where('category', 'bonus')->where('wallet_type', $walletType)->sum('amount') +
            $user->transactions->where('category', 'wallet')->where('to_wallet_id', optional($wallet)->id)->sum('transaction_amount');

        $walletOut = $user->transactions->where('category', 'wallet')->where('from_wallet_id', optional($wallet)->id)->sum('transaction_amount');

        return [
            'in' => $walletIn,
            'out' => $walletOut,
        ];
    }
}
