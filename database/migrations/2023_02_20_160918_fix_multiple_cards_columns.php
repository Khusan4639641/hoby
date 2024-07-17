<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixMultipleCardsColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasColumn('cards', 'is_main')) {
            Schema::table('cards', function (Blueprint $table) {
                $table->tinyInteger('is_main')->default(0);
                $table->tinyInteger('kyc_status')->default(0);
                $table->index(['is_main', 'kyc_status']);
            });


        }
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
