<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateFieldsInBuyerPersonals extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('buyer_personals', function (Blueprint $table) {
            $table->date('birthday_open')->nullable();
            $table->date('passport_date_issue_open')->nullable();
            $table->date('passport_expire_date_open')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('buyer_personals', function (Blueprint $table) {
            $table->dropColumn('birthday_open');
            $table->dropColumn('passport_date_issue_open');
            $table->dropColumn('passport_expire_date_open');
        });
    }
}
