<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKatmClaimsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('katm_claims', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('contract_id');
            $table->string('claim', 40);
            $table->unsignedInteger('general_company_id');
            $table->timestamps();
        });
        Schema::table('katm_claims', function (Blueprint $table) {
            $table->foreign('contract_id')
                ->references('id')
                ->on('contracts');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('katm_claims', function (Blueprint $table) {
            $table->dropForeign('katm_claims_contract_id_foreign');
        });
        Schema::dropIfExists('katm_claims');
    }
}
