<?php

namespace App\Providers\KATM;

use App\Services\KATM\CollectReportsToKatmService;
use Illuminate\Support\ServiceProvider;

class ReportingToKatmProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('repKatm', function () {
            return new CollectReportsToKatmService();
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
