<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MasterExport implements FromCollection, WithHeadings
{
    private $query;
    public function __construct($query)
    {
        $this->query = $query;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection(): \Illuminate\Support\Collection
    {
        $records = $this->query->get();
        $result = array();
        foreach($records as $record){
            $result[] = array(
                'date' => Carbon::parse($record->created_at)->format('Y-m-d H:i:s'),
                'name' => $record->user->name,
                'email' => $record->user->email,
                'master' => $record->tradingUser->name ?? '-',
                'master_trading_account' => $record->meta_login,
                'min_join_equity' => $record->min_join_equity,
                'sharing_profit' => $record->sharing_profit,
                'estimated_monthly_returns' => $record->estimated_monthly_returns,
                'estimated_lot_size' => $record->estimated_lot_size,
                'roi_period' => $record->roi_period,
                'public_status' => $record->is_public ? 'Public' : 'Private',
                'status' => $record->status,
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
            'Master',
            'Account No',
            'Min Join Amount',
            'Sharing Profit',
            'Estimated Monthly Returns',
            'Estimated Lot Size',
            'Settlement Period',
            'Public Status',
            'Status',
        ];
    }
}
