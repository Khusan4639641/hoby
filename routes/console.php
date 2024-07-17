<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->describe('Display an inspiring quote');

// TODO - remove this command after testing method
Artisan::command('1c:report {startDate} {endDate}', function($startDate, $endDate, \App\Services\MFO\Account1CService $service) {
    $this->info('Generating report to the accounts_1c_temp table');
    $service->generateReport(
         \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $startDate),
         \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $endDate)
     ) ? $this->info('Report generated successfully') : $this->error('Report generation failed');
});
