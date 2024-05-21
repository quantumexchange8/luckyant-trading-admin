<?php

namespace App\Exports;

use App\Models\Country;
use App\Models\SubscriptionBatch;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MemberListingExport implements FromCollection, WithHeadings
{
    private $members;

    public function __construct($members)
    {
        $this->members = $members;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $records = $this->members->get();
        $result = [];

        foreach ($records as $record) {
            // Check if $record is an array and has the necessary properties
            $total_fund = SubscriptionBatch::where('user_id', $record->id)
                ->where('status', 'Active')
                ->sum('meta_balance');

            $real_fund = SubscriptionBatch::where('user_id', $record->id)
                ->where('status', 'Active')
                ->sum('real_fund');

            $demo_fund = SubscriptionBatch::where('user_id', $record->id)
                ->where('status', 'Active')
                ->sum('demo_fund');

            $result[] = [
                'name' => $record->name,
                'email' => $record->email,
                'phone' => $record->phone,
                'created_at' => Carbon::parse($record->created_at)->format('Y-m-d'),
                'first_leader' => $record->getFirstLeader()->name ?? '',
                'upline_email' => $record->upline->email ?? '',
                'cash_wallet_balance' => $record->wallets->where('type', 'cash_wallet')->first()->balance ?? 0,
                'bonus_wallet_balance' => $record->wallets->where('type', 'bonus_wallet')->first()->balance ?? 0,
                'e_wallet_balance' => $record->wallets->where('type', 'e_wallet')->first()->balance ?? 0,
                'country' => Country::find($record->country)->name,
                'rank' => $record->rank->name,
                'kyc_approval' => $record->kyc_approval,
                'total_deposit' => $total_fund,
                'real_fund' => $real_fund,
                'demo_fund' => $demo_fund,
            ];
        }

        return collect($result);
    }

    public function headings(): array
    {
        return [
            'Name',
            'Email',
            'Contact Number',
            'Joining Date',
            'First Leader',
            'Upline Email',
            'Cash Balance',
            'Bonus Balance',
            'Ewallet Balance',
            'Country',
            'Ranking',
            'Status',
            'Total Deposit',
            'Real Fund',
            'Demo Fund',
        ];
    }
}
