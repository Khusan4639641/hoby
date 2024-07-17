<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMigrationAddFieldsToBuyerAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('buyer_addresses', function (Blueprint $table) {
            $table->string('address_myid',250)->nullable();
            $table->string('citizenship_id',200)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('buyer_addresses', function (Blueprint $table) {
            $table->dropColumn(['address_myid','citizenship_id']);
        });
    }
}
