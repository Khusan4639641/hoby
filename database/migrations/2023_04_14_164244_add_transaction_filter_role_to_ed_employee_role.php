<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

class AddTransactionFilterRoleToEdEmployeeRole extends Migration
{
    public function up(): void
    {
        $permission = Permission::where('name', 'V3AdminTransactionFilter')->first();


        $role = Role::where('name', 'ed_employee')->first();

        if (isset($permission) && isset($role)) {
            DB::table('permission_role')->insert([
                [
                    'permission_id' => $permission->id,
                    'role_id' => $role->id
                ],
            ]);
        } else {
            throw new RuntimeException("Role or permission not found ...");
        }

    }

    public function down(): void
    {
        $permission = Permission::where('name', 'V3AdminTransactionFilter')->first();

        $role = Role::where('name', 'ed_employee')->first();

        DB::table('permission_role')->where([
            'permission_id' => $permission->id ?? 0,
            'role_id' => $role->id ?? 0
        ])->delete();
    }
}
