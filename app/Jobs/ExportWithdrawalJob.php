<?php

namespace App\Jobs;

use App\Exports\TransactionsExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExportWithdrawalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $ids;
    public $timeout = null;

    public function __construct($ids)
    {
        $this->ids = $ids;
        $this->queue = 'withdrawal-export';
    }

    public function handle(): void
    {
        (new TransactionsExport($this->ids))->store('public/withdrawal-report.xlsx');
    }
}
