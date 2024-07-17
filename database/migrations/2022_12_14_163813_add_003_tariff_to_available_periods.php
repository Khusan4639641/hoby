<?php

use Illuminate\Database\Migrations\Migration;
use \App\Models\AvailablePeriod;

class Add003TariffToAvailablePeriods extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //Change old record
        $period_months_3 = AvailablePeriod::where('period_months',3)->first();
        if($period_months_3){
            $period_months_3->title_ru = '3 Месяц';
            $period_months_3->title_uz = '3 Oy';
            $period_months_3->save();
        }
        //Create new tariff
        $new_period = AvailablePeriod::where('period','0-0-3')->where('period_months',3)->first();
        if(!$new_period){
            AvailablePeriod::create([
                'period' => '0-0-3',
                'period_months' => 3,
                'title_ru' => '0-0-3',
                'title_uz' => '0-0-3',
                'status' => AvailablePeriod::STATUS_ACTIVE,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $new_period = AvailablePeriod::where('period','0-0-3')->where('period_months',3)->first();
        if($new_period){
            $new_period->delete();
        }
    }
}
