<?php

use Illuminate\Database\Migrations\Migration;

use \App\Models\Role;

class InsertCollectorRolesInRolesTable extends Migration
{
    protected iterable $old_roles = [
        [
            'name' => 'collector',
            'display_name' => 'Коллектор',
            'description' => 'Коллектор. Имеет доступ к кабинету коллектора'
        ],
        [
            'name' => 'collectors-curator',
            'display_name' => 'Куратор коллекторов',
            'description' => 'Имеет доступы с странице работы с коллекторами'
        ],
    ];
    protected iterable $roles = [
        [
            'name' => 'debt-collector',
            'display_name' => 'Коллектор',
            'description' => 'Отдел взыскания. Имеет свою кабинет'
        ],
        [
            'name' => 'debt-collect-curator',
            'display_name' => 'Куратор Коллекторов',
            'description' => 'Отдел взыскания. Контролирует коллекторов и распределяет районы и должников. Имеет свою кабинет'
        ],
        [
            'name' => 'debt-collect-leader',
            'display_name' => 'Руководитель Взыскания',
            'description' => 'Отдел взыскания. Контролирует кураторов и мониторит общую картиру. Имеет свою кабинет'
        ],
        [
            'name' => 'debt-collect-accounting',
            'display_name' => 'Бухгалтерия Взыскания',
            'description' => 'Отдел взыскания. Видит оплаты коллекторам. Имеет свою кабинет'
        ],
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach ($this->old_roles as $role) {
            Role::where('name', $role['name'])->delete();
        }

        foreach ($this->roles as $role) {
            Role::create($role);
        }

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        foreach ($this->roles as $role) {
            Role::where('name', $role['name'])->delete();
        }
        foreach ($this->old_roles as $role) {
            Role::create($role);
        }

    }
}
