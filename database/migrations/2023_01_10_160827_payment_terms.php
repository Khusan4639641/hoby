<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\PaymentTerms as PaymentTerm;

class PaymentTerms extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('payment_terms')) {
            Schema::create('payment_terms', function (Blueprint $table) {
                $table->id();
                $table->integer('period_id')->comment("Ид рассрочки");
                $table->string('urgency_type')->comment("Код типа срочности");
                $table->string('urgency_interval')->comment("Код интервала срочности");
                $table->timestamps();
            });

            $periods = [
                ['period_id'=>"1",'urgency_type'=>'1','urgency_interval'=>'03'],
                ['period_id'=>"2",'urgency_type'=>'1','urgency_interval'=>'06'],
                ['period_id'=>"3",'urgency_type'=>'1','urgency_interval'=>'07'],
                ['period_id'=>"4",'urgency_type'=>'1','urgency_interval'=>'07'],
                ['period_id'=>"5",'urgency_type'=>'1','urgency_interval'=>'03'],
            ];

            foreach ($periods as $period) {
                PaymentTerm::create($period);
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
        if (Schema::hasTable('payment_terms')) {
            Schema::dropIfExists('payment_terms');
        }
    }
}
