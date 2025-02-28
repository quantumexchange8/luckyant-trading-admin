<?php

namespace App\Exports;

use App\Models\User;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MemberFundExport implements FromCollection, WithHeadings
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        $records = $this->query
            ->latest('id')
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
        $records->each(function ($userQuery) use ($leaders) {
            $userService = new UserService();
            $firstLeader = $userService->getFirstLeader($userQuery->user?->hierarchyList, $leaders);

            $userQuery->first_leader_name = $firstLeader?->name;
        });

        $result = array();
        foreach($records as $record){
            $result[] = array(
                'name' => $record->name,
                'email' => $record->email,
                'first_leader' => $record->first_leader_name,
            );
        }

        return collect($result);
    }

    public function headings(): array
    {
        return [
            'Request Date',
            'Name',
            'Email',
            'First Leader',
            'Live Account',
            'Master',
            'Type',
            'Master Account',
            'Account Type',
            'Subscription Number',
            'Subscription Package',
            'Subscription Product',
            'Fund Size',
            'Status',
            'Approval Date',
            'Termination Date',
        ];
    }
}
