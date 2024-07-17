<?php

namespace App\Providers;

use App\Services\OldEncrypter;
use Illuminate\Support\ServiceProvider;

class OldEncryptionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('oldencrypter', function ($app) {
            return new OldEncrypter();
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
