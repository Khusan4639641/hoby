<?php

namespace App\Providers\KATM;

use App\Services\KATM\SaveReportFromKatmService;
use Illuminate\Support\ServiceProvider;

class SaveReportFromKatmProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('saveKatm', function () {
            return new SaveReportFromKatmService();
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
