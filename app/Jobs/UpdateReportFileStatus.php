<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

use App\Models\ReportFile;

class UpdateReportFileStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $report_id;
    public $status;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($report_id, $status)
    {
        $this->report_id = $report_id;
        $this->status = $status;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        ReportFile::where('id', $this->report_id)->update(['status' => $this->status]);
    }
}
