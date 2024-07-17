<?php

namespace App\Providers\KATM;

use App\Services\KATM\MakeReportToKatmService;
use Illuminate\Support\ServiceProvider;

class MakeReportToKatmProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('makeRepKatm', function () {
            return new MakeReportToKatmService();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
