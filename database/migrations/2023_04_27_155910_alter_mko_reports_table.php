<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterMkoReportsTable extends Migration
{
    public function up(): void
    {
        Schema::table('mko_reports', function (Blueprint $table) {
            $table->boolean('is_sent')->default(0)->change();
            $table->boolean('is_error')->default(0)->change();
        });
    }

    public function down(): void
    {
    }
}
