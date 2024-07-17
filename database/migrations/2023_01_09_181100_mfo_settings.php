<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\MfoSettings as MfoSetting;

class MfoSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('mfo_settings')) {
            Schema::create('mfo_settings', function (Blueprint $table) {
                $table->id();
                $table->integer('general_company_id')->comment("Ид Компани");
                $table->string('loan_type_code')->comment("Спрв Виды кредитования, микрозайм");
                $table->string('credit_object_code')->comment("Код объекта кредитования");
                $table->string('currency_code')->comment("Код валюты");
                $table->string('bank_code')->comment("Код банка");
                $table->string('contract_type_code')->comment("Кредитный договор");
                $table->string('subject_type_code')->comment("Код типа субъекта - физ лицо");
                $table->string('borrower_type_code')->comment("Код типа заемщика");
                $table->string('reason_early_termination')->comment("Причина досрочного прекращения договора(спр A17)");
                $table->string('disclaimer_note')->comment("Примечание к отказу (спр 0A8 отказ заемщика в получении кредита)");
                $table->string('issuance_form')->comment("Форма выдачи - безналичный");
                $table->text('payment_purpose')->comment("Цель платежа погашение кредита");
                $table->string('type_loan_collateral')->comment("Тип обеспечения кредита (спр 33)");
                $table->timestamps();
            });
            MfoSetting::create([
                'general_company_id'      => 3,
                'loan_type_code'          => '32',
                'credit_object_code'      => '060024',
                'currency_code'           => '000',
                'bank_code'               => '00974',
                'contract_type_code'      => '1',
                'subject_type_code'       => '2',
                'borrower_type_code'      => '0801',
                'reason_early_termination'=> '1',
                'disclaimer_note'         => '8',
                'issuance_form'           => '0',
                'payment_purpose'         => 'погашение кредита',
                'type_loan_collateral'    => '60'
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
        if (Schema::hasTable('mfo_settings')) {
            Schema::dropIfExists('mfo_settings');
        }
    }
}
