<?php

namespace App\Jobs;

use App\Exports\MemberListingExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExportMemberReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $ids;
    public $timeout = null;

    public function __construct($ids)
    {
        $this->ids = $ids;
        $this->queue = 'member-export';
    }

    public function handle(): void
    {
        (new MemberListingExport($this->ids))->store('public/member-report.xlsx');
    }
}
