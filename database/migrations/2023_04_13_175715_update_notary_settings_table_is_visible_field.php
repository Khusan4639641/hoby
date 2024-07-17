<?php

use App\Models\NotarySetting;

use Illuminate\Database\Migrations\Migration;

class UpdateNotarySettingsTableIsVisibleField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        NotarySetting::whereNotIn("id", [10,13])->update(["is_visible" => 0]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        NotarySetting::whereNotIn("id", [10,13])->update(["is_visible" => 1]);
    }
}
