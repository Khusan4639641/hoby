<?php

namespace App\Jobs\MkoJobs;

use App\Enums\MKOInfoCodesEnum;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class MkoDirectories
{
    private Carbon $dateFrom;
    private Carbon $dateTo;
    private array  $request;
    private array  $mkoReportsCodes;
    private string $groupInfoCode = '13';


    public function __construct(array $request, Carbon $dateFrom, Carbon $dateTo)
    {
        $this->dateFrom        = $dateFrom;
        $this->dateTo          = $dateTo;
        $this->request         = $request;
        $this->mkoReportsCodes = MKOInfoCodesEnum::toArray();
    }

    /**
     * Создаем новые директории для отчетов
     *
     * @return string
     */
    public function createMkoReportsDirs(): string
    {
        $path = 'mko_reports/' . $this->dateFrom->format('Y.m.d') . '-' .
            $this->dateTo->format('Y.m.d') . '/' .
            $this->request['dispatch_number'];
        if (app()->environment('local')) {
            $disk = Storage::disk('local');
        } else {
            $disk = Storage::disk('sftp');
        }
        $disk->makeDirectory($path);

        return $path;
    }

    public function generateMkoReportFileName(int $index, $companyCode): string
    {
        return 'M' . $companyCode . $this->groupInfoCode . '.' . $this->mkoReportsCodes[$index];
    }
}
