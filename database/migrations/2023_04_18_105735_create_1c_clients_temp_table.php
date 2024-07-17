<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Create1cClientsTempTable extends Migration
{
    public function up()
    {
        Schema::create('clients_1c_temp', function (Blueprint $table) {
            $table->collation = 'utf8mb4_general_ci';
            $table->bigInteger('id')->nullable();
            $table->string('nibbd', 20);
            $table->string('surname', 20)->nullable();
            $table->string('name', 20)->nullable();
            $table->string('patronymic', 20)->nullable();
            $table->tinyInteger('gender')->nullable();
            $table->string('birth_date')->nullable();
            $table->string('region', 5)->nullable();
            $table->string('local_region', 5)->nullable();
            $table->integer('passport_type')->nullable();
            $table->integer('passport_number')->nullable();
            $table->string('passport_date_issue')->nullable();
            $table->string('passport_issued_by')->nullable();
            $table->string('inn')->nullable();
            $table->string('client_type', 20)->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('clients_1c_temp');
    }
}
