<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

use App\Models\V3\RoleV3;
use App\Models\V3\PermissionV3;

class InsertIntoPermissionsTableDebtCollectLeaderAnalyticLettersRecord extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $leader_role = RoleV3::where("name", "debt-collect-leader")->first();
        $admin_role  = RoleV3::where("name", "admin")->first();

        $permission = new PermissionV3();
        $permission->name         = "detail-leader-reports-letters";
        $permission->display_name = "Право на получение отчёта\nпо сформированным письмам(проект Коллекторы)";
        $permission->description  = "Проект Коллекторы.\nСоздан на для роли Лидер (проект Коллекторы)\nи даёт пермишен на получение отчёта по сформированным письмам.";
        $permission->save();

        DB::table("permission_role")->insert([
            "permission_id" => $permission->id,
            "role_id"       => $leader_role->id
        ]);
        DB::table("permission_role")->insert([
            "permission_id" => $permission->id,
            "role_id"       => $admin_role->id
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $permission  = PermissionV3::where("name", "detail-leader-reports-letters")->first();
        $leader_role = RoleV3::where("name", "debt-collect-leader")->first();
        $admin_role  = RoleV3::where("name", "admin")->first();

        if (
            $permission
        ) {
            if (
                DB::table("permission_role")
                    ->where("permission_id", "=", $permission->id)
                    ->where("role_id", "=", $leader_role->id)
                    ->exists()
            ) {
                DB::table("permission_role")
                    ->where("permission_id", "=", $permission->id)
                    ->where("role_id", "=", $leader_role->id)
                    ->delete()
                ;
            }
            if (
                DB::table("permission_role")
                    ->where("permission_id", "=", $permission->id)
                    ->where("role_id", "=", $admin_role->id)
                    ->exists()
            ) {
                DB::table("permission_role")
                    ->where("permission_id", "=", $permission->id)
                    ->where("role_id", "=", $admin_role->id)
                    ->delete()
                ;
            }
        }

        if ( $permission ) {
            $permission->delete();
        }
    }
}
