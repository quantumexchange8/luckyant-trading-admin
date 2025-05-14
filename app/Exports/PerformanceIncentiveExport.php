<?php

namespace App\Exports;

use App\Models\User;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PerformanceIncentiveExport implements FromCollection, WithHeadings
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
            if ($record->meta_login) {
                $from_account = $record->meta_login;
                $from_email = $record->user->email;
            } else {
                if ($record->category == 'pamm') {
                    $from_account = $record->pamm_subscription->meta_login;
                    $from_email = $record->pamm_subscription->user->email;
                } else {
                    $from_account = $record->subscription->meta_login;
                    $from_email = $record->subscription->user->email;
                }
            }

            $result[] = array(
                'date' => Carbon::parse($record->created_at)->format('Y-m-d H:i:s'),
                'name' => $record->user->name,
                'email' => $record->user->email,
                'first_leader' => $record->first_leader_name,
                'category' => trans('public.' . $record->category),
                'from_account' => $from_account,
                'from_email' => $from_email,
                'type' => $record->meta_login ? 'Personal' : 'Network',
                'subscription_number' => $record->subscription_number,
                'subscription_profit' => $record->subscription_profit_amt,
                'percentage' => $record->personal_bonus_percent,
                'performance_incentive' => $record->personal_bonus_amt,
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
            'From Account',
            'From Email',
            'Type',
            'Subscription Number',
            'Subscription Profit',
            'Incentive Percentage',
            'Performance Incentive',
        ];
    }
}
