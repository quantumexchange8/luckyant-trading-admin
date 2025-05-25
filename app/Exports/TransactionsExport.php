<?php

namespace App\Exports;

use App\Models\User;
use App\Services\UserService;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TransactionsExport implements FromQuery, WithHeadings, WithMapping, WithChunkReading
{
    private $query;
    public $timeout = null;

    public function __construct($query)
    {
        $this->query = $query;

        ini_set('memory_limit', '512M');
    }

    public function query()
    {
        return $this->query;
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
            'Wallet Address / Account Number',
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

    public function map($row): array
    {
        $userService = new UserService();
        $hierarchyList = $row->user?->hierarchyList;
        $leaders = User::whereIn('id', collect(explode('-', trim($hierarchyList, '-'))))
            ->where('leader_status', 1)
            ->get()
            ->keyBy('id');

        $firstLeader = $userService->getFirstLeader($hierarchyList, $leaders);

        if ($row->transaction_type == 'Transfer') {
            $from = $row->from_wallet ? $row->from_wallet->user->name : '-';
            $to = $row->to_wallet ? $row->to_wallet->user->name : '-';
        } else {
            $from = $row->from_wallet ? $row->from_wallet->name : ($row->from_meta_login ?? $row->to_meta_login ?? '-');
            $to = $row->to_wallet ? $row->to_wallet->name : ($row->to_meta_login ?? $row->from_meta_login ?? '-');
        }

        // map and return your columns here
        return [
            $row->user->name,
            $row->user->email,
            $firstLeader?->name,
            $row->user?->ofCountry?->name ?? '-',
            $row->transaction_type,
            $row->fund_type,
            $from,
            $row->transaction_type == 'Withdrawal' ? $row->to_wallet_address : $to,
            $row->transaction_number,
            $row->txn_hash,
            $row->payment_method == 'Bank' ? $row->to_wallet_address : "'".$row->to_wallet_address,
            $row->payment_method,
            $row->payment_account_name,
            $row->payment_account->payment_platform_name ?? '',
            $row->payment_account->bank_sub_branch ?? '',
            $row->setting_payment->payment_account_name ?? '',
            $row->setting_payment->account_no ?? '',
            number_format((float)$row->amount, 2, '.', ''),
            number_format((float)$row->transaction_charges, 2, '.', ''),
            number_format((float)$row->transaction_amount, 2, '.', ''),
            number_format((float)$row->conversion_amount, 2, '.', ''),
            number_format((float)$row->profitAmount, 2, '.', ''),
            number_format((float)$row->bonusAmount, 2, '.', ''),
            $row->status,
            !empty($row->approval_at) ? $row->approval_at : null,
            $row->remarks,
        ];
    }

    public function chunkSize(): int
    {
        return 500; // Adjust based on server memory
    }
}
