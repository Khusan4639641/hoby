<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\StaffPersonal;

class AddFullnameAndStatusFieldsToStaffPersonalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('staff_personals', function (Blueprint $table) {
            $table->string('fullname')->after('id')->comment('ФИО');
            $table->tinyInteger('status')->default(1)->after('pinfl')->comment('0 - уже не работает, 1 - работает');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('staff_personals', function (Blueprint $table) {
            $table->dropColumn('fullname');
            $table->dropColumn('status');
        });
    }
}
