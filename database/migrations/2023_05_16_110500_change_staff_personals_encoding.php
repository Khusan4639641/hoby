<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeStaffPersonalsEncoding extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('staff_personals', function (Blueprint $table) {
            $table->string('fullname')->collation('utf8mb4_general_ci')->change();
            $table->string('pinfl')->collation('utf8mb4_general_ci')->change();
            $table->string('tmp_fio')->collation('utf8mb4_general_ci')->change();
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
            $table->string('fullname')->collation('utf8mb4_unicode_ci')->change();
            $table->string('pinfl')->collation('utf8mb4_unicode_ci')->change();
            $table->string('tmp_fio')->collation('utf8mb4_unicode_ci')->change();
        });
    }
}
