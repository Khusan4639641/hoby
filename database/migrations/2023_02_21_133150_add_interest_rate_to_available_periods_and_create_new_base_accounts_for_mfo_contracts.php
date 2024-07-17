<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use \App\Models\AccountParameter;
use \App\Models\Account;
use \App\Models\AccountCBU;

class AddInterestRateToAvailablePeriodsAndCreateNewBaseAccountsForMfoContracts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('available_periods', function (Blueprint $table) {
            $table->decimal('interest_rate',16,3)->default(0);
        });
        $this->addBaseAccountsForMfoContracts();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('available_periods', function (Blueprint $table) {
            $table->dropColumn(['interest_rate']);
        });
        $this->removeBaseAccountsForMfoContracts();
    }

    private static array $data = [
        'parameters' => [
            [
                'name' => 'Начисленные проценты по кредиту',
                'mask' => '16307',
                'type' => AccountParameter::TYPE_ACTIVE,
                'balance_type' => AccountParameter::BALANCE_TYPE_ON,
                'contract_bind' => 1,
            ],
            [
                'name' => 'Процентные доходы по краткосрочным кредитам ф/л',
                'mask' => '42001',
                'type' => AccountParameter::TYPE_INACTIVE,
                'balance_type' => AccountParameter::BALANCE_TYPE_ON,
                'contract_bind' => 0,
            ],
            [
                'name' => 'Процентные доходы по просроченным краткосрочным кредитам ф/л',
                'mask' => '42005',
                'type' => AccountParameter::TYPE_INACTIVE,
                'balance_type' => AccountParameter::BALANCE_TYPE_ON,
                'contract_bind' => 0,
            ],
        ],
        'accounts' => [
            [
                'name' => 'Процентные доходы по краткосрочным кредитам ф/л',
                'number' => '42001000905570410001',
                'mask' => '42001',
                'currency' => 'UZS',
            ],
            [
                'name' => 'Процентные доходы по просроченным краткосрочным кредитам ф/л',
                'number' => '42005000905570410001',
                'mask' => '42005',
                'currency' => 'UZS',
            ],
        ],
    ];

    private function addBaseAccountsForMfoContracts() : void
    {
        foreach (self::$data['parameters'] as $parameter) {
            AccountParameter::query()->create($parameter);
        }
        foreach (self::$data['accounts'] as $account){
            Account::query()->create($account);
            AccountCBU::query()->create($account);
        }
    }

    private function removeBaseAccountsForMfoContracts() : void
    {
        foreach (self::$data['parameters'] as $parameter) {
            AccountParameter::query()->where('mask',$parameter['mask'])->delete();
        }
        foreach (self::$data['accounts'] as $account){
            Account::query()->where('mask',$account['mask'])->delete();
            AccountCBU::query()->where('mask',$account['mask'])->delete();
        }
    }
}
