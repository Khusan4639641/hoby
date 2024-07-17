<?php

namespace App\Console;

use App\Console\Commands\MFOAccountBalanceHistory1C;
use App\Jobs\AutomaticPayment;
use App\Jobs\AutomaticKatm;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('monitoring:report --telegram')->hourly();
        $schedule->command('monitoring:report --telegram --type=daily')->daily();
        $schedule->command('contracts:update-expired')->daily();
        $schedule->command('permission:update')->dailyAt('14:37');
        $schedule->command('katmV3Status:complete')->everyMinute()->sendOutputTo('storage/logs/KatmV3.log');

        // send payments info to soliq to create qr_code
        $schedule->command('uztax:send')->daily();
        $schedule->command('uztax:send-missing');

        $schedule->command('contracts:royxat-export')->dailyAt('00:00');

        $schedule->command('resus:bank-account-history-sync lastWeek')->hourly();
        $schedule->command('resus:bank-canceled-receipt-sync lastMonth')->dailyAt('03:30');
        //$schedule->command('inspire')->hourly();
        //$schedule->job(new AutomaticPayment)->hourly();
        // $schedule->job(new AutomaticKatm)->everyMinute();

        $schedule->command(MFOAccountBalanceHistory1C::class)->dailyAt('23:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
