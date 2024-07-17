<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCardNumberPrefixColumnToCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasColumn('cards', 'card_number_prefix')) {
            Schema::table('cards', function (Blueprint $table) {
                $table->string('card_number_prefix', 20)->nullable()->comment('для хранения первых нешифрованных 8 цифр карты');
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
        if (Schema::hasColumn('cards', 'card_number_prefix')) {
            Schema::table('cards', function (Blueprint $table) {
                $table->dropColumn('card_number_prefix');
            });
        }
    }
}
