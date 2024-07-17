<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use \App\Models\Role;

class InsertCollectorRoleIntoRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Role::updateOrCreate(
            ['name' => 'collector'],
            [
                'display_name' => 'Коллектор', 
                'description' => 'Коллектор. Имеет доступ к кабинету коллектора'
            ]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Role::where('name', 'collector')->delete();
    }
}
