<?php

namespace App\Exports;

use App\Models\User;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProfitSharingExport implements FromCollection, WithHeadings
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
        $records = $this->query->get();

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
        $records->each(function ($incentive) use ($leaders) {
            $userService = new UserService();
            $firstLeader = $userService->getFirstLeader($incentive->user?->hierarchyList, $leaders);

            $incentive->first_leader_name = $firstLeader?->name;
        });

        $result = array();
        foreach($records as $record){
            $result[] = array(
                'date' => Carbon::parse($record->created_at)->format('Y-m-d H:i:s'),
                'name' => $record->user?->name ?? '-',
                'email' => $record->user?->email ?? '-',
                'first_leader' => $record->first_leader_name,
                'category' => trans('public.' . $record->category),
                'subscription_number' => $record->subscription_number,
                'total_profit' => $record->total_profit,
                'profit_sharing_percent' => $record->profit_sharing_percent,
                'profit_sharing_amt' => $record->profit_sharing_amt,
                'is_claimed' => $record->is_claimed,
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
            'Category',
            'Subscription Number',
            'Profit',
            'Profit Percentage',
            'Profit Sharing Amount',
            'Status'
        ];
    }
}
