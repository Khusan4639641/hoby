<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\EdWallet as model;

class EdWallet extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
            Schema::create('ed_wallet', function (Blueprint $table) {
                $table->id();
                $table->date('doc_date')->comment('Дата документа');
                $table->string('account')->comment('Счёт');
                $table->text('name')->comment('Наименование');
                $table->string('doc_id')->comment('Номер документа');
                $table->string('doc_type')->comment('Тип документа');
                $table->string('filial')->comment('Филиал');
                $table->string('turnover_debit')->comment('Оборот Дебет');
                $table->string('turnover_credit')->nullable()->comment('Оборот кредит');
                $table->text('purpose_of_payment')->comment('Назначение платежа');
                $table->string('cash_symbol')->comment('Кассовый символ');
                $table->string('inn')->comment('ИНН');
                $table->timestamps();
            });

            $resons = [
                [
                    'doc_date'           => '2023-03-02',
                    'account'            => "22640000900001190007",
                    'name'               => 'АЖ "SOLUTIONS LAB" 308349548',
                    'doc_id'             => '505',
                    'doc_type'           => '21',
                    'filial'             => '01190',
                    'turnover_debit'     => '10000000,00',
                    'turnover_credit'    => '',
                    'purpose_of_payment' => '00668Пополнение счета для эмиссии электронных денег',
                    'cash_symbol'        => '66',
                    'inn'                => '000000000'
                ],
                [
                    'doc_date'           => '2023-03-02',
                    'account'            => "22640000900001190007",
                    'name'               => 'АО "SOLUTIONS LAB" 308349548',
                    'doc_id'             => '47',
                    'doc_type'           => '21',
                    'filial'             => '01190',
                    'turnover_debit'     => '12773200265,64',
                    'turnover_credit'    => '',
                    'purpose_of_payment' => '00641Перевод денежных средств с целью пополнения электронного кошелька по договору № 1 от 01.02.2023г.',
                    'cash_symbol'        => '64',
                    'inn'                => '000000000'
                ],
                [
                    'doc_date'           => '2023-03-14',
                    'account'            => "22640000900001190007",
                    'name'               => 'АО "SOLUTIONS LAB" 308349548',
                    'doc_id'             => '54',
                    'doc_type'           => '21',
                    'filial'             => '01190',
                    'turnover_debit'     => '1152026000,00',
                    'turnover_credit'    => '',
                    'purpose_of_payment' => '00668Перевод денежных средств с целью пополнения электронного кошелька по договору № 1 от 01.02.2023г.',
                    'cash_symbol'        => '66',
                    'inn'                => '000000000'
                ],
                [
                    'doc_date'           => '2023-03-15',
                    'account'            => "22640000900001190007",
                    'name'               => 'Счет учета электронных денег - resus NASIYA 308349548',
                    'doc_id'             => '55',
                    'doc_type'           => '21',
                    'filial'             => '01190',
                    'turnover_debit'     => '8383099197,96',
                    'turnover_credit'    => '',
                    'purpose_of_payment' => '00668Перевод денежных средств с целью пополнения электронного кошелька по договору № 1 от 01.02.2023г.',
                    'cash_symbol'        => '66',
                    'inn'                => '000000000'
                ],
                [
                    'doc_date'           => '2023-03-16',
                    'account'            => "22640000900001190007",
                    'name'               => 'АЖ "SOLUTIONS LAB" 308349548',
                    'doc_id'             => '638',
                    'doc_type'           => '21',
                    'filial'             => '01190',
                    'turnover_debit'     => '3240000000,00',
                    'turnover_credit'    => '0',
                    'purpose_of_payment' => '00668Пополнение счета для эмиссии электронных денег',
                    'cash_symbol'        => '66',
                    'inn'                => '000000000'
                ],
                [
                    'doc_date'           => '2023-03-16',
                    'account'            => "22640000900001190007",
                    'name'               => 'АЖ "SOLUTIONS LAB" 308349548',
                    'doc_id'             => '638',
                    'doc_type'           => '21',
                    'filial'             => '01190',
                    'turnover_debit'     => '3240000000,00',
                    'turnover_credit'    => '0',
                    'purpose_of_payment' => '00668Пополнение счета для эмиссии электронных денег',
                    'cash_symbol'        => '66',
                    'inn'                => '000000000'
                ],
            ];

            foreach ($resons as $reson) {
                model::create($reson);
            }
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('ed_wallet')) {
            Schema::dropIfExists('ed_wallet');
        }
    }

}
