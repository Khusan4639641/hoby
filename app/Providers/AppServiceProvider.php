<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
       //$this->app['request']->server->set('HTTPS', true);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        if ( in_array(env('APP_ENV'),['production','dev']) ) {
            URL::forceScheme('https');
        }

//        \DB::listen(function ($query) {
//            dump($query->sql);
//            dump($query->time);
//        });
    }


}
