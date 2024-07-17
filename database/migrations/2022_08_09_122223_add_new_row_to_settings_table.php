<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;

class AddNewRowToSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Setting::query()->insert([
            'param' => 'SCORING_ALLOWED_OVERDUE_DAYS',
            'value' => 60,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Setting::query()->where([
            'param' => 'SCORING_ALLOWED_OVERDUE_DAYS',
        ])->delete();
    }
}
