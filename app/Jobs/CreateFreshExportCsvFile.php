<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class CreateFreshExportCsvFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $temp_file_path;
    public $headings;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($temp_file_path, $headings)
    {
        $this->temp_file_path = $temp_file_path;
        $this->headings = $headings;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->createFolderIfNotExists();

        $csv = fopen($this->temp_file_path, 'w');

        /* Add file header for correct UTF-8 characters encoding. */
        fprintf($csv, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($csv, $this->headings);

        fclose($csv);
    }

    private function createFolderIfNotExists() {

        $directory_name = dirname($this->temp_file_path);

        if (!is_dir($directory_name)) {
            mkdir($directory_name, 0755, true);
        }
    }
}
