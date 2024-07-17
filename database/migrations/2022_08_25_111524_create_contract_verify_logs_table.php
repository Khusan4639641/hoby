<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractVerifyLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contract_verify_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('contract_id')->index()->unsigned()->nullable();
            $table->integer('order_product_id')->index()->unsigned()->nullable();
            $table->integer('user_id')->index()->nullable();
            $table->string('old_name')->nullable();
            $table->string('new_name')->nullable();
            $table->integer('old_category_id')->nullable();
            $table->integer('new_category_id')->nullable();
            $table->integer('old_unit_id')->nullable();
            $table->integer('new_unit_id')->nullable();
            $table->timestamps();

            $table->foreign('contract_id')->references('id')->on('contracts');
            $table->foreign('order_product_id')->references('id')->on('order_products');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contract_verify_logs');
    }
}
