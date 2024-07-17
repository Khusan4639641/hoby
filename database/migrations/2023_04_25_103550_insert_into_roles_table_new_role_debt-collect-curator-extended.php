<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use Illuminate\Support\Facades\DB;
use App\Models\Role;
use App\Models\Permission;

class InsertIntoRolesTableNewRoleDebtCollectCuratorExtended extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!$old_role = Role::where("name", "debt-collect-curator-extended")->first()) {
            $role = new Role();
            $role->name         = "debt-collect-curator-extended";
            $role->display_name = "Куратор коллекторов (Расширенный)";
            $role->description  = "Отдел взыскания.\nСоздан на основании роли куратор и имеет функцию менять районы у должников, пока что.";
            $role->save();
        }

        if (!$old_permission = Permission::where("name", "modify-debtors-district")->first()) {
            $permission = new Permission();
            $permission->name         = "modify-debtors-district";
            $permission->display_name = "Права на изменение района у должника (проект Коллекторы)";
            $permission->description  = "Проект Коллекторы.\nСоздан на для роли Куратор-расширенный и Лидер (проект Коллекторы) и даёт права на изменение района у должника.";
            $permission->save();
        }

        $leader_role = Role::where("name", "debt-collect-leader")->first();
        $admin_role = Role::where("name", "admin")->first();

        if (
            !$old_role
            && !$old_permission
        ) {
            DB::table("permission_role")->insert([
                "permission_id" => $permission->id,
                "role_id"       => $role->id
            ]);
            DB::table("permission_role")->insert([
                "permission_id" => $permission->id,
                "role_id"       => $leader_role->id
            ]);
            DB::table("permission_role")->insert([
                "permission_id" => $permission->id,
                "role_id"       => $admin_role->id
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
        $permission  = Permission::where("name", "modify-debtors-district")->first();
        $role        = Role::where("name", "debt-collect-curator-extended")->first();
        $leader_role = Role::where("name", "debt-collect-leader")->first();
        $admin_role  = Role::where("name", "admin")->first();

        if ( $permission && $role ) {
            DB::table("permission_role")
                ->where("permission_id", "=", $permission->id)
                ->where("role_id", "=", $role->id)
                ->delete();
            DB::table("permission_role")
                ->where("permission_id", "=", $permission->id)
                ->where("role_id", "=", $leader_role->id)
                ->delete();
            DB::table("permission_role")
                ->where("permission_id", "=", $permission->id)
                ->where("role_id", "=", $admin_role->id)
                ->delete();
            DB::table("role_user")
                ->where("role_id", "=", $role->id)
                ->delete();
        }
        if ( $permission ) {
            $permission->delete();
        }
        if ( $role ) {
            $role->delete();
        }
    }
}
