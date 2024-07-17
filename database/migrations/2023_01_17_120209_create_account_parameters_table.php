<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\AccountParameter;

class CreateAccountParametersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_parameters', function (Blueprint $table) {
            $table->id();
            $table->string('name',255);
            $table->string('mask',5)->index();
            $table->tinyInteger('type')->default(AccountParameter::TYPE_ACTIVE)->comment('1-Активный, 0-Пассивный');
            $table->tinyInteger('balance_type')->default(AccountParameter::BALANCE_TYPE_ON)->comment('1-Балансовый, 0-Внебалансовый');
            $table->tinyInteger('contract_bind')->default(0);
            $table->timestamps();
        });

        $this->insertData();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('account_parameters');
    }

    private function insertData() : void
    {
        $data = [
            [
                'name' => 'Краткосрочные кредиты физ. Лицам',
                'mask' => '12401',
                'type' => AccountParameter::TYPE_ACTIVE,
                'balance_type' => AccountParameter::BALANCE_TYPE_ON,
                'contract_bind' => 1,
            ],
            [
                'name' => 'Просроченные кредиты физ. Лицам',
                'mask' => '12405',
                'type' => AccountParameter::TYPE_ACTIVE,
                'balance_type' => AccountParameter::BALANCE_TYPE_ON,
                'contract_bind' => 1,
            ],
            [
                'name' => 'Краткосрочные кредиты физ. лицам с уточненными условиями',
                'mask' => '12409',
                'type' => AccountParameter::TYPE_ACTIVE,
                'balance_type' => AccountParameter::BALANCE_TYPE_ON,
                'contract_bind' => 1,
            ],
            [
                'name' => 'Резерв по краткосрочным кредитам',
                'mask' => '12499',
                'type' => AccountParameter::TYPE_INACTIVE,
                'balance_type' => AccountParameter::BALANCE_TYPE_ON,
                'contract_bind' => 1,
            ],
            [
                'name' => 'Судебные займы физ. Лицам',
                'mask' => '15701',
                'type' => AccountParameter::TYPE_ACTIVE,
                'balance_type' => AccountParameter::BALANCE_TYPE_ON,
                'contract_bind' => 1,
            ],
            [
                'name' => 'Прочие активы',
                'mask' => '19997',
                'type' => AccountParameter::TYPE_ACTIVE,
                'balance_type' => AccountParameter::BALANCE_TYPE_ON,
                'contract_bind' => 1,
            ],
            [
                'name' => 'Обязательства по краткосрочным кредитам',
                'mask' => '91901',
                'type' => AccountParameter::TYPE_ACTIVE,
                'balance_type' => AccountParameter::BALANCE_TYPE_OFF,
                'contract_bind' => 1,
            ],
            [
                'name' => 'Обязательства по краткосрочным кредитам - встречный учет',
                'mask' => '96345',
                'type' => AccountParameter::TYPE_ACTIVE,
                'balance_type' => AccountParameter::BALANCE_TYPE_OFF,
                'contract_bind' => 1,
            ],
            [
                'name' => 'Прочие депозиты в др. банках',
                'mask' => '10513',
                'type' => AccountParameter::TYPE_ACTIVE,
                'balance_type' => AccountParameter::BALANCE_TYPE_ON,
                'contract_bind' => 0,
            ],
            [
                'name' => 'Безналичные средства в пути',
                'mask' => '10509',
                'type' => AccountParameter::TYPE_ACTIVE,
                'balance_type' => AccountParameter::BALANCE_TYPE_ON,
                'contract_bind' => 0,
            ],
            [
                'name' => 'Оценка предсказуемого ущерба',
                'mask' => '56802',
                'type' => AccountParameter::TYPE_ACTIVE,
                'balance_type' => AccountParameter::BALANCE_TYPE_ON,
                'contract_bind' => 0,
            ],
        ];
        foreach ($data as $item) {
            AccountParameter::query()->create($item);
        }
    }
}
