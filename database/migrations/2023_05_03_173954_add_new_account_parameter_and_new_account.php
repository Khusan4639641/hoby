<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\AccountParameter;
use \App\Models\Account;
use \App\Models\AccountCBU;

class AddNewAccountParameterAndNewAccount extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $account_parameter = AccountParameter::where('mask','=','77777')->first();
        if(!$account_parameter){
            AccountParameter::query()->create([
                'name' => 'Д/с в пути для частичного погашения',
                'mask' => '77777',
                'type' => 1,
                'balance_type' => 1,
                'contract_bind' => 0,
            ]);
        }
        $account = Account::where('mask','=','77777')->first();
        if(!$account){
            Account::query()->create([
                'name' => 'Д/с в пути для частичного погашения',
                'number' => '10509000805570410002',
                'mask' => '77777',
                'currency' => 'UZS',
            ]);
        }
        $account_cbu = AccountCBU::where('mask','=','77777')->first();
        if(!$account_cbu) {
            AccountCBU::query()->create([
                'name' => 'Д/с в пути для частичного погашения',
                'number' => '10509000805570410002',
                'mask' => '77777',
                'currency' => 'UZS',
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
        $account_parameter = AccountParameter::where('mask','=','77777')->first();
        if($account_parameter){
            $account_parameter->delete();
        }
        $account = Account::where('mask','=','77777')->first();
        if($account){
            $account->delete();
        }
        $account_cbu = AccountCBU::where('mask','=','77777')->first();
        if($account_cbu){
            $account_cbu->delete();
        }
    }
}
