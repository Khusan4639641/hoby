<?php

use App\Models\NotarySetting;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateNotarySettingsTableFeeField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        NotarySetting::where("template_number", "second")
            ->where("surname", "Абдалимова")
            ->where("name", "Зарина")
            ->first()
            ->update(["fee" => "33000"])
        ;
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        NotarySetting::where("template_number", "second")
            ->where("surname", "Абдалимова")
            ->where("name", "Зарина")
            ->first()
            ->update(["fee" => "30000"])
        ;
    }
}
