<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProcessingColumnsToCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasColumn('cards', 'token_payment')) {
            Schema::table('cards', function (Blueprint $table) {
                $table->string('token_payment', 255)->nullable();
            });
        }

        if(!Schema::hasColumn('cards', 'bean')) {
            Schema::table('cards', function (Blueprint $table) {
                $table->string('bean', 50)->nullable();
            });
        }

        if(!Schema::hasColumn('cards', 'processing_type')) {
            Schema::table('cards', function (Blueprint $table) {
                $table->string('processing_type', 50)->nullable();
            });
        }

        if(!Schema::hasColumn('cards', 'is_processing_active')) {
            Schema::table('cards', function (Blueprint $table) {
                $table->boolean('is_processing_active')->nullable();
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
        /*Schema::table('cards', function (Blueprint $table) {

        });*/
    }
}
