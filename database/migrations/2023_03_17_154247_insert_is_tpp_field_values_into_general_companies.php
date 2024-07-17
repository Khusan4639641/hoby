<?php

use App\Models\GeneralCompany;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InsertIsTppFieldValuesIntoGeneralCompanies extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        GeneralCompany::find(1)->update([ "is_tpp" => 1 ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        GeneralCompany::find(1)->update([ "is_tpp" => 0 ]);
    }
}
