<?php

use App\Models\AvailablePeriod;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAvailablePeriodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('available_periods', function (Blueprint $table) {
            $table->id();
            $table->string('period',255);
            $table->tinyInteger('period_months');
            $table->string('title_ru',255);
            $table->string('title_uz',255);
            $table->tinyInteger('status')->default(1)->comment('1-Active, 0-Inactive');
            $table->timestamps();
        });

        $this->insertDummyData();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('available_periods');
    }

    private function insertDummyData() : void
    {
        $data = [
            [
                'period' => '3',
                'period_months' => 3,
                'title_ru' => '0-0-3',
                'title_uz' => '0-0-3',
                'status' => AvailablePeriod::STATUS_ACTIVE,
            ],
            [
                'period' => '6',
                'period_months' => 6,
                'title_ru' => '6 Месяц',
                'title_uz' => '6 Oy',
                'status' => AvailablePeriod::STATUS_ACTIVE,
            ],
            [
                'period' => '9',
                'period_months' => 9,
                'title_ru' => '9 Месяц',
                'title_uz' => '9 Oy',
                'status' => AvailablePeriod::STATUS_ACTIVE,
            ],
            [
                'period' => '12',
                'period_months' => 12,
                'title_ru' => '12 Месяц',
                'title_uz' => '12 Oy',
                'status' => AvailablePeriod::STATUS_ACTIVE,
            ],
        ];

        foreach ($data as $item) {
            AvailablePeriod::create($item);
        }
    }
}
