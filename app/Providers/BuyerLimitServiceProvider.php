<?php

namespace App\Providers;

use App\Services\BuyerLimitService;
use Illuminate\Support\ServiceProvider;

class BuyerLimitServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('buyerLimit', function () {
            return new BuyerLimitService();
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
