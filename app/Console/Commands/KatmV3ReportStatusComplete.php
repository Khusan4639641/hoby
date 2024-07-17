<?php

namespace App\Console\Commands;

use App\Http\Controllers\Core\V3\KatmV3Controller;
use Illuminate\Console\Command;

class KatmV3ReportStatusComplete extends Command
{
    protected $signature = 'katmV3Status:complete';
    protected $description = 'Завершает отчеты которые не были завершены';

    public function handle()
    {
        $this->info('Запуск обновления статусов');
        KatmV3Controller::status();
        $this->info('Обновления статусов завершено');
    }

}
