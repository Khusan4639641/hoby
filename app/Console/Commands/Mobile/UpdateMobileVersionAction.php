<?php

namespace App\Console\Commands\Mobile;

use App\Services\Mobile\AppReleaseVersion\AppReleaseVersionService;
use Illuminate\Console\Command;

class UpdateMobileVersionAction extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appVersion:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command -  Update redis values after changing database table `mobile_app_releases` ';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        AppReleaseVersionService::saveVerifiedVersionToRedis();

        echo 'success' . PHP_EOL;
    }
}
