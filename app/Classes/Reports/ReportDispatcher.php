<?php

namespace App\Classes\Reports;

use App\Models\File;
use App\Models\ReportFile;

use App\Jobs\AppendRowsToFile;
use App\Jobs\MoveExportCsvFileToRemote;
use App\Jobs\CreateFreshExportCsvFile;
use App\Jobs\UpdateReportFileStatus;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ReportDispatcher
{
    private $user_id;
    private $report;
    private $period;
    private $start_date;
    private $end_date;
    private $report_types;

    public function __construct($user_id, $report, $period, $start_date, $end_date)
    {
        $this->user_id = $user_id;
        $this->report = $report;
        $this->period = $period;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->report_types = ReportFile::getReportTypes();
    }

    public function init() {

        Log::channel('reports')->info('ReportDispatcher init: '.date("h:i:sa"));

        /* 1. Generate appropriate export file name */
        $file_name = $this->generateFilename();
        Log::channel('reports')->info($file_name);

        /* 2. Generate appropriate export and temp file paths */
        $file_path = $this->generateFilepath($file_name);
        $temp_file_path = $this->getTempFilepath($file_name);

        /* 3. Create ReportFile model */
        $report_model = $this->createReportFileEntry();
        Log::channel('reports')->info('ReportFile ID: '.$report_model->id);

        /* 4. Create File model and link it to ReportFile */
        $file_model = $this->createFileEntry($report_model, $file_name, $file_path);
        Log::channel('reports')->info('File ID: '.$file_model->id);

        /* If ReportFile and File models created: */
        if ($report_model && $file_model) {

            /* 5. Choose appropriate Export model */
            $export_model_name = $this->report_types[$this->report]['exportModel'];
            $export_model = new $export_model_name;
            Log::channel('reports')->info($export_model_name);

            /* 6. Get Export model heading */
            $headings = $export_model->getHeadings();

            /* 7. Prepare params for fractional queries */
            $limit = $export_model->limit;
            $total = $export_model->query($count = true);
            $total_queries = ceil($total/$limit);
            Log::channel('reports')->info('Total frac. queries amount: '.$total_queries);

            /* 8. Generate AppendRowsToFile jobs for each frac. query */
            $append_rows_to_file_jobs = [];
            for ($i = 1; $i <= $total_queries; $i++) {
                $offset = $limit * ($i-1);
                $append_rows_to_file_jobs[] = new AppendRowsToFile($export_model, $offset, $limit, $temp_file_path);
            }
            Log::channel('reports')->info('AppendRowsToFile jobs list generated: '.date("h:i:sa"));

            /* 9. Append additional jobs to chained jobs list */
            $chained_jobs = $append_rows_to_file_jobs;
            array_push($chained_jobs,
                new MoveExportCsvFileToRemote($file_name, $file_path),
                new UpdateReportFileStatus($report_model->id, ReportFile::STATUS_COMPLETE)
            );

            /* 11. Dispatch all required jobs on chain */
            CreateFreshExportCsvFile::withChain($chained_jobs)->dispatch($temp_file_path, $headings);

            Log::channel('reports')->info('All required jobs have been dispatched: '.date("h:i:sa"));
        }
    }

    private function generateFilename() {

        $filename_template = $this->report_types[$this->report]['filename'];

        return str_replace("{}", uniqid(), $filename_template);
    }

    private function generateFilepath($file_name) {

        return 'report_files/'.$this->user_id.'/'.$file_name;
    }

    private function getTempFilepath($file_name) {


        return storage_path('temp_report_files'.DIRECTORY_SEPARATOR.$file_name);
    }

    private function createReportFileEntry() {

        $report_model = ReportFile::create([
            'user_id'       => $this->user_id,
            'report'        => $this->report,
            'period'        => $this->period,
            'start_date'    => $this->start_date,
            'end_date'      => $this->end_date,
            'status'        => ReportFile::STATUS_CREATED
        ]);

        return $report_model;
    }

    private function createFileEntry($report, $file_name, $file_path) {

        $file_model = File::create([
            'element_id'    => $report->id,
            'model'         => 'report-file',
            'type'          => 'report',
            'language_code' => null,
            'path'          => $file_path,
            'name'          => $file_name,
            'user_id'       => $this->user_id
        ]);

        return $file_model;
    }

    /*private function generateFreshExportFile($file_path, $headings) {

        $csv = fopen($file_path, 'w');

        // Add file header for correct UTF-8 characters encoding
        fprintf($csv, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($csv, $headings);

        fclose($csv);
    }*/
}
