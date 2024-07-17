<?php

namespace App\Helpers;

use App\Http\Controllers\Core\CardController;
use App\Models\Buyer;
use App\Models\Card;
use App\Models\Contract;
use App\Models\CronPayment;
use App\Models\PaymentsData;
use App\Policies\ContractPaymentsSchedulePolicy;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\ContractPaymentsSchedule as CPS;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Helpers\CurlHelper;


class PaymentHelper
{

    /*public static function getScheduleList(){

        $count_contracts = 100;

        if(Redis::has('last_id')){
            $last_id = Redis::get('last_id');
        }else{
            $last_id = 0;
        }
        $last_id = 0;
        $dt = Carbon::now();
        $startDay = $dt->copy()->startOfDay();//->timestamp;
        $endDay = $dt->copy()->endOfDay();//->timestamp;
        $response = CPS::where('payment_date','<=', $dt->format('Y-m-d 23:59:59'))
            ->join('contracts', function ($join) {
                $join->on('contracts.id', '=', 'contract_payments_schedule.contract_id')
                    ->where('contracts.status', '=', [1,4]);
            })
            ->where('contract_payments_schedule.id','>',$last_id)
            ->where('contract_payments_schedule.status', 0)
            ->take($count_contracts);

        $max_id = $response->max('contract_payments_schedule.id');

        $response = $response->get();




        $last_id += $max_id; // + $count_contracts; // колво для обработки

        Redis::set('last_id',$last_id);

        Log::info('last-id: ' . $last_id . ' max-id: ' . $max_id);

        return $response;
    }*/


    /**
     * $params = [
     * 'last_id' =>
     * 'quantity' =>
     * ];
     */

    public static function getScheduleList(&$params)
    {
        $dt = Carbon::now();

        $response = CPS::select('contracts.id as cid','contract_payments_schedule.*')->where('payment_date', '<=', $dt->format('Y-m-d 23:59:59'))
            ->where('contract_payments_schedule.status', 0)
            ->with('contract')
            ->join('contracts',function ($query){
                $query->on('contracts.id','contract_payments_schedule.contract_id')->whereIn('contracts.status',[1,3,4]);
            })
            ->where('contract_payments_schedule.id', '>=', $params['last_id'])
            ->take($params['quantity']);

       // Log::channel('autopayment')->info($response->toSql());
        //Log::channel('cronpayment')->info('getScheduleList');
        //Log::channel('cronpayment')->info($response->toSql());

        $response = $response->get();

        return $response;
    }

    // возвраты денежных средств
    public static function actionRefund(Request $request)
    {
        $refund = $request->refund;
        $request = new Request();
        $cardController = new CardController();
        foreach ($refund as $tranId) {
            $request->merge([
                'payment_id' => $tranId
            ]);
            $cardController->refund($request);
            //dump($req);
        }
    }

    // списание с карты и лицевого счета
    public static function actionCronPayment($scheduleItem)
    {

        //Log::channel('autopayment')->info('$scheduleItem ' . $scheduleItem);
        // if (isset($scheduleItem->contract)) {
            // if ($scheduleItem->contract->status == 1 || $scheduleItem->contract->status == 3 || $scheduleItem->contract->status == 4) {  // просрочен или создан

               // Log::channel('autopayment')->info('Месяц оплаты: ' . $scheduleItem->payment_date . ' id: ' . $scheduleItem->id . ' Контракт: ' . $scheduleItem->contract_id . ' user_id: ' . $scheduleItem->user_id . ' - contract->status ' . $scheduleItem->contract->status);
               // Log::channel('autopayment')->info('Сумма ' . $scheduleItem->total .' остаток ' . $scheduleItem->balance );

                $request = request(); // ???
                $personalAccount = $scheduleItem->buyer->settings->personal_account;  // деньги в лс
                $cards = $scheduleItem->buyer->cardsActive;  // все карты

               // $currentPayment = (float)$scheduleItem->total;     // всего
                $balance = (float)$scheduleItem->balance;      // остаточный ожидаемый ( остаток долга за месяц ) в сумах
                $isOperation = false;

                // если есть на лс деньги, закрываем месяц с него
                if ($personalAccount > 0) {
                    Log::channel('autopayment')->info('списание с ЛС ' . $personalAccount);

                    if ($personalAccount > $balance) {  // если денег на лс больше, чем долг
                        $amount = $balance;  // списали
                        $balance = 0;
                    } else {
                        $amount = $personalAccount;  // списали
                        $balance -= $personalAccount;
                    }

                    // сохраняем расчеты
                    $scheduleItem->balance -= $amount;  // ост долга в график
                    Log::channel('autopayment')->info('остаток долга за месяц ' . $balance);

                    // если закрыли месяц
                    if ( $balance == 0 ) {
                        $scheduleItem->buyer->settings->balance += $scheduleItem->price;   // увеличиваем лимит на платеж без маржи
                        $scheduleItem->buyer->settings->save();
                        $scheduleItem->status = 1;
                        $scheduleItem->paid_at = time();
                        $scheduleItem->contract->status = 1;  // больше не просрочник
                        Log::channel('autopayment')->info('месяц закрыт ');
                    }


                    if ( !$scheduleItem->save() ) {
                        Log::channel('autopayment')->info('ERROR. NOT SAVED sch ' . $scheduleItem->id . ' ' . $scheduleItem->buyer->id);
                        Log::channel('cronpayment')->info('ERROR. NOT SAVED sch ' . $scheduleItem->id . ' ' . $scheduleItem->buyer->id);
                    }else {
                        Log::channel('autopayment')->info('scheduleItem saved ');
                    }

                    $scheduleItem->buyer->settings->personal_account -= $amount;  // списываем деньги с лс
                    if(!$scheduleItem->buyer->settings->save()){
                        Log::channel('cronpayment')->info('ERROR. NOT SAVED buyer->settings ' . $scheduleItem->id . ' ' . $scheduleItem->buyer->id);
                        Log::channel('autopayment')->info($scheduleItem->buyer->settings);
                        Log::channel('cronpayment')->info($scheduleItem->buyer->settings);
                    }

                    Log::channel('autopayment')->info('остаток денег на ЛС ' . $scheduleItem->buyer->settings->personal_account);

                    // отнимаем списанную сумму из общего долга
                    $scheduleItem->contract->balance -= $amount;

                    if ($scheduleItem->contract->balance == 0) {
                        $scheduleItem->contract->status = 9;  //  закрываем контракт
                    }
                    if(!$scheduleItem->contract->save()){
                        Log::channel('autopayment')->info('ERROR. NOT SAVED contract  ' . $scheduleItem->id . ' ' . $scheduleItem->buyer->id);
                        Log::channel('cronpayment')->info('ERROR. NOT SAVED contract  ' . $scheduleItem->id . ' ' . $scheduleItem->buyer->id);
                        Log::channel('autopayment')->info($scheduleItem->contract);
                        Log::channel('cronpayment')->info($scheduleItem->contract);
                    }

                    $payment = new Payment;
                    $payment->schedule_id = $scheduleItem->id;
                    $payment->type = 'auto';
                    $payment->order_id = $scheduleItem->contract->order_id;
                    $payment->contract_id = $scheduleItem->contract->id;

                    $payment->amount = $amount;

                    $payment->user_id = $scheduleItem->buyer->id;
                    $payment->payment_system = 'ACCOUNT';
                    $payment->status = 1;
                    $payment->save();

                    $result['ACCOUNT'] = ['amount'=>$amount]; // сумма списания c лс


                    Log::channel('autopayment')->info('списали c ЛС: ' . $payment->amount);

                    /* if ($isOperation) {

                        $request->merge([
                            'amount' => $payment->amount,
                            'buyer_id' => $payment->user_id,
                            'contract_id' => $payment->contract_id,
                            'schedule_id' => $scheduleItem->id,
                            'debt' => $balance,
                            'lc' => true,
                        ]);

                        $send = SmsHelper::sendSmsPayment($request);
                        Log::channel('autopayment')->info('sms account payment sended ' . $send);

                    } */

                }

                // если еще остался незакрытый долг, списываем с карты
                if ($balance > 0) {
                    $count = sizeof($cards);   // цикл по всем картам -
                    Log::channel('autopayment')->info('Всего карт ' . $count);
                    for ($c = 0; $c < $count; $c++) {
                        if ($balance > 0) {   // если еще есть долг
                            $card = $cards[$c];

                            if($card->status !== 1 ) continue;  // пропускаем неактивные карты

                            $request->merge(['buyer_id' => $scheduleItem->buyer->id,
                                'schedule_id' => $scheduleItem->id,
                                'order_id' => $scheduleItem->contract->order_id,
                                'contract_id' => $scheduleItem->contract->id,
                                'payment_type' => 'auto']);

                            $cardController = new CardController();
                            $request->merge(['card_id' => $card->id, 'info_card' => ['token' => $card->token, 'card_id' => $card->id,
                                'card_number' => $card->card_number,
                                'card_valid_date' => $card->card_valid_date]]);

                            $result = $cardController->balance($request);

                            if(!isset($result['result'])) continue;
                            if($result['result']['status']=='error') continue;

                            // ИСКЛЮЧИТЬ ИЗ СПИСКА ТЕ КАРТЫ, КОТОРЫЕ ЗАБЛОЧЕНЫ ИЛИ ОТКЛЮЧЕНО СМС ИНФОРМИРОВАНИЕ
                            Log::channel('autopayment')->info('карта ' . $c . '. смс информирование ' . $result['result']['state']);
                            if ($result['result']['state'] === -1) continue; // отключено смс информирование
                            if ($result['result']['state'] === -6) continue; // хз что такое, но оно нам не надо, в документации нет**

                            if ($result) {
                                //$availableBalance = $result['data']['balance'] / 100;  // для узкарда
                                $availableBalance = isset($result['result']['balance']) ? $result['result']['balance'] / 100 : 0;  // для Universal
                                Log::channel('autopayment')->info('карта ' . $c . '. Card availableBalance - cумма на карте  ' . $availableBalance);

                                if ($availableBalance > 0) {          // если на карте есть деньги - 1000 сум поставить??
                                    if ($availableBalance > $balance) {           // если хватает закрыть месяц
                                        $sum = $balance;
                                        $request->merge(['sum' => $balance]);

                                        Log::channel('autopayment')->info('перед оплатой FULL ' . $scheduleItem->id . ' ' . $scheduleItem->buyer->id);
                                        Log::channel('cronpayment')->info('перед оплатой FULL ' . $scheduleItem->id . ' ' . $scheduleItem->buyer->id);

                                        $result = $cardController->payment($request); // оплата

                                        Log::channel('autopayment')->info('после оплаты FULL ' . $scheduleItem->id . ' ' . $scheduleItem->buyer->id);
                                        Log::channel('cronpayment')->info('после оплаты FULL ' . $scheduleItem->id . ' ' . $scheduleItem->buyer->id);

                                        if ($result['status'] == 'success') {  // если списалось
                                            $balance = 0;
                                            $scheduleItem->contract->status = 1;  // больше не просрочник
                                            $isOperation = true;

                                        }else{
                                            Log::channel('cronpayment')->info($result);
                                            switch($result['data']['error']['code']){
                                                case '100':
                                                case '109':
                                                case '-31101':
                                                    CardHelper::changeStatus($card,3);
                                                    Log::channel('autopayment')->info('ERROR: '. $result['error']['code']. ' DISABLE CARD ' . $card->id . ' ' . $scheduleItem->buyer->id);
                                            }

                                        }

                                    } else {
                                        $sum = $availableBalance;    // если не хватает, списываем то, что есть
                                        $request->merge(['sum' => $availableBalance]);

                                        Log::channel('autopayment')->info('перед оплатой PART ' . $scheduleItem->id . ' ' . $scheduleItem->buyer->id);
                                        Log::channel('cronpayment')->info('перед оплатой PART ' . $scheduleItem->id . ' ' . $scheduleItem->buyer->id);

                                        $result = $cardController->payment($request); // оплата

                                        Log::channel('autopayment')->info('после оплаты PART ' . $scheduleItem->id . ' ' . $scheduleItem->buyer->id);
                                        Log::channel('cronpayment')->info('после оплаты PART ' . $scheduleItem->id . ' ' . $scheduleItem->buyer->id);


                                        if ($result['status'] == 'success') {     // если списалось
                                            Log::channel('autopayment')->info('карта ' . $c . '. ' . $balance . ' - ' . $availableBalance . ' = ' . ($balance - $availableBalance));
                                            $balance -= $availableBalance;            // остаток долга
                                            $isOperation = true;
                                        }else{
                                            Log::channel('cronpayment')->info($result);
                                        }

                                    }

                                    // если списали с карты
                                    if ($isOperation) {
                                        $scheduleItem->balance = $balance;  // ост долга в график  ??  проверить
                                        Log::channel('autopayment')->info('остаток долга в графике за месяц ' . $balance);

                                        $scheduleItem->contract->balance -= $sum;
                                        if ($scheduleItem->contract->balance == 0) {
                                            $scheduleItem->contract->status = 9;  //  закрываем контракт
                                        }

                                        if(!$scheduleItem->contract->save()){
                                            Log::channel('autopayment')->info('ERROR. NOT SAVED contract  ' . $scheduleItem->id . ' ' . $scheduleItem->buyer->id . ' row: ' .  __LINE__ );
                                            Log::channel('cronpayment')->info('ERROR. NOT SAVED contract  ' . $scheduleItem->id . ' ' . $scheduleItem->buyer->id . ' row: ' .  __LINE__);
                                            Log::channel('autopayment')->info($scheduleItem->contract);
                                            Log::channel('cronpayment')->info($scheduleItem->contract);
                                        }

                                        if ($balance == 0) {               // если закрыли месяц
                                            $scheduleItem->buyer->settings->balance += $scheduleItem->price;   // увеличиваем лимит на платеж без маржи
                                            if(!$scheduleItem->buyer->settings->save()){
                                                Log::channel('autopayment')->info('ERROR. NOT SAVED buyer->settings ' . $scheduleItem->id . ' ' . $scheduleItem->buyer->id . ' row: ' .  __LINE__);
                                                Log::channel('cronpayment')->info('ERROR. NOT SAVED buyer->settings ' . $scheduleItem->id . ' ' . $scheduleItem->buyer->id . ' row: ' .  __LINE__);
                                                Log::channel('autopayment')->info($scheduleItem->buyer->settings);
                                                Log::channel('cronpayment')->info($scheduleItem->buyer->settings);
                                            }
                                            $scheduleItem->status = 1;
                                            $scheduleItem->paid_at = time();
                                            Log::channel('autopayment')->info('месяц закрыт ');
                                        }
                                        if (!$scheduleItem->save()) {
                                            Log::channel('autopayment')->info('ERROR. NOT SAVED ' . $scheduleItem->id . ' ' . $scheduleItem->buyer->id . ' row: ' .  __LINE__);
                                            Log::channel('cronpayment')->info('ERROR. NOT SAVED ' . $scheduleItem->id . ' ' . $scheduleItem->buyer->id . ' row: ' .  __LINE__);
                                            Log::channel('autopayment')->info($scheduleItem);
                                            Log::channel('cronpayment')->info($scheduleItem);
                                        }else {
                                            Log::channel('autopayment')->info('scheduleItem saved ');
                                        }
                                        Log::channel('autopayment')->info('списали с карты: ' . $sum);
                                        //Log::channel('autopayment')->info('sms ');
                                        $request->merge([
                                            'amount' => $sum,
                                            'buyer_id' => $scheduleItem->buyer->id,
                                            'contract_id' => $scheduleItem->contract->id,
                                            'schedule_id' => $scheduleItem->id,
                                            'debt' => $balance,
                                            'lc' => false,
                                        ]);
                                        //Log::channel('autopayment')->info('sms card payment');

                                        $send = SmsHelper::sendSmsPayment($request);
                                        Log::channel('autopayment')->info('sms card payment sended ' . $send);
                                    }
                                }
                            } else {
                                Log::channel('autopayment')->info('card.register поломался у Universal');
                            }

                        }else{
                            break;
                        }


                    }
                }

                // добавление баллов, если нет просрочки
                if($isOperation) PaycoinHelper::addBall($scheduleItem);

                return true; // $scheduleItem->toJson();

           // }

          //  Log::channel('autopayment')->info($scheduleItem->contract->id . ' не попал  - контр статус : ' . $scheduleItem->contract->status);
       // }

        //Log::channel('autopayment')->info('contract not found'); // $scheduleItem->contract->id . ' не попал  - контр статус : ' . $scheduleItem->contract->status);

        return false;

    }

    // списание с карты и лицевого счета
    public static function actionPayment($scheduleItem)
    {

        Log::channel('autopayment')->info('actionPayment OFF');

        return false;

        //Log::channel('autopayment')->info('$scheduleItem ' . $scheduleItem);
        if (isset($scheduleItem->contract)) {
            if ($scheduleItem->contract->status == 1 || $scheduleItem->contract->status == 3 || $scheduleItem->contract->status == 4) {  // просрочен или создан

                Log::channel('autopayment')->info('Месяц оплаты: ' . $scheduleItem->payment_date . ' id: ' . $scheduleItem->id . ' Контракт: ' . $scheduleItem->contract_id . ' user_id: ' . $scheduleItem->user_id . ' - contract->status ' . $scheduleItem->contract->status);
                Log::channel('autopayment')->info('Сумма ' . $scheduleItem->total .' остаток ' . $scheduleItem->balance );

                $request = request(); // ???
                $personalAccount = $scheduleItem->buyer->settings->personal_account;  // деньги в лс
                $cards = $scheduleItem->buyer->cardsActive;  // все карты

               // $currentPayment = (float)$scheduleItem->total;     // всего
                $balance = (float)$scheduleItem->balance;      // остаточный ожидаемый ( остаток долга за месяц ) в сумах
                $isOperation = false;

                // если есть на лс деньги, закрываем месяц с него
                if ($personalAccount > 0) {
                    Log::channel('autopayment')->info('списание с ЛС ' . $personalAccount);

                    if ($personalAccount > $balance) {  // если денег на лс больше, чем долг
                        $amount = $balance;  // списали
                       // $personalAccount -= $balance;
                        $balance = 0;
                    } else {
                        $amount = $personalAccount;  // списали
                        $balance -= $personalAccount;
                       // $personalAccount = 0;
                    }

                    // сохраняем расчеты
                    $scheduleItem->balance -= $amount;  // ост долга в график
                    Log::channel('autopayment')->info('остаток долга за месяц ' . $balance);

                    // если закрыли месяц
                    if ( $balance == 0 ) {
                        $scheduleItem->buyer->settings->balance += $scheduleItem->price;   // увеличиваем лимит на платеж без маржи
                        $scheduleItem->buyer->settings->save();
                        $scheduleItem->status = 1;
                        $scheduleItem->paid_at = time();
                        $scheduleItem->contract->status = 1;  // больше не просрочник
                        Log::channel('autopayment')->info('месяц закрыт ');
                    }


                    if ( !$scheduleItem->save() ) {
                        Log::channel('autopayment')->info('ERROR. NOT SAVED sch ' . $scheduleItem->id . ' ' . $scheduleItem->buyer->id);
                        Log::channel('cronpayment')->info('ERROR. NOT SAVED sch ' . $scheduleItem->id . ' ' . $scheduleItem->buyer->id);
                    }else {
                        Log::channel('autopayment')->info('scheduleItem saved ');
                    }

                    $scheduleItem->buyer->settings->personal_account -= $amount;  // списываем деньги с лс
                    if(!$scheduleItem->buyer->settings->save()){
                        Log::channel('cronpayment')->info('ERROR. NOT SAVED buyer->settings ' . $scheduleItem->id . ' ' . $scheduleItem->buyer->id);
                        Log::channel('autopayment')->info($scheduleItem->buyer->settings);
                        Log::channel('cronpayment')->info($scheduleItem->buyer->settings);
                    }

                    Log::channel('autopayment')->info('остаток денег на ЛС ' . $scheduleItem->buyer->settings->personal_account);

                    // отнимаем списанную сумму из общего долга
                    $scheduleItem->contract->balance -= $amount;
                    if ($scheduleItem->contract->balance == 0) {
                        $scheduleItem->contract->status = 9;  //  закрываем контракт
                    }
                    if(!$scheduleItem->contract->save()){
                        Log::channel('autopayment')->info('ERROR. NOT SAVED contract  ' . $scheduleItem->id . ' ' . $scheduleItem->buyer->id);
                        Log::channel('cronpayment')->info('ERROR. NOT SAVED contract  ' . $scheduleItem->id . ' ' . $scheduleItem->buyer->id);
                        Log::channel('autopayment')->info($scheduleItem->contract);
                        Log::channel('cronpayment')->info($scheduleItem->contract);
                    }

                    $payment = new Payment;
                    $payment->schedule_id = $scheduleItem->id;
                    $payment->type = 'auto';
                    $payment->order_id = $scheduleItem->contract->order_id;
                    $payment->contract_id = $scheduleItem->contract->id;

                    $payment->amount = $amount;

                    $payment->user_id = $scheduleItem->buyer->id;
                    $payment->payment_system = 'ACCOUNT';
                    $payment->status = 1;
                    $payment->save();

                    Log::channel('autopayment')->info('списали c ЛС: ' . $payment->amount);

                    if ($isOperation) {

                        $request->merge([
                            'amount' => $payment->amount,
                            'buyer_id' => $payment->user_id,
                            'contract_id' => $payment->contract_id,
                            'schedule_id' => $scheduleItem->id,
                            'debt' => $balance,
                            'lc' => true,
                        ]);

                        $send = SmsHelper::sendSmsPayment($request);
                        Log::channel('autopayment')->info('sms account payment sended ' . $send);

                    }

                }

                // если еще остался незакрытый долг, списываем с карты
                if ($balance > 0) {
                    $count = sizeof($cards);   // цикл по всем картам -
                    Log::channel('autopayment')->info('Всего карт ' . $count);
                    for ($c = 0; $c < $count; $c++) {
                        if ($balance > 0) {   // если еще есть долг
                            $card = $cards[$c];
                            if($card->status !==1) continue;  // пропускаем неактивные карты

                            $request->merge(['buyer_id' => $scheduleItem->buyer->id,
                                'schedule_id' => $scheduleItem->id,
                                'order_id' => $scheduleItem->contract->order_id,
                                'contract_id' => $scheduleItem->contract->id,
                                'payment_type' => 'auto']);

                            $cardController = new CardController();
                            $request->merge(['card_id' => $card->id, 'info_card' => ['token' => $card->token, 'card_id' => $card->id,
                                'card_number' => $card->card_number,
                                'card_valid_date' => $card->card_valid_date]]);

                            $result = $cardController->balance($request);

                            if(!isset($result['result'])) continue;
                            if($result['result']['status']=='error') continue;

                            // ИСКЛЮЧИТЬ ИЗ СПИСКА ТЕ КАРТЫ, КОТОРЫЕ ЗАБЛОЧЕНЫ ИЛИ ОТКЛЮЧЕНО СМС ИНФОРМИРОВАНИЕ
                            Log::channel('autopayment')->info('карта ' . $c . '. смс информирование ' . $result['result']['state']);
                            if ($result['result']['state'] === -1) continue; // отключено смс информирование
                            if ($result['result']['state'] === -6) continue; // хз что такое, но оно нам не надо, в документации нет**

                            if ($result) {
                                //$availableBalance = $result['data']['balance'] / 100;  // для узкарда
                                $availableBalance = isset($result['result']['balance']) ? $result['result']['balance'] / 100 : 0;  // для Universal
                                Log::channel('autopayment')->info('карта ' . $c . '. Card availableBalance - cумма на карте  ' . $availableBalance);

                                if ($availableBalance > 0) {          // если на карте есть деньги - 1000 сум поставить??
                                    if ($availableBalance > $balance) {           // если хватает закрыть месяц
                                        $sum = $balance;
                                        $request->merge(['sum' => $balance]);

                                        Log::channel('autopayment')->info('перед оплатой FULL ' . $scheduleItem->id . ' ' . $scheduleItem->buyer->id);
                                        Log::channel('cronpayment')->info('перед оплатой FULL ' . $scheduleItem->id . ' ' . $scheduleItem->buyer->id);

                                        $result = $cardController->payment($request); // оплата

                                        Log::channel('autopayment')->info('после оплаты FULL ' . $scheduleItem->id . ' ' . $scheduleItem->buyer->id);
                                        Log::channel('cronpayment')->info('после оплаты FULL ' . $scheduleItem->id . ' ' . $scheduleItem->buyer->id);


                                        if ($result['status'] == 'success') {  // если списалось
                                            $balance = 0;
                                            $scheduleItem->contract->status = 1;  // больше не просрочник
                                            $isOperation = true;

                                        }else{
                                            Log::channel('cronpayment')->info($result);

                                            switch($result['data']['error']['code']){
                                                case '100':
                                                case '109':
                                                case '-31101':
                                                    CardHelper::changeStatus($card,3);
                                                    Log::channel('autopayment')->info('ERROR: '. $result['error']['code']. ' DISABLE CARD ' . $card->id . ' ' . $scheduleItem->buyer->id);

                                            }

                                        }

                                    } else {
                                        $sum = $availableBalance;    // если не хватает, списываем то, что есть
                                        $request->merge(['sum' => $availableBalance]);

                                        Log::channel('autopayment')->info('перед оплатой PART ' . $scheduleItem->id . ' ' . $scheduleItem->buyer->id);
                                        Log::channel('cronpayment')->info('перед оплатой PART ' . $scheduleItem->id . ' ' . $scheduleItem->buyer->id);

                                        $result = $cardController->payment($request); // оплата

                                        Log::channel('autopayment')->info('после оплаты PART ' . $scheduleItem->id . ' ' . $scheduleItem->buyer->id);
                                        Log::channel('cronpayment')->info('после оплаты PART ' . $scheduleItem->id . ' ' . $scheduleItem->buyer->id);


                                        if ($result['status'] == 'success') {     // если списалось
                                            Log::channel('autopayment')->info('карта ' . $c . '. ' . $balance . ' - ' . $availableBalance . ' = ' . ($balance - $availableBalance));
                                            $balance -= $availableBalance;            // остаток долга
                                            $isOperation = true;
                                        }else{
                                            Log::channel('cronpayment')->info($result);
                                        }

                                    }

                                    // если списали с карты
                                    if ($isOperation) {
                                        $scheduleItem->balance = $balance;  // ост долга в график  ??  проверить
                                        Log::channel('autopayment')->info('остаток долга в графике за месяц ' . $balance);

                                        $scheduleItem->contract->balance -= $sum;
                                        if ($scheduleItem->contract->balance == 0) {
                                            $scheduleItem->contract->status = 9;  //  закрываем контракт
                                        }

                                        if(!$scheduleItem->contract->save()){
                                            Log::channel('autopayment')->info('ERROR. NOT SAVED contract  ' . $scheduleItem->id . ' ' . $scheduleItem->buyer->id . ' row: ' .  __LINE__ );
                                            Log::channel('cronpayment')->info('ERROR. NOT SAVED contract  ' . $scheduleItem->id . ' ' . $scheduleItem->buyer->id . ' row: ' .  __LINE__);
                                            Log::channel('autopayment')->info($scheduleItem->contract);
                                            Log::channel('cronpayment')->info($scheduleItem->contract);
                                        }

                                        if ($balance == 0) {               // если закрыли месяц
                                            $scheduleItem->buyer->settings->balance += $scheduleItem->price;   // увеличиваем лимит на платеж без маржи
                                            if(!$scheduleItem->buyer->settings->save()){
                                                Log::channel('autopayment')->info('ERROR. NOT SAVED buyer->settings ' . $scheduleItem->id . ' ' . $scheduleItem->buyer->id . ' row: ' .  __LINE__);
                                                Log::channel('cronpayment')->info('ERROR. NOT SAVED buyer->settings ' . $scheduleItem->id . ' ' . $scheduleItem->buyer->id . ' row: ' .  __LINE__);
                                                Log::channel('autopayment')->info($scheduleItem->buyer->settings);
                                                Log::channel('cronpayment')->info($scheduleItem->buyer->settings);
                                            }
                                            $scheduleItem->status = 1;
                                            $scheduleItem->paid_at = time();
                                            Log::channel('autopayment')->info('месяц закрыт ');
                                        }
                                        if (!$scheduleItem->save()) {
                                            Log::channel('autopayment')->info('ERROR. NOT SAVED ' . $scheduleItem->id . ' ' . $scheduleItem->buyer->id . ' row: ' .  __LINE__);
                                            Log::channel('cronpayment')->info('ERROR. NOT SAVED ' . $scheduleItem->id . ' ' . $scheduleItem->buyer->id . ' row: ' .  __LINE__);
                                            Log::channel('autopayment')->info($scheduleItem);
                                            Log::channel('cronpayment')->info($scheduleItem);
                                        }else {
                                            Log::channel('autopayment')->info('scheduleItem saved ');
                                        }
                                        Log::channel('autopayment')->info('списали с карты: ' . $sum);
                                        //Log::channel('autopayment')->info('sms ');
                                        $request->merge([
                                            'amount' => $sum,
                                            'buyer_id' => $scheduleItem->buyer->id,
                                            'contract_id' => $scheduleItem->contract->id,
                                            'schedule_id' => $scheduleItem->id,
                                            'debt' => $balance,
                                            'lc' => false,
                                        ]);
                                        //Log::channel('autopayment')->info('sms card payment');

                                        $send = SmsHelper::sendSmsPayment($request);
                                        Log::channel('autopayment')->info('sms card payment sended ' . $send);
                                    }
                                }
                            } else {
                                Log::channel('autopayment')->info('card.register поломался у Universal');
                            }

                        }else{
                            break;
                        }
                    }
                }

                // добавление баллов, если нет просрочки
                if($isOperation) PaycoinHelper::addBall($scheduleItem);

                return true; // $scheduleItem->toJson();

            }

            Log::channel('autopayment')->info($scheduleItem->contract->id . ' не попал  - контр статус : ' . $scheduleItem->contract->status);
        }

        Log::channel('autopayment')->info('contract not found'); // $scheduleItem->contract->id . ' не попал  - контр статус : ' . $scheduleItem->contract->status);

        return false;

    }

    /**
       новый алгоритм - расчеты при списании

        amount сумма списания в суммах
        payment_type - откуда списали, карта или ЛС

     */
    public static function correctPayment(){

        $payments = CronPayment::where('status', 0)->get();

        foreach($payments as $payment){

            // раскомментировать после корректировки бд  // сначала нужно все предыдущие исправить !! а то будет неверно считать
            // проверка, если списано с ЛС, есть ли пополнения на эту сумму
            /*$receip_sum = 0;  // сумма пополнений
            $took_sum = 0;  // сумма списаний с ЛС
            if($payment->type ===2 ){
                // все пополнения клиента
                $receipts = Payment::where(['user_id' => $payment->user_id, 'status' => 1])->get();

                foreach($receipts as $receip){
                    // сумма всех пополнений
                    if($receip->type == 'user') {
                        $receip_sum += $receip->amount;
                    }
                    // сумма всех списаний с ЛС
                    if($receip->type == 'auto' && $receip->payment_system == 'ACCOUNT'){
                        $took_sum += $receip->amount;
                    }
                }

                $diff = $receip_sum - $took_sum;
                if($diff > 0 && $diff < $payment->amount){  // если сумма списания с ЛС прислали больше, чем ост пополнений
                    $payment->status = 5; // помечаем недоразумение и ничего не делаем
                    $payment->save();
                    continue;
                }



            }*/

            $sct= 0;  // для учета хотя бы одного погашения
            $total_debt= 0;  // сумма общего долга клиента
            $transaction_id = '';
            $uuid = '';
            $card_type = 'ACCOUNT';

            Log::channel('cron_payments')->info($payment);
            $total = $amount = $payment->amount;  // списанная сумма

            $lc = $payment->type ===2 ? true : false; // ЛС или карта
            $type = $lc ? ' ЛС ' : ' карты ';

            if(!$lc){
                //if($payment->type ===1){  // обычное списание
                if(in_array($payment->type, [1,2])){  // обычное списание
                    // парсим response
                    $response = json_decode($payment->response);
                    $transaction_id = $response->payment->id;
                    $uuid = $response->payment->uuid;
                    $card_type = CardHelper::checkTypeCard($response->card->number); // $card_type['name']; $card_type['type'] 1 или 2;
                    $card_type = $card_type['name'];
                    $card_id = $response->card->card_id??null;
                }
                if($payment->type === 3){  // pnfl списание
                    // парсим response
                    $response = json_decode($payment->response);
                    $transaction_id = $response->result->id;
                    $uuid = '';
                    $card_type = 'PNFL';
                    $card_id = null;
                }

            }

            $contracts = Contract::where('user_id' , $payment->user_id)->whereIn('status', [1,3,4])->with('buyer','schedule')->get();
            $c_id = [];
            foreach($contracts as $contract){
                $c_id[] = $contract->id;

                // сумма общего долга клиента
                if($schedules = $contract->schedule){
                    foreach($schedules as $schedule) {
                        if ($schedule->status == 1) continue;

                        $payment_date = strtotime($schedule->payment_date);
                        $now = strtotime(Carbon::now()->format('Y-m-d 23:59:59'));
                        if ($payment_date > $now) continue;

                        $total_debt += $schedule->balance;  // сумма общего долга клиента

                    }
                }

            }
            // если списали больше, чем он должен, то что делаем? ?????
            // if($payment->amount > $total_debt )

            $c_ids = json_encode($c_id);

            Log::channel('cron_payments')->info('Всего контрактов ' .$contracts->count() . ': ' . $c_ids );
            Log::channel('cron_payments')->info('**************************************************************************************');
            foreach($contracts as $contract){

                $phone = $contract->buyer->phone;
                $contract_amount = 0;

                if($amount > 0){
                    Log::channel('cron_payments')->info('сумма к началу операции контракта ' . $amount);
                    Log::channel('cron_payments')->info('******************* № ' .$contract->id . ' ****************************');
                    $schedules = $contract->schedule;

                    // пока месяц не закрыт, не переходить на след
                    foreach($schedules as $schedule){
                        if($schedule->status == 1) continue;

                        $payment_date = strtotime($schedule->payment_date);
                        $now = strtotime(Carbon::now()->format('Y-m-d 23:59:59'));
                        if($payment_date > $now) continue;

                        if($amount > 0) {
                            Log::channel('cron_payments')->info('**************');
                            Log::channel('cron_payments')->info('сумма к списанию за месяц ' . $amount);
                            Log::channel('cron_payments')->info('сумма долга за месяц ' . $schedule->balance);

                            if ($amount < $schedule->balance ) {  // если сумма к списанию меньше, чем долг за месяц

                                $sum = $amount;  // списали
                                $schedule->balance -= $amount;  // ост долга
                                $amount = 0;  //  ост суммы

                                // ВРЕМЕННАЯ КОРРЕКТИРОВКА --
                                if($schedule->balance == 0  || abs($schedule->balance) < 1){
                                    $schedule->status = 1;
                                    $schedule->paid_at = time();

                                    $contract->buyer->settings->balance += $schedule->price;  // если месяц закрыт, возвращаем баланс за месяц
                                    $contract->buyer->settings->save();
                                    Log::channel('cron_payments')->info('МЕСЯЦ ЗАКРЫТ ' . $schedule->id);
                                }
                            }else {                              // если меньше
                                $sum = $schedule->balance;  // списали
                                $amount -= $schedule->balance;  //  ост суммы
                                $schedule->balance = 0;
                                $schedule->status = 1;
                                $schedule->paid_at = time();

                                $contract->buyer->settings->balance += $schedule->price;  // если месяц закрыт, возвращаем баланс за месяц
                                $contract->buyer->settings->save();
                                Log::channel('cron_payments')->info('МЕСЯЦ ЗАКРЫТ ' . $schedule->id);
                            }

                            Log::channel('cron_payments')->info('$schedule->status : ' . $schedule->status);
                            Log::channel('cron_payments')->info('Контракт ' . $contract->id . ' c' . $type . 'списали ' . $sum . ' за месяц schedule->id ' . $schedule->id);
                            Log::channel('cron_payments')->info('ост долга за месяц : ' . $schedule->balance);
                            Log::channel('cron_payments')->info('$amount : ' . $amount);


                            Log::channel('cron_payments')->info('contract->balance ' . $contract->balance . ' -  ' . $sum . ' =  ' . ($contract->balance - $sum));
                            $contract->balance -= $sum;
                            if($contract->balance < 0) $contract->balance = 0; // временная коррекция, если сумма ушла в минус

                            Log::channel('cron_payments')->info('ост долга по контракту ' . $contract->balance);
                            if ($contract->balance == 0) {
                                $contract->status = 9;
                                Log::channel('cron_payments')->info('КОНТРАКТ ЗАКРЫТ ' . $contract->id);
                            }

                            try {
                                $schedule->save();
                                $contract->save();
                            } catch (\Exception $e) {
                                Log::channel('cron_payments')->info('!!! contract ' . $contract->id .  ' or schedule ' . $schedule->id . ' saving error !!!');
                                Log::channel('cron_payments')->info('contract amount : ' . $contract->balance .  ', schedule amount : ' . $schedule->balance);
                                Log::channel('cron_payments')->info($e);
                                Log::info($e);
                            }

                            $transaction = new Payment();
                            $transaction->contract_id  = $contract->id;
                            $transaction->order_id  = $contract->order->id;
                            $transaction->schedule_id   = $schedule->id;
                            $transaction->user_id   = $payment->user_id;
                            $transaction->card_id   = $card_id??null;
                            $transaction->amount  = $sum;
                            $transaction->type  = 'auto';
                            $transaction->payment_system  = $card_type;
                            $transaction->status  = 1;
                            $transaction->transaction_id  = $transaction_id;
                            $transaction->uuid  = $uuid;
                            $transaction->created_at  = time();
                            $transaction->updated_at  = time();
                            $transaction->save();

                            $sct ++;  // для учета хотя бы одного погашения
                            $contract_amount += $sum;  // списанная сумма за один контракт

                        }else{
                            break;  //  если сумма к списанию 0
                        }
                    }

                    //*********************************************************************************************************************
                    // проверим, если долг погашен, $contract->recovery = 0 (если нет доп договора взыскания) - 07.01.22
                        self::debtRelief($contract);

                    //*********************************************************************************************************************

                    if($contract_amount > 0){
                        Log::channel('cron_payments')->info('сумма для sms $contract_amount ' . $contract_amount);
                        // отправить смс клиенту - на каждый договор 1 смс, информация о сумме списания и долге
                        $request = new Request();
                        $request->merge([
                            'amount' => $contract_amount,  // сумма за один контракт
                            'buyer_id' => $contract->buyer->id,
                            'contract_id' => $contract->id,
                            'debt' => $contract->balance,  // остаток долга
                            'lc' => $lc,
                        ]);

                        $send = SmsHelper::sendSmsCronPayment($request);
                        Log::channel('cron_payments')->info('sms cron payment sended ' . $send);
                    }
                }else{
                    break;  //  если сумма к списанию 0
                }

            }
            /////////////////////////////////////////////////////////////////////
            // если деньги еще остались, закрываем долг по МИБ если он есть
            if($amount > 0){
                if($collect_cost = \App\Models\CollectCost::where(['user_id' => $payment->user_id, 'status' => 0])->first()){
                    $sum_collect = 0;
                    if($amount < $collect_cost->balance){
                        $collect_cost->balance -= $amount;  // остаток долга
                        $sum_collect = $amount;
                        $amount = 0;
                    }else{
                        $sum_collect = $collect_cost->balance;
                        $amount -= $collect_cost->balance;
                        $collect_cost->balance = 0;
                        $collect_cost->status = 1;  // долг погашен
                        $collect_cost->contract->recovery = 7; // контракт закрыт
                        $collect_cost->contract->save();
                    }
                    // ВРЕМЕННАЯ КОРРЕКТИРОВКА --
                    if(abs($amount) < 1) $amount = 0;
                    if(abs($collect_cost->balance) < 1){
                        $collect_cost->balance = 0;
                        $collect_cost->status = 1; // долг погашен
                        $collect_cost->contract->recovery = 7; // контракт закрыт
                        $collect_cost->contract->save();
                    }

                    if($collect_cost->save()){
                        // транзакция
                        $transaction = new Payment();
                        $transaction->user_id   = $payment->user_id;
                        $transaction->card_id   = $card_id??null;
                        $transaction->amount  = $sum_collect;
                        $transaction->type  = 'reimbursable';
                        $transaction->payment_system  = $card_type;
                        $transaction->status  = 1;
                        $transaction->transaction_id  = $transaction_id;
                        $transaction->uuid  = $uuid;
                        $transaction->save();

                        $sct ++;  // для учета хотя бы одного погашения
                    }
                }
            }
            //////////////////////////////////////////////////////////////////////////

            if($sct == 0 || $amount > 0 ){  // если нет погашений или сумма еще осталась
                $total -= $amount;
                $payment->status = $total != 0 ? 6 : 2 ;  // НЕ закрываем строчку крона // 2 - лишнее списание// 6 - УСПЕЛИ ЧАСТЬ РАСПРЕДЕЛИТЬ
                $payment->save();

                if($total == 0) {  // дубль списания
                    $transaction = new Payment();
                    $transaction->contract_id  = null;
                    $transaction->order_id  = null;
                    $transaction->schedule_id   = null;
                    $transaction->user_id   = $payment->user_id;
                    $transaction->card_id   = $card_id??null;
                    $transaction->amount  = $payment->amount;  // сумма лишней транзакции, не видна никому (в личном кабинете видна!!)
                    $transaction->type  = 'auto';
                    $transaction->payment_system  = $card_type;
                    $transaction->status  = 5;
                    $transaction->transaction_id  = $transaction_id;
                    $transaction->uuid  = $uuid;
                    $transaction->created_at  = time();
                    $transaction->updated_at  = time();
                    $transaction->save();
                }
                Log::channel('cron_payments')->info('ЛИШНЕЕ СПИСАНИЕ ИЛИ ИЗБЫТОЧНОЕ - cron_payment: ' . $payment->id);
                if($total != 0 ){  // 6 - избыточная сумма
                    if($amount > 1){
                        // часть лишних денег на ЛС
                        $payment->buyer->settings->personal_account += $amount;
                        $payment->buyer->settings->save();

                        // пополнение на остаток - пробный вариант
                        $transaction = new Payment();
                        $transaction->contract_id  = null;
                        $transaction->order_id  = null;
                        $transaction->schedule_id   = null;
                        $transaction->user_id   = $payment->user_id;
                        $transaction->card_id   = $card_id??null;
                        $transaction->amount  = $amount;
                        $transaction->type  = 'user';
                        $transaction->payment_system  = $card_type;
                        $transaction->status  = 1;
                        $transaction->transaction_id  = $transaction_id;
                        $transaction->uuid  = $uuid;
                        $transaction->created_at  = time();
                        $transaction->updated_at  = time();
                        $transaction->save();
                    }

                    Log::channel('cron_payments')->info('ЧАСТЬ РАСПРЕДЕЛИЛИ - : ' . $total . ', статок денег ' . $amount . ' закинули на ЛС: ' . $payment->user_id);

                }

            }else{
                $payment->status = 1;  // закрываем строчку крона
                $payment->save();
            }


        }

        return true;

    }



    /**
     *  сброс статуса задолжника
     */
    public static function debtRelief($contract){
        // сумма задолженности за контракт
        $debt = 0;
        if ($schedules = $contract->schedule) {
            foreach ($schedules as $schedule) {
                if ($schedule->status == 1) continue;
                $payment_date = strtotime($schedule->payment_date);
                $now = strtotime(Carbon::now()->format('Y-m-d 23:59:59'));
                if ($payment_date > $now) continue;
                $debt += $schedule->balance;  // сумма задолженности за контракт
            }
        }

        if($debt == 0){
            if(in_array($contract->status, [3,4])){
                //$contract->status = 1;                  // сбрасываем задолжность - !!нельзя , остается в черном списке навеки - 27.01.22
                if($contract->collcost == null){         // если нет доп договора взыскания
                    $contract->recovery = 0;             // сбрасываем процесс взыскания
                    Log::channel('payment')->info('процесс взыскания по договору ' . $contract->id . ' погашен ');
                }
                $contract->save();
                Log::channel('payment')->info('просрочка по договору ' . $contract->id . ' погашена ');
            }

        }

        return true;
    }


}
