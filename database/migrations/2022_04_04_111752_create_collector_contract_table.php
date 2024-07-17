<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCollectorContractTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('collector_contract', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collector_id')
                ->constrained();
            $table->unsignedInteger('contract_id');
            $table->timestamps();
            $table->softDeletes();

            // Laravel foreign error: Id must be unsigned bigInt(20)
            // TODO: Change contracts.id from int(10) to bigInt(20)
            $table->foreign('contract_id')
                ->references('id')
                ->on('contracts');
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('collector_contract');
    }
}
