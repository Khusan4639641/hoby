<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMkoReportsTable extends Migration
{
    public function up(): void
    {
        Schema::create('mko_reports', function (Blueprint $table) {
            $table->id();
            $table->integer('mko_id');
            $table->date('from');
            $table->date('to');
            $table->string('dispatch_number');
            $table->string('url');
            $table->float('is_sent')->default(0);
            $table->float('is_error')->default(0);
            $table->foreign('mko_id')->references('id')->on('general_companies');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mko_reports');
    }
}
