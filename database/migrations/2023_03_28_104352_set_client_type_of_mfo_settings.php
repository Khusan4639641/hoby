<?php

use Illuminate\Database\Migrations\Migration;

class SetClientTypeOfMfoSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('mfo_settings')
            ->where('id', 1)
            ->update(['client_type' => '08']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
