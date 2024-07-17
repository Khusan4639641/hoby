<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCancelReasonColumnToDetailPaymentsTable extends Migration
{
    public function up(): void
    {
        Schema::table('detail_payments', function (Blueprint $table) {
            $table->string('cancel_reason', 256)->nullable()->comment('причина отмены');
        });
    }

    public function down(): void
    {
        Schema::table('detail_payments', function (Blueprint $table) {
            $table->dropColumn('cancel_reason');
        });
    }
}
