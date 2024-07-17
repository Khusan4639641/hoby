<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class MoveExportCsvFileToRemote implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $file_name;
    public $remote_file_path;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($file_name, $remote_file_path)
    {
        $this->file_name = $file_name;
        $this->remote_file_path = $remote_file_path;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Storage::disk('sftp')->writeStream(
            $this->remote_file_path,
            Storage::disk('temp_report_files')->readStream($this->file_name)
        );

        Storage::disk('temp_report_files')->delete($this->file_name);
    }
}
