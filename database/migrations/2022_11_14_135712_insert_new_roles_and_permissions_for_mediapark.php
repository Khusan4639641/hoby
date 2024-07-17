<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class InsertNewRolesAndPermissionsForMediapark extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->makeDuplicateRoleAndPermission('vendor',26);
        $this->makeDuplicateRoleAndPermission('partner',27);
        $this->makeDuplicateRoleAndPermission('sales-manager',28);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->deleteDuplicateRoleAndPermission('vendor');
        $this->deleteDuplicateRoleAndPermission('partner');
        $this->deleteDuplicateRoleAndPermission('sales-manager');
    }

    private function makeDuplicateRoleAndPermission(string $role_name,int $id) : void
    {
        $new_role_name = $role_name.'-eup';//eup - stands for Exception Uploading Photo
        $exists = Role::where('name',$new_role_name)->first();
        if(!$exists){
            $role = Role::where('name',$role_name)->first();
            if($role){
                $new_role = new Role();
                $new_role->id = $id;
                $new_role->name = $new_role_name;
                $new_role->display_name = $role->display_name.' (с исключением загрузки фото для контракта)';
                $new_role->description = $role->description;
                $new_role->save();
                $permissions = DB::table('permission_role')->where('role_id',$role->id)->get();
                if(count($permissions)){
                    foreach ($permissions as $permission) {
                        DB::table('permission_role')->insert([
                            'role_id' => $new_role->id,
                            'permission_id' => $permission->permission_id,
                        ]);
                    }
                }
            }
        }
    }

    private function deleteDuplicateRoleAndPermission(string $role_name) :void
    {
        $new_role_name = $role_name.'-eup';//eup - stands for Exception Uploading Photo
        $role = Role::where('name',$new_role_name)->first();
        if($role){
            DB::table('permission_role')->where('role_id',$role->id)->delete();
            $role->delete();
        }
    }
}
