<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCompanyIdFieldToDetailPaymentsTable extends Migration
{
    public function up(): void
    {
        Schema::table('detail_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('detail_payments', function (Blueprint $table) {
            $table->dropColumn('company_id');
        });
    }
}
