<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBannedUsersListTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('banned_users_list', function (Blueprint $table) {
            $table->id();
            $table->string('surname_k', 100);
            $table->string('name_k', 100);
            $table->string('patronymic_k', 100);
            $table->string('surname_l', 50);
            $table->string('name_l', 50);
            $table->string('patronymic_l', 50);
            $table->date('birth_date');
            $table->text('birth_place');
            $table->string('passport_series', 10);
            $table->string('passport_number', 10);
            $table->date('passport_reg_date');
            $table->text('passport_reg_place');
            $table->string('tax_number', 50)->nullable();
            $table->text('business_work_info')->nullable();
            $table->string('business_reg_num', 100)->nullable();
            $table->date('business_reg_date')->nullable();
            $table->string('business_type', 100)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('banned_users_list');
    }
}
