<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class TradeRebatesExport implements FromCollection, WithHeadings, ShouldQueue, WithChunkReading
{
    private $query;
    private $chunkSize;

    public function __construct($query, $chunkSize = 2000)
    {
        $this->query = $query;
        $this->chunkSize = $chunkSize;
        ini_set('memory_limit', '1G');
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection(): \Illuminate\Support\Collection
    {
        $result = collect();
    
        $this->query->chunk($this->chunkSize, function ($records) use ($result) {
            foreach ($records as $row) {
                $result->push([
                    'date' => Carbon::parse($row->created_at)->format('Y-m-d H:i:s'),
                    'name' => $row->upline_user->name,
                    'email' => $row->upline_user->email,
                    'affiliate_name' => $row->user->name,
                    'affiliate_email' => $row->user->email,
                    'trade_volume' => $row->trade_volume,
                    'net_rebate_amt' => $row->net_rebate_amt,
                    'rebate_final_amt_get' => $row->rebate_final_amt_get,
                    'status' => $row->is_claimed,
                ]);
            }
        });
    
        return $result;
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

    public function chunkSize(): int
    {
        return $this->chunkSize;
    }
}
