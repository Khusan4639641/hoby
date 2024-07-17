<?php

namespace App\Providers;

use App\Services\GradeScoringService;
use Illuminate\Support\ServiceProvider;

class GradeScoringServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('gradeScoring', function () {
            return new GradeScoringService();
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
