<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewFieldToPartnerSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('partner_settings', function (Blueprint $table) {
            $table->decimal('markup_003',16,2)->unsigned()->after('markup_24')->default(0);
            $table->decimal('discount_003',16,2)->unsigned()->after('discount_24')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('partner_settings', function (Blueprint $table) {
            $table->dropColumn(['markup_003','discount_003']);
        });
    }
}
