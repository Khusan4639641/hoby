<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PermissionEdRoleTableInsertNewRow extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $exists = Role::where('name','ed_employee')->first();
        if(!$exists){
            $new_role = new Role();
            $new_role->name = 'ed_employee';
            $new_role->display_name = 'Сотрудник ЭД от банка';
            $new_role->description = 'Сотрудник ЭД от банка. Только Чтение. Просматривает все платежи по электронным деньгам МКО по: Выдаче кредита, Продажам, Отменам и Возвратам.';
            $new_role->save();
            $permission = new Permission();
            $permission->name = "detail-ed-payments";
            $permission->display_name = "Посмотреть платежи ЭД";
            $permission->description = "Возможность посмотреть платежи ЭД(Электронные Деньги скорее всего)";
            $permission->save();
            DB::table('permission_role')->insert([
                'role_id' => $new_role->id,
                'permission_id' => $permission->id,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $role = Role::where('name','ed_employee')->first();
        if($role){
            DB::table('permission_role')->where('role_id',$role->id)->delete();
            $role->delete();
            Permission::where('name','detail-ed-payments')->delete();
        }
    }
}
