<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Console\Command;

class ACLAction extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'acl:action {action?}';

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
        $role = Role::find(1);

        $permission = new Permission();
        $permission->name = 'detail-statistic';
        $permission->display_name = 'Посмотреть  статистику';
        $permission->description = 'Возможность просмотреть  statistic';
        $permission->save();

        $permission = new Permission();
        $permission->name = 'add-slider';
        $permission->display_name = 'Добавлять слайд';
        $permission->description = 'Возможность добавлять  слайд';
        $permission->save();
        $role->attachPermission($permission);

        $permission = new Permission();
        $permission->name = 'detail-slider';
        $permission->display_name = 'Посмотреть  слайд';
        $permission->description = 'Возможность просмотреть  слайд';
        $permission->save();
        $role->attachPermission($permission);

        $permission = new Permission();
        $permission->name = 'modify-slider';
        $permission->display_name = 'Редактировать  слайд';
        $permission->description = 'Возможность редактировать  слайд';
        $permission->save();
        $role->attachPermission($permission);

        $permission = new Permission();
        $permission->name = 'delete-slider';
        $permission->display_name = 'Удалить  слайд';
        $permission->description = 'Возможность удалить  слайд';
        $permission->save();
        $role->attachPermission($permission);


        return 0;
    }
}
