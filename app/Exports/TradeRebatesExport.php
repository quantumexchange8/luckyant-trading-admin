<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TradeRebatesExport implements FromCollection, WithHeadings, ShouldQueue
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
        foreach($records as $row){

            $result[] = array(
                'date' => Carbon::parse($row->created_at)->format('Y-m-d H:i:s'),
                'name' => $row->upline_user->name,
                'email' => $row->upline_user->email,
                'affiliate_name' => $row->user->email,
                'affiliate_email' => $row->user->email,
                'trade_volume' => $row->trade_volume,
                'net_rebate_amt' => $row->net_rebate_amt,
                'rebate_final_amt_get' => $row->rebate_final_amt_get,
                'status' => $row->is_claimed,
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
            'Affiliate Name',
            'Affiliate Email',
            'Trade Volume',
            'Rebate',
            'Total Rebate Earn',
            'Status',
        ];
    }
}
