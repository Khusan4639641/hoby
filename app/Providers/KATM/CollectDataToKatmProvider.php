<?php

namespace App\Providers\KATM;

use App\Services\KATM\CollectDataToKatmService;
use Illuminate\Support\ServiceProvider;

class CollectDataToKatmProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('collectDataToKatm', function () {
            return new CollectDataToKatmService();
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
