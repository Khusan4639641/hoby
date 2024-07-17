<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contract_statuses', function (Blueprint $table) {
            $table->id();
            $table->integer('contract_id')->index()->unique();
            $table->integer('status')->index();
            $table->enum('type', ['installment', 'mfo'])->default('installment')->index();
            $table->integer('myid_attempts')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contract_statuses');
    }
}
