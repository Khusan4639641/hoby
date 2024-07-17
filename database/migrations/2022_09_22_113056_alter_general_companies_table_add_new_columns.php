<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterGeneralCompaniesTableAddNewColumns extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
      if(!Schema::hasColumns('general_companies', ['sign', 'stamp'])) {

          Schema::table('general_companies', static function (Blueprint $table) {
              $table->string('sign')->after('address')->nullable();
              $table->string('stamp')->after('address')->nullable();
          });

      }
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
      if(Schema::hasColumns('general_companies', ['sign', 'stamp'])) {

          Schema::table('general_companies', function (Blueprint $table) {
              $table->dropColumn(['sign', 'stamp']);
          });

      }
  }
}
