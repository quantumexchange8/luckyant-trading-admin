<?php

namespace App\Exports;

use App\Models\User;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ApplicantExport implements FromCollection, WithHeadings
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
            ->latest()
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
        $records->each(function ($applicantQuery) use ($leaders) {
            $userService = new UserService();
            $firstLeader = $userService->getFirstLeader($applicantQuery->user?->hierarchyList, $leaders);

            $applicantQuery->first_leader_name = $firstLeader?->name;
        });

        $result = array();
        foreach($records as $record){
            $result[] = array(
                'request_date' => Carbon::parse($record->created_at)->format('Y-m-d H:i:s'),
                'request_user' => $record->user->name ?? '-',
                'request_email' => $record->user->email ?? '-',
                'first_leader' => $record->first_leader_name,
                'applicant_name' => $record->name ?? '-',
                'applicant_email' => $record->email ?? '-',
                'applicant_gender' => $record->gender ?? '-',
                'applicant_country' => $record->country->name ?? '-',
                'applicant_phone_number' => $record->phone_number ?? '-',
                'applicant_identity_number' => $record->identity_number ?? '-',
                'applicant_requires_transport' => $record->requires_transport ? 'Yes' : 'No',
                'applicant_requires_ib_training' => $record->requires_ib_training ? 'Yes' : 'No',
                'applicant_flight_name' => $record->transport_detail->name ?? '-',
                'applicant_flight_gender' => $record->transport_detail->gender ?? '-',
                'applicant_flight_country' => $record->transport_detail->country->name ?? '-',
                'applicant_flight_dob' => $record->transport_detail->dob ?? '-',
                'applicant_flight_phone_number' => $record->transport_detail->phone_number ?? '-',
                'applicant_flight_identity_number' => $record->transport_detail->identity_number ?? '-',
                'applicant_flight_departure_address' => $record->transport_detail->departure_address ?? '-',
                'applicant_flight_return_address' => $record->transport_detail->return_address ?? '-',
                'status' => $record->status,
                'approval_at' => $record?->approval_at,
                'remarks' => $record->remarks ?? '-',
            );
        }

        return collect($result);
    }

    public function headings(): array
    {
        return [
            'Request Date',
            'Request User Name',
            'Request User Email',
            'First Leader',
            'Applicant Name',
            'Applicant Email',
            'Applicant Gender',
            'Applicant Country',
            'Applicant Phone',
            'Applicant IC/Passport',
            'Applicant Flight',
            'Applicant IB Training',
            'Flight-Name',
            'Flight-Gender',
            'Flight-Country',
            'Flight-DOB',
            'Flight-Phone',
            'Flight-IC/Passport',
            'Flight-Departure City',
            'Flight-Return City',
            'Status',
            'Approval Date',
            'Remarks',
        ];
    }
}
