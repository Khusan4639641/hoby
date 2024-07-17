<?php


namespace App\Helpers;


use App\Models\Buyer;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class PaycoinHelper
{

    /** добавить балл
     * @var $buyer Buyer
     * @var $amount float
     */
    public static function addBall(&$scheduleItem){

        $from = \Carbon\Carbon::parse($scheduleItem->payment_date);
        $to = \Carbon\Carbon::parse($scheduleItem->paid_at);
        $day = $to->diffInDays($from);

        if($day<4) {
            $amount = $scheduleItem->total - $scheduleItem->balance;
            $config = Config::get('test.paycoin');
            $k_limit = $config['limits'][$scheduleItem->buyer->settings->limit]; // 3, 1.5, 1
            $scheduleItem->buyer->settings->paycoin += ($amount / $config['bonus']) * $k_limit;
            if(!$scheduleItem->buyer->settings->save()){
                Log::channel('autopayment')->info('ERROR. NOT SAVED contract  ' . $scheduleItem->id . ' ' . $scheduleItem->buyer->id . ' row: ' .  __LINE__);
                Log::channel('cronpayment')->info('ERROR. NOT SAVED contract  ' . $scheduleItem->id . ' ' . $scheduleItem->buyer->id . ' row: ' .  __LINE__);
                Log::channel('autopayment')->info($scheduleItem->buyer->settings);
                Log::channel('cronpayment')->info($scheduleItem->buyer->settings);
            }

            $pc = $scheduleItem->buyer->settings->paycoin; // всего

            if( $pc > 1000 && $pc < 2000 ){ // silver
                $level = 1;
            }elseif( $pc >= 2000 && $pc < 3000 ){ // gold
                $level = 2;
            }elseif($pc >= 3000 ){ // platinum
                $level = 3;
            }else{
                $level = 0;
            }

            $levels = [0 => 'Bronze', 1 => 'Silver', 2 => 'Gold', 3 => 'Platinum'];


            $message = [
                'ru' => [
                    'title' =>'Повышение уровня',
                    'text' => "Поздравляем! Ваш уровень был повышен до {$levels[$level]}. Покупайте еще больше за меньшие деньги!",
                ],
                'uz'=>[
                    'title' =>'Arzonroqqa xarid qiling',
                    'text' => "Tabriklaymiz! Darajangiz {$levels[$level]}ga oshdi! Endi yanada ko'proq mahsulotlarni yanada arzonroqqa xarid qiling!",
                ]
            ];

            $buyerInfo = Buyer::getInfo($scheduleItem->buyer->id);

            $options = [
                'type'=>PushHelper::TYPE_LEVEL,
                'buyer' => $buyerInfo,
                'title' => $message[$buyerInfo['lang']]['title'],
                'text' => $message[$buyerInfo['lang']]['text'],
                'id' => 0
            ];

            Log::channel('autopayment')->info('paycoins add curr level: ' . $levels[$level] . ' pc: ' . $pc);

            PushHelper::send($options);

        }


    }




}
