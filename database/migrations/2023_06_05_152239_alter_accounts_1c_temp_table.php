<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class AlterAccounts1cTempTable extends Migration
{
    public function up(): void
    {
        Schema::table('accounts_1c_temp', function (Blueprint $table) {
            $table->integer('contract_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('accounts_1c_temp', function (Blueprint $table) {
            $table->integer('contract_id')->nullable()->change();
        });
    }
}
