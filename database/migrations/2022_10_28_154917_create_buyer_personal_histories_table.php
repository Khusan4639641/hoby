<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBuyerPersonalHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('buyer_personal_histories', function (Blueprint $table) {
            $table->id();
            $table->text('passport_number');
            $table->text('passport_date_issue')->nullable();
            $table->text('passport_issued_by')->nullable();
            $table->text('passport_expire_date')->nullable();
            $table->tinyInteger('passport_type');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('buyer_personal_histories');
    }
}
