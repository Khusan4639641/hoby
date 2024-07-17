<?php

namespace App\Console\Commands;

use App\Models\V3\PermissionV3;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class PermissionUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permission:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        foreach (Route::getRoutes() as $item) {
            if ($item->getName() !== null) {
                if (substr($item->action['namespace'], 0, 20) == 'App\Http\Controllers') {
                    PermissionV3::updateOrCreate([
                        'name'         => $item->getName(),
                    ],
                        [
                            'name'         => $item->getName(),
                            'display_name' => $item->getName(),
                            'route_name'   => $item->getName(),
                        ]);
                }
            }
        }
        $this->info('Доступы успешно обновлены');
        return 0;
    }
}
