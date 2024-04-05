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
                    'volume' => $row->volume,
                    'rebate' => $row->rebate,
                    'status' => $row->status,
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
            'Total Rebate',
            'Status',
        ];
    }

    public function chunkSize(): int
    {
        return $this->chunkSize;
    }
}
