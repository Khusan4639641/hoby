<?php

use App\Models\AccountingPaymentPurpose;
use Illuminate\Database\Migrations\Migration;

class RefillAccountingPaymentPurposesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        AccountingPaymentPurpose::updateOrCreate([
            'code' => '1007',
            'title' => 'Выдача кредита',
            'company_type' => 1,
        ]);
        AccountingPaymentPurpose::updateOrCreate([
            'code' => '1008',
            'title' => 'Оплата за кредит',
            'company_type' => 2,
        ]);
        AccountingPaymentPurpose::updateOrCreate([
            'code' => '1009',
            'title' => 'Просроченный кредит',
            'company_type' => 1,
        ]);
        AccountingPaymentPurpose::updateOrCreate([
            'code' => '1011',
            'title' => 'Переданные в суд',
            'company_type' => 1,
        ]);
        AccountingPaymentPurpose::updateOrCreate([
            'code' => '1012',
            'title' => 'Резерв',
            'company_type' => 1,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
