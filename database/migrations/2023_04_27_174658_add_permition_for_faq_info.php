<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddPermitionForFaqInfo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $permission = Permission::where('name','panel.faqinfo.index')->first();
        DB::table('permission_role')->insert([
            'role_id' => Role::whereName('admin')->first()->id,
            'permission_id' => $permission->id,
        ]);

        DB::table('permission_role')->insert([
            'role_id' => Role::whereName('editor')->first()->id,
            'permission_id' => $permission->id,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $permission = Permission::where('name','panel.faqinfo.index')->first();
        if($permission){
            DB::table('permission_role')->where('permission_id',$permission->id)->delete();
            $permission->delete();
        }
    }
}
