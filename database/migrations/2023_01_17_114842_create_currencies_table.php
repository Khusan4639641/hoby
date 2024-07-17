<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCurrenciesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('name',50);
            $table->string('code',3);
            $table->string('number',3);
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
        Schema::dropIfExists('currencies');
    }

    /**
     * Insert dummy data to currencies
     *
     * @return void
     */
    private function insertDummyData() : void
    {
        $data = [
            [
                "name" => "Uzbek Sum",
                "code" => "UZS",
                "number" => "000",
            ]
        ];
        foreach ($data  as $value){
            \App\Models\Currency::query()->create([
                'name' => $value['name'],
                'code' => $value['code'],
                'number' => $value['number'],
            ]);
        }
    }
}
