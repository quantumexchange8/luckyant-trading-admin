<?php

namespace App\Exports;

use App\Models\User;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AccountPendingExport implements FromCollection, WithHeadings
{
    private $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    /**
     * @return Collection
     */
    public function collection(): Collection
    {
        $records = $this->query
            ->orderByDesc('created_at')
            ->get();

        $userHierarchyLists = $records->pluck('user.hierarchyList')
            ->filter()
            ->flatMap(fn($list) => explode('-', trim($list, '-')))
            ->unique()
            ->toArray();

        $leaders = User::whereIn('id', $userHierarchyLists)
            ->where('leader_status', 1)
            ->get()
            ->keyBy('id');

        // Attach the first leader details
        $records->each(function ($subscriptionQuery) use ($leaders) {
            $userService = new UserService();
            $firstLeader = $userService->getFirstLeader($subscriptionQuery->user?->hierarchyList, $leaders);

            $subscriptionQuery->first_leader_name = $firstLeader?->name;
        });

        $result = array();
        foreach($records as $record){

            $result[] = array(
                'date' => Carbon::parse($record->created_at)->format('Y-m-d'),
                'name' => $record->user->name,
                'email' => $record->user->email,
                'first_leader' => $record->first_leader_name,
                'type' => $record->transaction_type,
                'from' => $record->from_wallet->name,
                'to' => $record->to_meta_login,
                'account_type' => $record->to_account->accountType->name,
                'transaction_id' => $record->transaction_number,
                'amount' =>  number_format((float)$record->amount, 2, '.', ''),
                'fund_type' => $record->fund_type,
                'status' => $record->status,
                'remarks' => $record->remarks,
            );
        }

        return collect($result);
    }

    public function headings(): array
    {
        return [
            'Date',
            'Name',
            'Email',
            'First Leader',
            'Type',
            'From',
            'To',
            'Account Type',
            'Transaction ID',
            'Amount',
            'Fund Type',
            'Status',
            'Remarks',
        ];
    }
}
