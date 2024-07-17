<?php

namespace App\Providers;

use App\Services\UniversalAutoPayment;
use Illuminate\Support\ServiceProvider;

class UniversalAutoPaymentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('universalautopayment', function ($app) {
            return new UniversalAutoPayment();
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
