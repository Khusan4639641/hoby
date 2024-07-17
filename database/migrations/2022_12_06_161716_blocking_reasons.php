<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\BlockingReasons as BlockingReason;

class BlockingReasons extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('blocking_reasons')) {
              Schema::create('blocking_reasons', function (Blueprint $table) {
                  $table->id();
                  $table->string('name');
                  $table->string('position')->default('top');
                  $table->timestamps();
            });

            $resons = [
                ['name'=>"Не отправляет вовремя минусовые счета фактуры",'position'=>'top'],
                ['name'=>"Продает черный товар",'position'=>'top'],
                ['name'=>"Не выставляет счет фактуру вовремя",'position'=>'top'],
                ['name'=>"Не загружает акт/фото клиента с товаром",'position'=>'top'],
                ['name'=>"Много проблемных клиентов со стороны Вендора",'position'=>'top'],
                ['name'=>"Много ошибок при работе с платформой",'position'=>'top'],
                ['name'=>"NPL высокий",'position'=>'top'],
                ['name'=>"Интернет-магазин, проблемы с доставкой, загрузке актов и фото клиента с товаром",'position'=>'top'],
                ['name'=>"Дал логин и пароль третьим лицам",'position'=>'top'],
                ['name'=>"Создает договор на другой товар отдает другой товар",'position'=>'top'],
                ['name'=>"Сотрудничает с магазинами которых мы отключили по веским причинам (то есть делает продажи через другой компании)",'position'=>'top'],
                ['name'=>"Не грамотность бухгалтера",'position'=>'top'],
                ['name'=>"Много отмен договоров со стороны партнера",'position'=>'top'],
                ['name'=>"Другое",'position'=>'bottom']
            ];

            foreach ($resons as $reson) {
                BlockingReason::create($reson);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('blocking_reasons')) {
                Schema::dropIfExists('blocking_reasons');
        }
    }
}
