<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompanyAccountsTable extends Migration
{
    public function up(): void
    {
        Schema::create('company_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->string('payment_account', 20);
            $table->string('mfo', 5);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_accounts');
    }
}
