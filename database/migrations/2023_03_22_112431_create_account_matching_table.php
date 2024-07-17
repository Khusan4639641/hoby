<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountMatchingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('matched_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('mfo_mask', 5)->nullable();
            $table->string('1c_mask', 6)->nullable();
            $table->foreignId('parent_id')->nullable();
            $table->string('number', 10)->nullable();
            $table->text('mfo_account_name')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['mfo_mask', '1c_mask']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('matched_accounts');
    }
}
