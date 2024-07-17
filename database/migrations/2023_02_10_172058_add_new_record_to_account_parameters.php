<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Account;
use App\Models\AccountCBU;
use App\Models\AccountParameter;

class AddNewRecordToAccountParameters extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        AccountParameter::query()->create([
            'name' => 'Обязательства перед SolutionLabs',
            'mask' => '29802',
            'type' => AccountParameter::TYPE_INACTIVE,
            'balance_type' => AccountParameter::BALANCE_TYPE_ON,
            'contract_bind' => 0,
        ]);
        $item = [
            'name' => 'Обязательства перед SolutionLabs',
            'number' => '29802000505570410001',
            'mask' => '29802',
            'currency' => 'UZS',
        ];
        Account::query()->create($item);
        AccountCBU::query()->create($item);

        //Set account balance 0
        Account::query()->whereIn('mask',['10513','10509','56802','29802'])->update(['balance' => 0]);
        AccountCBU::query()->whereIn('mask',['10513','10509','56802','29802'])->update(['balance' => 0]);

        \App\Models\MFOEventPayment::query()->update(['status' => 0,'record_created_at' => now()]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $item = AccountParameter::query()->where('mask','=','29802')->first();
        if($item){
            $item->delete();
        }
        Account::query()->where('mask','=','29802')->delete();
        AccountCBU::query()->where('mask','=','29802')->delete();
    }
}
