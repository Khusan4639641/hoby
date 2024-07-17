<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToAccounts1cTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('accounts_1c', function (Blueprint $table) {
            $table->unsignedTinyInteger('type')->nullable()->after('subconto_number');
            $table->string('system_number', '10')->nullable()->after('type');
            $table->boolean('is_subconto_without_remainder')->default(0)->after('system_number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('accounts_1c', function (Blueprint $table) {
            $table->dropColumn(['type', 'system_number', 'is_subconto_without_remainder']);
        });
    }
}
