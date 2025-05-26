<?php

namespace App\Exports;

use App\Models\Transaction;
use App\Models\User;
use DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TransactionsExport implements FromQuery, WithHeadings, WithMapping, WithChunkReading
{
    use Exportable;

    protected $ids;
    protected $leaders;

    public function __construct($ids)
    {
        $this->ids = $ids;

        // prefetch leaders ONCE here
        $this->leaders = User::where('leader_status', 1)
            ->get()
            ->keyBy('id');

        ini_set('memory_limit', '1024M');
    }

    public function query()
    {
        return Transaction::with([
            'user:id,name,email,hierarchyList,country',
            'from_wallet:id,type,name,wallet_address,user_id',
            'to_wallet:id,type,name,wallet_address,user_id',
            'from_account:id,meta_login',
            'to_account:id,meta_login',
            'payment_account:id,payment_platform_name,bank_sub_branch',
            'setting_payment:id,payment_account_name,account_no',
            'user.ofCountry:id,name'
        ])
            ->select([
                'id',
                'user_id',
                'transaction_type',
                'fund_type',
                'transaction_number',
                'txn_hash',
                'to_wallet_address',
                'payment_method',
                'amount',
                'transaction_charges',
                'transaction_amount',
                'conversion_amount',
                'status',
                'approval_at',
                'remarks',
                'created_at',
                'from_wallet_id',
                'to_wallet_id',
                'from_meta_login',
                'to_meta_login',
                'payment_account_id',
                'setting_payment_method_id',
            ])
            ->addSelect([
                // Profit Amount subquery
                'profitAmount' => DB::table('wallet_logs')
                    ->selectRaw('SUM(amount)')
                    ->whereColumn('wallet_logs.user_id', 'transactions.user_id')
                    ->where('wallet_logs.purpose', 'ProfitSharing'),

                // Bonus Amount subquery
                'bonusAmount' => DB::table('wallet_logs')
                    ->selectRaw('SUM(amount)')
                    ->whereColumn('wallet_logs.user_id', 'transactions.user_id')
                    ->whereIn('wallet_logs.purpose', [
                        'PerformanceIncentive',
                        'SameLevelRewards',
                        'LotSizeRebate'
                    ])
            ])
            ->whereIn('id', $this->ids)
            ->latest();
    }

    public function getFirstLeaderFromHierarchy($hierarchyList): ?array
    {
        $ids = array_reverse(array_filter(explode('-', trim($hierarchyList, '-'))));

        foreach ($ids as $id) {
            if (isset($this->leaders[$id])) {
                return [
                    'name' => $this->leaders[$id]->name,
                    'email' => $this->leaders[$id]->email,
                ];
            }
        }
        return null;
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
        // Get the hierarchy list and process the leader lookup
        $firstLeader = $this->getFirstLeaderFromHierarchy($row->user->hierarchyList);

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
            $firstLeader['name'] ?? '-',
            $row->user?->ofCountry?->name ?? '-',
            $row->transaction_type,
            $row->fund_type,
            $from,
            $row->transaction_type == 'Withdrawal' ? $row->to_wallet_address : $to,
            $row->transaction_number,
            $row->txn_hash,
            $row->payment_method == 'Bank' ? "'".$row->to_wallet_address : $row->to_wallet_address,
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
        return 500; // adjust depending on your memory
    }
}
