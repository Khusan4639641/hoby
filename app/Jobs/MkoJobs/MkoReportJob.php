<?php

namespace App\Jobs\MkoJobs;

use App\Enums\MKOInfoCodesEnum;
use App\Helpers\FileHelper;
use App\Models\MkoReport;
use ErrorException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\{HttpClientException};
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\{Log, Storage};
use Throwable;
use ZipArchive;

class MkoReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private Carbon         $dateFrom;
    private Carbon         $dateTo;
    private string         $mkoCompanyCode;
    private array          $mkoReportsCodes;
    private array          $request;
    private string         $path;
    private MkoDirectories $dirs;
    private MkoZip         $zip;
    private MkoStrings     $strings;
    private MkoFrom1C      $from1C;
    private ZipArchive     $archiver;
    private int            $countFrom;
    private int            $countTo;
    private MkoFrom1C      $mkoFrom1C;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $request)
    {
        $this->request = $request;
    }

    public function handle(): void
    {
        try {
            $this->dateFrom        = Carbon::parse($this->request['from']);
            $this->dateTo          = Carbon::parse($this->request['to']);
            $this->dirs            = new MkoDirectories($this->request, $this->dateFrom, $this->dateTo);
            $this->path            = $this->dirs->createMkoReportsDirs();
            $this->zip             = new MkoZip($this->path);
            $this->strings         = new MkoStrings($this->request);
            $this->mkoFrom1C       = new MkoFrom1C($this->dateFrom, $this->dateTo);
            $this->mkoReportsCodes = MKOInfoCodesEnum::toArray();
            $this->archiver        = $this->zip->getArchiver();
            $this->countFrom       = $this->request['count_from'] ?? 0;
            $this->countTo         = $this->request['count_to'] ?? 8;
            Log::channel('mko_to_bank_errors')->info('MKOJOB STARTED');
            $this->process();
            Log::channel('mko_to_bank_errors')->info('MKOJOB ENDED');
        } catch (QueryException $e) {
            Storage::disk('sftp')->delete(session('savePath'));
            Log::channel('mko_to_bank_errors')->error('MAKING-REPORT-DB-ERROR: ',
                                                      [$e->getCode(), $e->getLine(), $e->getMessage()]);
        } catch (ErrorException $e) {
            Storage::disk('sftp')->delete(session('savePath'));
            Log::channel('mko_to_bank_errors')->error('MAKING-REPORT-PARAMETER-ERROR: ',
                                                      [$e->getCode(), $e->getLine(), $e->getMessage()]);
        } catch (HttpClientException $e) {
            Storage::disk('sftp')->delete(session('savePath'));
            Log::channel('mko_to_bank_errors')->error('MAKING-REPORT-HTTP-ERROR: ',
                                                      [$e->getCode(), $e->getLine(), $e->getMessage()]);
        } catch (Throwable $e) {
            Storage::disk('sftp')->delete(session('savePath'));
            Log::channel('mko_to_bank_errors')->error('MAKING-REPORT-ERROR: ',
                                                      [$e->getCode(), $e->getLine(), $e->getMessage()]);
        }
    }

    public function process(): void
    {
        $this->archiver->open(storage_path('/app/temp.zip'), ZipArchive::CREATE | ZipArchive::OVERWRITE);
        for ($i = $this->countFrom; $i < $this->countTo; $i++) {
            Log::channel('mko_to_bank_errors')->info('LOOP FOR REPORT#: ' . ($i + 1) . ' STARTED');
            $reportData       = $this->strings->getDataByReportCode($this->mkoReportsCodes[$i]);
            $filledDataString = $this->strings->fillReportDataByCode($this->mkoReportsCodes[$i], $reportData);
            $filename         = $this->dirs->generateMkoReportFileName($i, $this->request['company']['nko']);
            $this->archiver->addFromString($filename, $filledDataString);
            Log::channel('mko_to_bank_errors')->info('LOOP FOR REPORT#: ' . ($i + 1) . ' ENDED');
        }
        $this->archiver->close();
        $this->zip->saveFile($this->path);
        $this->createReportRecord(FileHelper::url($this->zip->getSavePath()), $this->strings->sumByMask);
    }

    /**
     * Записать данные об отчете в таблицу
     *
     * @param string $url
     *
     * @return void
     */
    private function createReportRecord(string $url, array $data): void
    {
        MkoReport::create(['mko_id'          => $this->request['mko_id'],
                           'from'            => $this->request['from'],
                           'to'              => $this->request['to'],
                           'dispatch_number' => $this->request['dispatch_number'],
                           'url'             => $url,
                           'report_info'     => json_encode($data)]);
    }

    public function failed(Throwable $exception): void
    {
        Log::channel('mko_to_bank_errors')->error('MAKING-REPORT-ERROR: ',
                                                  [$exception->getCode(),
                                                   $exception->getLine(),
                                                   $exception->getMessage()]);
    }
}
