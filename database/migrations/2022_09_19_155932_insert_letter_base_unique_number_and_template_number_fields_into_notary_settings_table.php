<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\NotarySetting;

class InsertLetterBaseUniqueNumberAndTemplateNumberFieldsIntoNotarySettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        NotarySetting::updateOrCreate(
            [
              'name' => 'Гайратжон',
              'surname' => 'Хуррамов',
              'patronymic' => 'Сотиболди угли',
            ],
            [
              'region' => 'Мирзо-Улугбекском районе города Ташкента',
              'address' => 'Мирзо-Улугбекский район, улица Сайрам, дом 2-А, квартира 47',
              'fee' => 27000,
              'letter_base_unique_number' => '20220235400',
              'template_number' => 'first',
            ],
        );
        NotarySetting::updateOrCreate(
            [
              'name' => 'Дилором',
              'surname' => 'Ахунджанова',
              'patronymic' => 'Шухратовна',
            ],
            [
              'region' => 'Юнусабадском районе города Ташкента',
              'address' => 'Юнусабадский район, 8 квартал, пр.А.Тимура, дом 32 А',
              'fee' => 27000,
              'letter_base_unique_number' => '20220235400',
              'template_number' => 'first',
            ],
        );
        NotarySetting::updateOrCreate(
            [
              'name' => 'Журабек',
              'surname' => 'Максудов',
              'patronymic' => 'Жумакулович',
            ],
            [
              'region' => 'Тошкент шаҳар Сергели тумани',
              'address' => 'Сергели тумани, Янги Сергели кўчаси, 3-уй',
              'fee' => 33000,
              'letter_base_unique_number' => '20220235200',
              'template_number' => 'second',
            ],
        );
        NotarySetting::updateOrCreate(
            [
              'name' => 'Феруза',
              'surname' => 'Хамраева',
              'patronymic' => 'Кобиловна',
            ],
            [
              'region' => 'Тошкент шаҳар Миробод тумани',
              'address' => 'Миробод тумани, Шахризабз кўчаси, 15-уйда',
              'fee' => 27000,
              'letter_base_unique_number' => '2022023500',
              'template_number' => 'second',
            ],
        );
        NotarySetting::updateOrCreate(
            [
              'name' => 'Зарина',
              'surname' => 'Абдалимова',
              'patronymic' => 'Хамидуллаевна',
            ],
            [
              'region' => 'Тошкент шаҳар М.Улуғбек тумани',
              'address' => 'М.Улуғбек тумани, массив Ц-1, Б.Ипак Йўли кўчаси, 27-уй',
              'fee' => 30000,
              'letter_base_unique_number' => '20220218001',
              'template_number' => 'second',
            ],
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        NotarySetting::updateOrCreate(
            [
                'name' => 'Гайратжон',
                'surname' => 'Хуррамов',
                'patronymic' => 'Сотиболди угли',
            ],
            [
                'letter_base_unique_number' => '',
                'template_number' => '',
            ],
        );
        NotarySetting::updateOrCreate(
            [
                'name' => 'Дилором',
                'surname' => 'Ахунджанова',
                'patronymic' => 'Шухратовна',
            ],
            [
                'letter_base_unique_number' => '',
                'template_number' => '',
            ],
        );
        NotarySetting::updateOrCreate(
            [
                'name' => 'Журабек',
                'surname' => 'Максудов',
                'patronymic' => 'Жумакулович',
            ],
            [
                'letter_base_unique_number' => '',
                'template_number' => '',
            ],
        );
        NotarySetting::updateOrCreate(
            [
                'name' => 'Феруза',
                'surname' => 'Хамраева',
                'patronymic' => 'Кобиловна',
            ],
            [
                'letter_base_unique_number' => '',
                'template_number' => '',
            ],
        );
        NotarySetting::updateOrCreate(
            [
                'name' => 'Зарина',
                'surname' => 'Абдалимова',
                'patronymic' => 'Хамидуллаевна',
            ],
            [
                'letter_base_unique_number' => '',
                'template_number' => '',
            ],
        );
    }
}
