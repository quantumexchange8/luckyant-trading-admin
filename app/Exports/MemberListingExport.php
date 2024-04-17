<?php

namespace App\Exports;

use App\Models\Country;
use App\Models\User;
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

            $result[] = [
                'name' => $record->name,
                'email' => $record->email,
                'created_at' => Carbon::parse($record->created_at)->format('Y-m-d'),
                'first_leader' => $record->getFirstLeader()->name ?? '',
                'wallets_sum_balance' => $record->wallets->sum('balance'),
                'country' => Country::find($record->country)->name,
                'rank' => $record->rank->name,
                'kyc_approval' => $record->kyc_approval,
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
            'Wallet Balance',
            'Country',
            'Rank',
            'Status',
        ];
    }
}
