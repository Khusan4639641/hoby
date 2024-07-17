<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\NotarySetting;

class InsertNewNotariesIntoNotarySettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        NotarySetting::create([
          'name' => 'Наргис',
          'surname' => 'Мейлиева',
          'patronymic' => 'Зулфикоровна',
          'region' => 'Мирзо-Улугбекского района города Ташкента',
          'address' => 'г.Ташкент, Мирзо-Улугбекский район, улица Мирзо-Улугбек, дом 82А',
          'fee' => 33000,
          'letter_base_unique_number' => 20220168600,
          'template_number' => 'third',
        ]);
        NotarySetting::create([
          'name' => 'Феруза',
          'surname' => 'Гулямова',
          'patronymic' => 'Эркиновна',
          'region' => 'Учтепинского района города Ташкента',
          'address' => 'г.Ташкент, Учтепинскский район, 14-квартал, дом 12, квартира 2',
          'fee' => 33000,
          'letter_base_unique_number' => 20220214100,
          'template_number' => 'third',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        NotarySetting::where(['name' => 'Наргис', 'surname' => 'Мейлиева', 'patronymic' => 'Зулфикоровна'])->delete();
        NotarySetting::where(['name' => 'Феруза', 'surname' => 'Гулямова', 'patronymic' => 'Эркиновна'])->delete();
    }
}
