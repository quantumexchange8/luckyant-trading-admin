<?php

namespace App\Exports;

use App\Models\User;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PammSubscriptionExport implements FromCollection, WithHeadings, WithMapping
{
    private $records;
    private $leaders;

    public function __construct($query)
    {
        $this->records = $query->orderByDesc('approval_date')->get();

        $userHierarchyLists = $this->records->pluck('user.hierarchyList')
            ->filter()
            ->flatMap(fn($list) => explode('-', trim($list, '-')))
            ->unique()
            ->toArray();

        $this->leaders = User::whereIn('id', $userHierarchyLists)
            ->where('leader_status', 1)
            ->get()
            ->keyBy('id');
    }

    /**
     * @return Collection
     */
    public function collection(): Collection
    {
        return $this->records;
    }

    public function map($record): array
    {
        $userService = new UserService();
        $firstLeader = $userService->getFirstLeader($record->user?->hierarchyList, $this->leaders);

        return [
            Carbon::parse($record->created_at)->format('Y-m-d H:i:s'),
            $record->user->name ?? '-',
            $record->user->email ?? '-',
            $firstLeader?->name,
            $record->meta_login,
            $record->master->tradingUser->name ?? '-',
            strtoupper($record->type),
            $record->master_meta_login,
            $record->master->tradingUser->from_account_type->name ?? '-',
            $record->subscription_number,
            $record->package->amount ?? null,
            $record->subscription_package_product,
            $record->subscription_amount,
            $record->expired_date ? Carbon::parse($record->expired_date)->format('Y-m-d H:i:s') : '',
            $record->status,
            $record->approval_date ? Carbon::parse($record->approval_date)->format('Y-m-d H:i:s') : '',
            $record->settlement_date ? Carbon::parse($record->approval_date)->format('Y-m-d') . ' - ' . Carbon::parse($record->settlement_date)->addDay()->format('Y-m-d') : '',
            $record->settlement_date ? Carbon::parse($record->settlement_date)->subDays($record->settlement_period)->format('Y-m-d') : '',
            $record->settlement_date ? Carbon::parse($record->settlement_date)->addDay()->format('Y-m-d') : '',
            $record->termination_date ? Carbon::parse($record->termination_date)->format('Y-m-d H:i:s') : '',
        ];
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
            'Expired On',
            'Status',
            'Approval Date',
            'Settlement Start Date',
            'Settlement End Date',
            'Termination Date',
        ];
    }
}
