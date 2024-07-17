<?php

namespace App\Providers;

use App\Services\Debtor;
use Illuminate\Support\ServiceProvider;

class DebtorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('debtor', function ($app) {
            return new Debtor();
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
