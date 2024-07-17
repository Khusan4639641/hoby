<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddPermitionV3ForEditor extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('permission_role')->insert([
            'role_id' => Role::whereName('editor')->first()->id,
            'permission_id' => Permission::whereName('V3AdminАaqList')->first()->id,
        ]);

        DB::table('permission_role')->insert([
            'role_id' => Role::whereName('editor')->first()->id,
            'permission_id' => Permission::whereName('V3AdminShowHistory')->first()->id,
        ]);

        DB::table('permission_role')->insert([
            'role_id' => Role::whereName('editor')->first()->id,
            'permission_id' => Permission::whereName('V3AdminFaqInsert')->first()->id,
        ]);

        DB::table('permission_role')->insert([
            'role_id' => Role::whereName('editor')->first()->id,
            'permission_id' => Permission::whereName('V3AdminFaqDelete')->first()->id,
        ]);

        DB::table('permission_role')->insert([
            'role_id' => Role::whereName('editor')->first()->id,
            'permission_id' => Permission::whereName('V3AdminFaqUpdate')->first()->id,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $permission = Permission::whereIn('name',['V3AdminАaqList','V3AdminShowHistory','V3AdminFaqInsert','V3AdminFaqDelete','V3AdminFaqUpdate']);
        if($permission){
            DB::table('permission_role')->whereIn('permission_id',$permission->pluck('id')->toArray())->delete();
            $permission->delete();
        }
    }
}
