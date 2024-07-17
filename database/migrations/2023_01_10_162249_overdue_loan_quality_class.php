<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\OverdueLoanQuality;
class OverdueLoanQualityClass extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('overdue_loan_quality_class')) {
            Schema::create('overdue_loan_quality_class', function (Blueprint $table) {
                $table->id();
                $table->string('name')->comment("Наименование класса качества просроченного кредита");
                $table->string('possible_losses')->comment("Процент отчисления в резерв возможных убытков");
                $table->integer('expiry_days_from')->comment("Количество дней просрочки (с)");
                $table->integer('expiry_days_to')->comment("Количество дней просрочки (до)")->nullable();
                $table->timestamps();
            });

            $infos = [
                ['name'=>"Стандартный",   'possible_losses'=>'0', 'expiry_days_from'=>1,  'expiry_days_to'=>30],
                ['name'=>"Субстандартный",'possible_losses'=>'25','expiry_days_from'=>31, 'expiry_days_to'=>60],
                ['name'=>'','possible_losses'=>'','expiry_days_from'=>0, 'expiry_days_to'=>0],
                ['name'=>"Сомнительный",  'possible_losses'=>'50','expiry_days_from'=>61, 'expiry_days_to'=>90],
                ['name'=>"Безнадежный",   'possible_losses'=>'100', 'expiry_days_from'=>'91'],
            ];

            foreach ($infos as $info) {
                OverdueLoanQuality::create($info);
            }
            OverdueLoanQuality::where('id',3)->delete();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('overdue_loan_quality_class')) {
            Schema::dropIfExists('overdue_loan_quality_class');
        }
    }
}
