<?php

namespace App\Http\Controllers\Core;

use App\Helpers\CardHelper;
use App\Helpers\TelegramHelper;
use App\Helpers\EncryptHelper;
use App\Helpers\PaymentHelper;
use App\Helpers\PushHelper;
use App\Helpers\SallaryHelpers;
use App\Helpers\TelegramInformer;
use App\Models\Buyer;
use App\Models\BuyerSetting;
use App\Models\CardPnfl;
use App\Models\CollectCost;
use App\Models\CronData;
use App\Models\CronInit;
use App\Models\CronPayment;
use App\Models\CronUsersDelays;
use App\Models\KatmScoring;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class CronController extends CoreController
{

    /*public function __construct()
    {
        parent::__construct();
    } */

    /**
     *
     * @return bool
     */
    public function run()
    {
        return true;

        /*   $log = '';
         Log::channel('jobs')->info('start automatic.payment.start');
           $scheduleList = PaymentHelper::getScheduleList();
           //Log::channel('jobs')->info($scheduleList->toJson());
           if ($scheduleList) {
               $count = sizeof($scheduleList);
               / * for($c=0; $c<$count; $c++){
                    $log = PaymentHelper::actionPayment($scheduleList[$c]);
                } * /
               Log::info('payment OK!');
           }
           PaymentHelper::actionDelayPayment();
           Log::channel('jobs')->info($log);
           Log::channel('jobs')->info('start automatic.payment.end');

           //Return data
           return true;*/
    }

    // инициализация данных для крона по просрочникам
    private function initCronData(&$cron_init)
    {

        Log::channel('autopayment_many_init')->info('START INIT');

        $starttime = microtime(true);

        // блокируем
        $cron_init->id = 1;
        $cron_init->init = 1;
        $cron_init->save();

        // на проде
         $date = date('Y-m-d 23:59:00',strtotime('+1 day')); //  запускать после 23:00

        // для теста просрочники до вечера сегодня 23:59:59
        // $date = date('Y-m-d 23:59:00',time());

        // старый рабочий запрос
        /*$users = Buyer::*/  /*with('paymentsDelays')*/
        /*select(DB::raw('u.id, SUM(cps.balance) + SUM(st.balance) AS balance'))
            ->from('users as u')
            ->leftJoin('collect_cost as ct', function ($query)  {
                $query->on('ct.user_id', 'u.id')->where('ct.status', 0)->where('ct.balance', '>', 0);
            })
            ->leftJoin('contracts as c', function ($query) {
                $query->on('c.user_id', 'u.id')->whereIn('c.status', [1, 3, 4]);
            })
            ->leftJoin('contract_payments_schedule as cps', function ($query) use ($date) {
                $query->on('cps.contract_id', 'c.id')->where('cps.status', 0)->where('cps.payment_date', '<', $date);
            })

            ->where('cps.payment_date', '<', $date)
            ->where('cps.status', 0)
            ->whereIn('c.status', [1, 3, 4])
            ->groupBy('u.id');

        $users = $users->get();*/

        // 26.01.22 - новый запрос , прибавляем и долги по МИБУ - !!!
        $u = Buyer::query()->fromSub(function ($query) {
            $collect_costs = CollectCost::
            select(DB::raw('ct.user_id AS id, 0 AS balance , ifnull(sum(ct.balance), 0) AS ct_balance'))
                ->from('collect_cost AS ct')
                ->where('ct.status', 0)
                ->groupBy('ct.user_id');
            $date = date('Y-m-d 23:59:00',strtotime('+1 day')); //  запускать после 23:00
            $query->select(DB::raw('u.id, SUM(cps.balance) AS balance, 0 AS ct_balance'))
                ->from('users AS u')
                ->leftJoin('contracts AS c', function ($query) {
                    $query->on('c.user_id', 'u.id')->whereIn('c.status', [1, 3, 4]);
                })
                ->leftJoin('contract_payments_schedule AS cps', function ($query) use ($date) {
                    $query->on('cps.contract_id', 'c.id')->where('cps.status', 0)->where('cps.payment_date', '<', $date);
                })
                ->where('cps.payment_date', '<', $date)
                ->where('cps.status', 0)
                ->union($collect_costs)
                ->groupBy('u.id');

        }, 'sub');

        $u->select(DB::raw('id, ifnull(SUM(balance), 0) + ifnull(sum(ct_balance), 0) AS total_balance'))
            ->groupBy('id');
        $users = $u->get();


        DB::table('cron_users_delays')->truncate(); // таблица просрочников

        // сохраняем всех просрочников
        foreach ($users as $user) {
            $cud = new \App\Models\CronUsersDelays();
            $cud->user_id = $user->id;
            $cud->balance = $user->total_balance; //$user->balance;
            $cud->pa_amount = $user->total_balance; //$user->balance;
            $cud->status = 0;
            $cud->save();
        }

        $ucnt = $cud->id;

        /* $cd = CronData::get();
        $cs = [];
        foreach ($cd as $item){
            $cs[$item->range]=$item->status;
        } */

        DB::table('cron_data')->truncate(); // таблица диапазонов

        if ($ucnt > 0) {
            $users_range = ceil($ucnt / 4);
            $k = 0;
            for ($r = 0; $r < 4; $r++) {
                $cron_data = new CronData();
                $cron_data->range = $r + 1;
                if($r>0) $k++;
                $cron_data->start = $r * $users_range + 1 + $k;
                $cron_data->end = $r * $users_range + $users_range + 1 + $k;
                $cron_data->status = 0; //$cs[$r];
                $cron_data->cnt = 0;
                $cron_data->save();
            }

            Log::channel('autopayment_many_init')->info('buyers in range: ' . $users_range);
            Log::channel('autopayment_many_init')->info('buyers count: ' . count($users));

            // освобождаем
            $cron_init->init = 0;
            $cron_init->save();

            $endtime = microtime(true);
            $dt = $endtime - $starttime;

            Log::channel('autopayment_many_init')->info('END INIT. time: ' . number_format($dt, 2, '.', ' ') . ' sec');

            return true;

        }

        return false;

    }

    public function getBuyersDelay(Request $request){


        Log::channel('autopayment_many_init')->info('START INIT API');

        $date = date('Y-m-d 23:59:00',strtotime('+1 day')); //  запускать после 23:00

        // для теста просрочники до вечера сегодня 23:59:59
        // $date = date('Y-m-d 23:59:00',time());

        $users = Buyer::with('cards','cardsPnfl','pnflContract','personalAccount')
            ->select(DB::raw('u.id, SUM(cps.balance) AS balance'))
            ->from('users as u')
            ->leftJoin('contracts as c', function ($query) {
                $query->on('c.user_id', 'u.id')->whereIn('c.status', [1, 3, 4]);
            })
            ->leftJoin('contract_payments_schedule as cps', function ($query) use ($date) {
                $query->on('cps.contract_id', 'c.id')->where('cps.status', 0)->where('cps.payment_date', '<', $date);
            })
            ->where('cps.payment_date', '<', $date)
            ->where('cps.status', 0)
            ->whereIn('c.status', [1, 3, 4])
            ->groupBy('u.id')

        //->take(100)
        ;

        $users = $users->get();

       // dd($users);

        return $users;



    }

    // 27-22.08.2021 WORK VERSION - автосписание сразу по нескольким cron
    public function autopaymentCron(Request $request)
    {

        $telegramBot = new TelegramInformer(env('MONITORING_REPORT_TELEGRAM_BOT_TOKEN'), env('MONITORING_REPORT_CHAT_ID'));


        if(!$request->has('range')){
            Log::channel('autopayment_many')->info('ERROR range not set!');
            exit;
        }

        $channel = 'autopayment_many_' . $request->range;

        $test = false;

        $starttime = microtime(true);

        $hour = date('H');
        $min = date('i');

        $result = true;
        $init = false;
        if ($cron_init = CronInit::where('id', 1)->first()) {




            Log::channel($channel)->info('-->CRON INIT STATUS=' . $cron_init->init . ' by range='.$request->range);

            if ($cron_init->init == 1) {
                Log::channel($channel)->info('<--STOP INIT IS RUNNING by range='.$request->range);
                exit;
            }

            // проверка актуальности cron
            /* $day = date('d',strtotime($cron_init->created_at));
            $cur_day = date('d');
            if($day!=$cur_day){ // работает

                dd('!date ' .$day . ' ' . $cur_day);
                $result = $this->initCronData($cron_init);
                // $init = $result;
                exit;
            } */

            //if ($hour >= 23 && $min >= 58 ) {
            if ($hour == 23 && $min >= 56 ) {
                if($request->range==1) {
                    $result = $this->initCronData($cron_init);
                    // $init = $result;
					exit;
			    }else{
                    Log::channel($channel)->info('<--STOP INIT IS RUNNING by range=1 exit for range='.$request->range);
                    exit;
                }
            }

        } else { // если нет записи в бд создать и произвести инициализацию
            $cron_init = new CronInit();
            if($request->range==1) {
                $result = $this->initCronData($cron_init);
                // $init = $result;
                exit;
            }else{
                Log::channel($channel)->info('<--STOP INIT IS RUNNING by range=1 exit for range='.$request->range);
                exit;
            }
        }


        $local_get = 0;
        $card_get = 0;
        $users_payment = 0;
		$users_count = 0;

        if (!$result) {
            Log::channel($channel)->info('<--STOP CRON NOT INIT ' . $request->range);
            exit;
        }

        // если пришло время и не инициализировано
        if (!$init && $hour == 23 && $min >= 55) {
            Log::channel($channel)->info('EXIT SCRIPT FOR INIT 23:55 range: ' . $request->range);
            exit;
        }

        $cronPaymentsCreated = 0;
        $personalAccountDecommissioned = 0;
        $checkedCardBalance = 0;
        $writeOffFromCard = 0;
        $createCronPayment = 0;
        $checkBalanceFromPnflCard = 0;
        $writeOffFromPnflCard = 0;
        $createCronPaymentForPnfl = 0;

        $cronRange = $request->range;

        $telegramBot->line('<b>' . __('Cron[' . $cronRange . ']: Started!') . '</b>');

        if ($request->has('range')) {
            if(!$cronData = CronData::where('status', 0)->where('range', $request->range)->first()){
                Log::channel($channel)->info('EXIT NO FREE RANGE DATA for range: ' . $request->range);
                exit;
            }

            $cronData->status = 1; // блок
            $cronData->cnt+=1;
            $cronData->save();
            sleep(rand(1,10));

            if( $cronData->cnt > 1 ){
                Log::channel($channel)->info('EXIT CRON COUNT > 1 cnt: ' . $cronData->cnt . ' NO FREE RANGE DATA for range: ' . $request->range);
                TelegramHelper::sendByChatId('1726060082','EXIT CRON COUNT > 1 cnt: ' . $cronData->cnt . ' NO FREE RANGE DATA for range: ' . $request->range);
                if( $cronData->cnt - 1 > 1 ) {
                    $cronData->cnt -= 1;
                    $cronData->save();
                }
                exit;
            }

            if( $cronData->cnt > 1 ){
                Log::channel($channel)->info('STOP cnt > 1 = ' . $request->cnt);
                exit;
            }

            $date = date('Y-m-d H:i:s');


            if ($buyers = CronUsersDelays::with('cards', 'personalAccount')->where('status', 0)->whereBetween('id', [$cronData->start, $cronData->end])->get()) {
                $cardController = new CardController();

                $telegramBot->line('<b>' . __('Cron[' . $cronRange . ']: Debtors count - ' . count($buyers)) . '</b>');

                //$date_from = date('Y-m-d 00:00:00',strtotime('-1 day'));

				$date_from = date('Y-m-d 23:58:00',strtotime('-1 day'));
				$date_to = date('Y-m-d 23:59:00');

                foreach ($buyers as $buyer) {

                    $min = date('i');
                    $hour = date('H');
                    if($hour==23 && ($min>=55 && $min<=59)) {

                        $endtime = microtime(true);
                        $dt = $endtime - $starttime;

                        $cronData->status = 0; // освободить
                        $cronData->save();

                        Log::channel($channel)->info('CRON STOP');
                        Log::channel($channel)->info('users count: ' . $users_count . ' users payments: ' . $users_payment );
                        Log::channel($channel)->info('SUM PAY ACCOUNT: ' . $local_get . ' CARD: ' . $card_get);
                        Log::channel($channel)->info('RANGE: ' . $request->range . ' time: ' . number_format($dt, 2, '.', ' ') . ' sec');

                        $telegramBot->line('<b>' . __('Cron[' . $cronRange . ']: Timeout!') . '</b>');
                        $telegramBot->line('<b>' . __('Cron[' . $cronRange . ']: Stopped!') . '</b>');
                        $telegramBot->send();
                        exit;
                    }


                    if( $buyer->balance == 0 ){
                        Log::channel($channel)->info($buyer->user_id . ' ПРОСРОЧКА УЖЕ ПОЛНОСТЬЮ ПОГАШЕНА!!!');
                        break;  // следующий клиент
                    }

                    $pay = false;
                    Log::channel($channel)->info('---');
                    Log::channel($channel)->info('ОБРАБОТКА ' . $buyer->user_id);

                    if(!$buyer->personalAccount){
                        Log::channel($channel)->info('ERROR. ' . $buyer->user_id . ' NO BuyerSettings');
                        continue;
                    }

                    $cron_pay = CronPayment::select(DB::raw('SUM(amount) sum'))->where('user_id',$buyer->user_id)->whereBetween('created_at',[$date_from,$date_to])->first();
                    if( $buyer->pa_amount <= $cron_pay->sum ){
						$buyer->status = 3; // попытка повторного списания
                        $buyer->save();
                        Log::channel($channel)->info('ПОПЫТКА ПОВТОРНОГО СПИСАНИЯ. ' . $buyer->user_id . ' статус: ' . $buyer->status . ' долг ' . $buyer->balance . ' оплачено: ' . $cron_pay->sum . ' уже было списание' );
                        //TelegramHelper::sendByChatId('1726060082', $date. ' ПОПЫТКА ПОВТОРНОГО СПИСАНИЯ PAYMENTS. ' . $buyer->user_id . ' статус: ' . $buyer->status . ' долг ' . $buyer->balance . ' оплачено: ' . $cron_pay->sum . ' уже было списание' );
                        continue;
                    }

                    // realtime проверка ЛС
                    $_buyer = BuyerSetting::select('id','user_id','personal_account')->where('user_id', $buyer->user_id)->first();
                    $prefix = $_buyer->personal_account >0 ? '>' : '=';
                    Log::channel($channel)->info('_buyer->personal_account ' . $prefix . ' ' . $_buyer->personal_account);
                    $personalAccount = $_buyer->personal_account; // $buyer->personalAccount->personal_account;

                    $balanceDelay = (float)$buyer->balance; // остаточный, ожидаемый ( остаток долга ) в сумах

                    Log::channel($channel)->info('Сумма на ЛС ' . $personalAccount . ' просрочка ' . $balanceDelay );

                    // если есть на Личном счету деньги, закрываем просрочку с него
                    if ($personalAccount > 0){ // && ($buyer->pa_date!=$buyer->personalAccount->updated_at || $buyer->pa_amount != $personalAccount) ) {

                        if ($personalAccount > $balanceDelay) {  // если денег на лс больше, чем просрочка
                            $amount = $balanceDelay;     // сумма списания
                            $balanceDelay = 0;
                        } else {
                            $amount = $personalAccount;  // сумма списания
                            $balanceDelay -= $personalAccount; // остаток просрочки
                        }

                        $cronPayment = new CronPayment();
                        $cronPayment->user_id = $buyer->user_id;
                        $cronPayment->amount = $amount;
                        $cronPayment->type = 2; // CronPayment::PAYMENT_ACCOUNT;
                        $cronPayment->status = 0;

						if($buyer->balance - $amount >=0){

                            if($hour<2) sleep(1);

                            if ($cronPayment->save()) { // сохраняем

                                $cronPaymentsCreated++;

								Log::channel($channel)->info($buyer->user_id . ' СПИСАНИЕ с ЛС сумма: ' . $amount);
								Log::channel($channel)->info($buyer->user_id . ' ОСТАТОК долга ' . $balanceDelay);

								$buyer->balance -= $amount; // ост долга
								//$buyer->pa_amount = $amount; // списано
								//$buyer->pa_date = $buyer->personalAccount->updated_at;

								if($buyer->save()) {

                                    $_buyer->personal_account -= $amount;
                                    Log::channel($channel)->info( $buyer->user_id . ' остаток на ЛС ' . $_buyer->personal_account . ' списали ' .  $amount);

                                    if(!$_buyer->save()){
                                        Log::channel($channel)->info($buyer->user_id . ' ERROR save payment personal account from account');
                                        continue;
                                    }

                                    $personalAccountDecommissioned++;

                                }else{
                                    Log::channel($channel)->info($buyer->user_id . ' ERROR save payment from account');
                                    continue;
                                }

								$local_get += $amount;

								$pay = true;

							} else {
								Log::channel($channel)->info($buyer->user_id . ' ERROR save payment from account');
								continue;
							}

						}else{

						    $ds = $buyer->balance - $amount;
							Log::channel($channel)->info($buyer->user_id . ' ERROR ЛС ' . $buyer->balance . '  ' . $amount . ' ' . $ds);

						}


                    } // СПИСАНИЕ С ЛС

                    // если закрыли просрочку
                    if ($balanceDelay == 0 ) {

                        if($hour<2) sleep(1);

                        $buyer->status = 1;
                        $buyer->save();
                        Log::channel($channel)->info($buyer->user_id . ' ДОЛГ ПОГАШЕН ПОЛНОСТЬЮ FROM ACCOUNT status: ' . $buyer->status . ' осталось: ' . $buyer->balance);
                        continue;  // больше не просрочник, переходим к следующему клиенту
                    }

                    // если остался долг, пытаемся списать со всех карт клиенты
                    if ($buyer->cards) { // СПИСАНИЕ С КАРТ

                        foreach ($buyer->cards as $card) {

                            if( $buyer->balance == 0 ){
                                Log::channel($channel)->info($buyer->user_id . ' ПРОСРОЧКА УЖЕ ПОЛНОСТЬЮ ПОГАШЕНА');
                                break;  // следующий клиент
                            }

                            $card_state = '';
                            if( in_array($card->status,[0,2] ) ) $card_state = ' ПРОПУСК';

                            Log::channel($channel)->info('Карта ' . $card->id . ' status: ' . $card->status . ' ' . $buyer->user_id . $card_state);

                            /*if( $card->status != 1 ) { // карта не активна для списания
                                continue;
                            }*/

                            if( (int)$card->status == 0 ) {
                                Log::channel($channel)->info('ПРОПУСК. карта ' . $card->id . ' status: ' . $card->status . ' ' . $buyer->user_id );
                                continue;
                            }

                            if( (int)$card->status == 2 ) {
                                Log::channel($channel)->info('ПРОПУСК. карта ' . $card->id . ' status: ' . $card->status . ' ' . $buyer->user_id );
                                continue;
                            }

                            /* if( (int)$card->status == 3 ) {
                                Log::channel($channel)->info('ПРОПУСК. карта ' . $card->id . ' status: ' . $card->status . ' ' . $buyer->user_id );
                                continue;
                            } */

                            $request->merge([
                                'is_cron' => true,
                                'buyer_id' => $buyer->user_id,
                                'buyer' => $buyer,
                                'card_id' => $card->id,
                                'info_card' => [
                                    'token' => $card->token,
                                    'card_id' => $card->id,
                                    'card_number' => EncryptHelper::decryptData($card->card_number),
                                    'card_valid_date' => EncryptHelper::decryptData($card->card_valid_date)
                                ]
                            ]);

                            try {

                                $result = $cardController->balance($request); // получаем баланс с карты

                                $checkedCardBalance++;

                                //Log::channel($channel)->info('результ от баланса');
                                //Log::channel($channel)->info($result);

                                if (isset($result['result']['status']) && $result['result']['status'] == 'success') {

                                    $cardBalance = $result['result']['balance'] ?? 0;
                                    $cardBalance /= 100; // баланс на карте в тийинах переводим в суммы

                                    Log::channel($channel)->info($buyer->user_id . ' карта ' . $card->id . '. сумма на карте  ' . $cardBalance . '. смс информирование ' . $result['result']['state']);

                                    if ($cardBalance > 0 && $balanceDelay > 0.01) {

                                        // ИСКЛЮЧИТЬ ИЗ СПИСКА ТЕ КАРТЫ, КОТОРЫЕ ЗАБЛОЧЕНЫ ИЛИ ОТКЛЮЧЕНО СМС ИНФОРМИРОВАНИЕ
                                        if ($result['result']['state'] === -1) continue; // отключено смс информирование
                                        if ($result['result']['state'] === -6) continue; // хз что такое, но оно нам не надо, в документации нет**

                                        if (!$result) {
                                            Log::channel($channel)->info('card.register поломался у Universal');
                                            continue;
                                        }

                                        if ($cardBalance > $balanceDelay) { // погашаем всю задолженность
                                            $amount = $balanceDelay;
                                            $request->merge(['sum' => $balanceDelay]);
                                            $balanceDelay = 0;
                                            Log::channel($channel)->info($buyer->user_id . ' к списанию: ' . $amount );

                                        } else {
                                            $amount = $cardBalance;    // если не хватает, списываем то, что есть
                                            $request->merge(['sum' => $cardBalance]);
                                            $balanceDelay -= $amount; // ост долга
                                            Log::channel($channel)->info($buyer->user_id . ' ОСТАТОК ПРОСРОЧКИ ' . $balanceDelay);

                                        }

                                        $request->merge(['is_cron'=>true]);

                                        try {

											$cron_pay = CronPayment::select(DB::raw('SUM(amount) sum'))->where('user_id',$buyer->user_id)->whereBetween('created_at',[$date_from,$date_to])->first();
											if( $buyer->pa_amount <= $cron_pay->sum ){
												$buyer->status = 3; // попытка повторного списания
												$buyer->save();
												Log::channel($channel)->info('ПОПЫТКА ПОВТОРНОГО СПИСАНИЯ. ' . $buyer->user_id . ' статус: ' . $buyer->status . ' долг ' . $buyer->balance . ' оплачено: ' . $cron_pay->sum . ' уже было списание' );
												TelegramHelper::sendByChatId('1726060082', $date. ' ПОПЫТКА ПОВТОРНОГО СПИСАНИЯ BEFORE PAYMENTS. ' . $buyer->user_id . ' статус: ' . $buyer->status . ' долг ' . $buyer->balance . ' оплачено: ' . $cron_pay->sum . ' уже было списание' );
												break;
											}

                                            $result = $cardController->payment($request); // оплата с карты

                                            $writeOffFromCard++;

                                            if ($result['status'] == 'success' || $result['status'] == 'true') {  // если списалось

                                                Log::channel($channel)->info($result);

                                                $result['data']['result']['card']['card_id'] = $card->id;

                                                $cronPayment = new CronPayment();
                                                $cronPayment->user_id = $buyer->user_id;
                                                $cronPayment->amount = $amount;
                                                $cronPayment->type = CronPayment::PAYMENT_CARD;
                                                $cronPayment->status = 0;
                                                $cronPayment->response = json_encode($result['data']['result'],JSON_UNESCAPED_UNICODE);

                                                $createCronPayment++;

                                                if ($cronPayment->save()) {

                                                    if($hour<2) sleep(1);

                                                    Log::channel($channel)->info($buyer->user_id . ' перед сохранением FROM CARD ' . $buyer->balance . ' ' . $amount);

                                                    try {
                                                        $buyer->balance -= $amount; // остаток долга в CronUsersDelays
                                                        if ($buyer->balance == 0) $buyer->status = 1; // полностью погашен

                                                        if($buyer->balance<0){
                                                            // $buyer->balance = 0;
                                                            Log::channel($channel)->info($buyer->user_id . ' Ошибка при сохранении FROM CARD <0 ' . $buyer->balance . ' ' . $amount);
                                                            TelegramHelper::sendByChatId('1726060082', $date. ' ПОПЫТКА ПОВТОРНОГО СПИСАНИЯ. FROM CARD <0 ' . $buyer->user_id . ' долг ' . $buyer->balance . ' сумма: ' . $amount  );
                                                        }

                                                        if (!$buyer->save()) {

                                                            Log::channel($channel)->info($buyer->user_id . ' Ошибка при сохранении FROM CARD ' . $buyer->balance . ' ' . $amount);
                                                        }
                                                    }catch (\Exception $e){

                                                        Log::channel($channel)->info($buyer->user_id . ' TRY Ошибка при сохранении FROM CARD ' . $buyer->balance . ' ' . $amount);
                                                        Log::channel($channel)->info($e);

                                                    }


                                                    $card_get += $amount;
                                                    $pay = true;

                                                    Log::channel($channel)->info($buyer->user_id .' ДОЛГ ПОГАШЕН ПОЛНОСТЬЮ FROM CARD карта ' . $card->id . '. долг ' . $buyer->balance . ' status: ' . $buyer->status /*balanceDelay*/ . '  на карте ' . $cardBalance . ' списано ' . $amount); // abs($balanceDelay - $cardBalance));

                                                    break;

                                                } else {
                                                    Log::channel($channel)->info($buyer->user_id . ' ERROR save payment from CARD баланс: ' . $buyer->balance . ' сумма: ' . $amount);
                                                    break;
                                                }


                                            } else {
                                                Log::channel($channel)->info('ошибка в результате оплаты');
                                                Log::channel($channel)->info($result);

                                                $buyer->status = 2; // нужно разобраться с клиентом, т.к. возможно уже списали средства
                                                if(!$buyer->save()){
                                                    Log::channel($channel)->info($buyer->user_id . ' Ошибка при сохранении FROM CARD Null Universal ' . $buyer->balance . ' ' . $amount);
                                                    break;
                                                }

                                                if(isset($result['status']) && $result['status']=='false') {
                                                    if($result['error']['code'] == -207){
                                                        Log::channel($channel)->info('ERROR от Universal, но списание могло произойти! ' . $buyer->user_id . ' карта ' . $card->id . '. долг ' . $buyer->balance . ' status: ' . $buyer->status . '  на карте ' . $cardBalance . ' возможно списано ' . $amount);
                                                        break;
                                                    }
                                                }


                                                if(is_null($result['status'])) {
                                                    $buyer->status = 2; // нужно разобраться с клиентом, т.к. возможно уже списали средства

                                                    if (!$buyer->save()) {

                                                        Log::channel($channel)->info($buyer->user_id . ' Ошибка при сохранении FROM CARD Null Universal ' . $buyer->balance . ' ' . $amount);
                                                    }
                                                    Log::channel($channel)->info('NULL от Universal, но списание могло произойти! ' . $buyer->user_id . ' карта ' . $card->id . '. долг ' . $buyer->balance . ' status: ' . $buyer->status . '  на карте ' . $cardBalance . ' возможно списано ' . $amount);
                                                    break;
                                                }

                                                /*if(!isset($result['data'])){
                                                    Log::channel($channel)->info('ERROR no data in result! 100 109 -31101');
                                                }*/

                                                if(isset($result['data'])) {

                                                    Log::channel($channel)->info('ERROR: ' . $result['data']['error']['code'] . ' BEFORE DISABLE CARD ' . $card->id . ' ' . $buyer->user_id);

                                                    switch ((int)$result['data']['error']['code']) {
                                                        case 100:
                                                        case 109:
                                                        case -31101:
                                                            CardHelper::changeStatus($card, 3);
                                                            Log::channel($channel)->info('ERROR: ' . $result['data']['error']['code'] . ' DISABLE CARD ' . $card->id . ' ' . $buyer->user_id);

                                                    }
                                                }else{
                                                    Log::channel($channel)->info("ERROR: not set array => result['data']['error']['code'] card: $card->id buyer: $buyer->user_id");

                                                }
                                                if(isset($result['result'])) {

                                                    Log::channel($channel)->info('ERROR: ' . $result['result']['response']['error']['code'] . ' BEFORE DISABLE CARD ' . $card->id . ' ' . $buyer->user_id);

                                                    switch ((int)$result['result']['response']['error']['code']) {
                                                        case 100:
                                                        case 109:
                                                        case -31101:
                                                            CardHelper::changeStatus($card, 3);
                                                            Log::channel($channel)->info('ERROR: ' . $result['result']['response']['error']['code'] . ' DISABLE CARD ' . $card->id . ' ' . $buyer->user_id);

                                                    }
                                                }else{
                                                    Log::channel($channel)->info("ERROR: not set array => result['result']['response']['error']['code'] card: $card->id buyer: $buyer->user_id");

                                                }
                                            }


                                        }catch (\Exception $e){
                                            Log::channel($channel)->info('ERROR TRY при списании ' . $buyer->user_id . ' карта ' . $card->id);
                                            Log::channel($channel)->info($e);


                                            /* if(UniversalController::checkTransactionError($request)){

                                            } */

                                            $buyer->status = 2; // нужно разобраться с клиентом, т.к. возможно уже списали средства
                                            $buyer->save();

                                            Log::channel($channel)->info('NULL от Universal, но списание могло произойти! ' . $buyer->user_id . ' карта ' . $card->id . '. долг ' . $buyer->balance . ' status: ' . $buyer->status . '  на карте ' . $cardBalance . ' возможно списано ' . $amount);

                                            break;
                                        }



                                    } // $cardBalance > 0

                                }else { // $result['result']['status'] == 'success'
                                    Log::channel($channel)->info($result);
                                    Log::channel($channel)->info('ERROR status=error получение баланса на карте ' . $buyer->user_id  . ' card_id ' . $card->id);

                                    if(isset($result['result']) && isset($result['result']['response'])) {
                                        if(isset($result['result']['response']['error']['code'])){
                                            Log::channel($channel)->info('ERROR: ' . $result['result']['response']['error']['code'] . ' BEFORE DISABLE CARD ' . $card->id . ' ' . $buyer->user_id);
                                            switch ((int)$result['result']['response']['error']['code']) {
                                                case 100:
                                                case 109:
                                                case -31101:
                                                    CardHelper::changeStatus($card, 3);
                                                    Log::channel($channel)->info('ERROR: ' . $result['result']['response']['error']['code'] . ' DISABLE CARD ' . $card->id . ' ' . $buyer->user_id);
                                            }
                                        }
                                    }else{
                                        Log::channel($channel)->info("ERROR: not set array => result['result']['response']['error']['code'] card: $card->id buyer: $buyer->user_id");

                                    }

                                }


                            }catch (\Exception $e){
                                Log::channel($channel)->info('ERROR TRY при получении баланса ' . $buyer->user_id . ' карта ' . $card->id);
                                Log::channel($channel)->info($e);
                                continue;
                            }



                        } // foreach ($buyer->cards)

                        $cron_pnfl = (bool) env('CRON_PNFL', true);
                        if ($cron_pnfl) {
                            if($buyer->balance > 0) {

                                Log::channel($channel)->info('CARD_PINFL. ' . $buyer->user_id . ' долг ' . $buyer->balance );

                                if ($cardsPnfl = CardPnfl::with('pnflContract')->where('user_id', $buyer->user_id)->where('state', 1)->get() ) {



                                    foreach ($cardsPnfl as $item) {

                                        if($buyer->balance == 0 || $buyer->status==2) break;

                                        $cardRequest = new Request();
                                        $cardRequest->merge(['card_id'=>$item->card_id]);

                                        try {

                                            $result = UniversalPnflController::getBalance($cardRequest);

                                            $checkBalanceFromPnflCard++;

                                            if( $result['status'] && $result['balance']>0 ) {

                                                $cardBalance = $result['balance'];

                                                Log::channel($channel)->info('CARD_PINFL. ' . $buyer->user_id . ' карта ' . $item->card_id . '. сумма на карте  ' . $cardBalance );

                                                if ($buyer->balance > $cardBalance) {
                                                    $amount = $result['balance'];
                                                } else {
                                                    $amount = $buyer->balance;
                                                }

                                                $cardRequest->merge([
                                                    'amount' => $amount,
                                                    'contract_id' => $item->pnflContract->contract_id,
                                                ]);


                                                Log::channel($channel)->info($cardRequest);

                                                try{

                                                    $cron_pay = CronPayment::select(DB::raw('SUM(amount) sum'))->where('user_id',$buyer->user_id)->whereBetween('created_at',[$date_from,$date_to])->first();
                                                    if( $buyer->pa_amount <= $cron_pay->sum ){
                                                        $buyer->status = 3; // попытка повторного списания
                                                        $buyer->save();
                                                        Log::channel($channel)->info('ПОПЫТКА ПОВТОРНОГО СПИСАНИЯ. ' . $buyer->user_id . ' статус: ' . $buyer->status . ' долг ' . $buyer->balance . ' оплачено: ' . $cron_pay->sum . ' уже было списание' );
                                                        TelegramHelper::sendByChatId('1726060082', $date. ' ПОПЫТКА ПОВТОРНОГО СПИСАНИЯ BEFORE PAYMENTS PINFL. ' . $buyer->user_id . ' статус: ' . $buyer->status . ' долг ' . $buyer->balance . ' оплачено: ' . $cron_pay->sum . ' уже было списание' );
                                                        break;
                                                    }

                                                    $result = UniversalPnflController::payment($cardRequest);

                                                    $writeOffFromPnflCard++;

                                                }catch (\Exception $e){
                                                    Log::channel($channel)->info('ERROR TRY при списании PINFL ' . $buyer->user_id . ' карта ' . $item->card_id);
                                                    Log::channel($channel)->info($e);
                                                    continue;
                                                }

                                                Log::channel($channel)->info('CARD_PINFL. ' . $buyer->user_id . ' после оплаты' );
                                                //Log::channel($channel)->info($result);

                                                if ( $result['status'] ) {  // если списалось

                                                    $cronPayment = new CronPayment();
                                                    $cronPayment->user_id = $buyer->user_id;
                                                    $cronPayment->amount = $amount;
                                                    $cronPayment->type = CronPayment::PAYMENT_CARD_PINFL;
                                                    $cronPayment->status = 0;
                                                    $cronPayment->response = json_encode($result, JSON_UNESCAPED_UNICODE);

                                                    $createCronPaymentForPnfl++;

                                                    if ($cronPayment->save()) {

                                                        if($hour<2) sleep(1);

                                                        Log::channel($channel)->info($buyer->user_id . ' перед сохранением FROM PINFL CARD ' . $buyer->balance . ' ' . $amount);

                                                        $buyer->balance -= $amount; // остаток долга в CronUsersDelays
                                                        if ($buyer->balance == 0) $buyer->status = 1; // полностью погашен

                                                        if($buyer->balance<0){
                                                            // $buyer->balance = 0;
                                                            Log::channel($channel)->info($buyer->user_id . ' Ошибка при сохранении FROM PINFL CARD <0 ' . $buyer->balance . ' ' . $amount);
                                                            TelegramHelper::sendByChatId('1726060082', $date. ' ПОПЫТКА ПОВТОРНОГО СПИСАНИЯ. FROM PINFL CARD <0 ' . $buyer->user_id . ' долг ' . $buyer->balance . ' сумма: ' . $amount  );
                                                        }

                                                        if (!$buyer->save()) {
                                                            Log::channel($channel)->info($buyer->user_id . ' Ошибка при сохранении FROM PINFL CARD ' . $buyer->balance . ' ' . $amount);
                                                        }

                                                        $card_get += $amount;
                                                        $pay = true;

                                                        Log::channel($channel)->info('CARD_PINFL. ДОЛГ ПОГАШЕН ПОЛНОСТЬЮ FROM CARD PINFL ' . $buyer->user_id . ' карта ' . $card->id . '. долг ' . $buyer->balance /*balanceDelay*/ . '  на карте ' . $cardBalance . ' списано ' . $amount); // abs($balanceDelay - $cardBalance));

                                                    } else {
                                                        Log::channel($channel)->info('CARD_PINFL. ' . $buyer->user_id . ' ERROR save payment from CARD PINFL');
                                                        continue;
                                                    }

                                                }

                                            }else{
                                                Log::channel($channel)->info('CARD_PINFL. balance not work');
                                            }

                                        }catch (\Exception $e){
                                            Log::channel($channel)->info('ERROR TRY при получении баланса PINFL ' . $buyer->user_id . ' карта ' . $item->card_id);
                                            Log::channel($channel)->info($e);
                                            continue;
                                        }



                                    }

                                }
                            }
                        }


                    } // $buyer->cards

                    if($pay) $users_payment++;
                    $users_count++;

                } // foreach ($buyers)
            } // if($buyers)

            $cronData->status = 0; // освобождаем
            $cronData->quantity += 1;
            $cronData->cnt=0; // доп счетчик кронов
            $cronData->save();

        } // $request->has('range')

        $endtime = microtime(true);
        $dt = $endtime - $starttime;

        $telegramBot->line('<b>' . __('Cron[' . $cronRange . ']: Personal accounts decommissioned - ' . $personalAccountDecommissioned) . '</b>');
        $telegramBot->line('<b>' . __('Cron[' . $cronRange . ']: Cron payments created (write-off from account) - ' . $cronPaymentsCreated) . '</b>');
        $telegramBot->line('<b>' . __('Cron[' . $cronRange . ']: Cards balances checked - ' . $checkedCardBalance) . '</b>');
        $telegramBot->line('<b>' . __('Cron[' . $cronRange . ']: Write-offs from cards - ' . $writeOffFromCard) . '</b>');
        $telegramBot->line('<b>' . __('Cron[' . $cronRange . ']: Cron payments created (write-off from card) - ' . $createCronPayment) . '</b>');
        $telegramBot->line('<b>' . __('Cron[' . $cronRange . ']: PNFL cards balances checked - ' . $checkBalanceFromPnflCard) . '</b>');
        $telegramBot->line('<b>' . __('Cron[' . $cronRange . ']: Write-offs from PNFL cards - ' . $writeOffFromPnflCard) . '</b>');
        $telegramBot->line('<b>' . __('Cron[' . $cronRange . ']: Cron payments created (write-off from PNFL card) - ' . $createCronPaymentForPnfl) . '</b>');

        $telegramBot->send();

        Log::channel($channel)->info('users count: ' . $users_count . ' users payments: ' . $users_payment );
        Log::channel($channel)->info('SUM PAY ACCOUNT: ' . $local_get . ' CARD: ' . $card_get);
        Log::channel($channel)->info('RANGE: ' . $request->range . ' time: ' . number_format($dt, 2, '.', ' ') . ' sec');

        exit;

    }



    /** TEST */
    public function autopaymentTest(Request $request)
    {

        $starttime = microtime(true);

        switch ($request->range) {
            case 1:
                $start = 1;
                $end = 179;
                break;
            case 2:
                $start = 180;
                $end = 360;
                break;
            case 3:
                $start = 361;
                $end = 540;
                break;
            case 4:
                $start = 541;
                $end = 716;
                break;
            default:
                Log::channel('autopayment_many')->info('RANGE NOT FOUND');
                exit;

        }

        if ($users = CronUsersDelays::with('card')->whereBetween('id', [$start, $end])->get()) {

            $cardController = new CardController();

            $n = 0;
            foreach ($users as $user) {
                $n++;
                /*Log::channel('autopayment_many')->info('correct_payment_before');
                $this->sendPaymentData([]);
                Log::channel('autopayment_many')->info('correct_payment_after'); */

                if ($user->card) {

                    foreach ($user->card as $card) {
                        $request->merge([
                            'is_cron' => true,
                            'buyer_id' => $user->user_id,
                            'card_id' => $card->id, 'info_card' => ['token' => $card->token, 'card_id' => $card->id,
                                'card_number' => EncryptHelper::decryptData($card->card_number),
                                'card_valid_date' => EncryptHelper::decryptData($card->card_valid_date)]]);

                        $result = $cardController->balance($request);

                        Log::channel('autopayment_many')->info('RANGE ' . $request->range . ' npp: ' . $n . ' ' . $user->user_id . ' card_id: ' . $card->id . ' result:');
                        Log::channel('autopayment_many')->info($result);

                        if (isset($result['result']['status']) && $result['result']['status'] == 'success') {
                            Log::channel('autopayment_many')->info('RANGE ' . $request->range . ' npp: ' . $n . ' ' . $user->user_id . ' ' . $result['result']['balance']);
                        }

                    }

                    /* }else{
                    echo 'user_id: ' . $user->user_id . '<br>';
                    echo 'no card<br>'; */

                }

            }

        }

        $endtime = microtime(true);
        $dt = $endtime - $starttime;
        Log::channel('autopayment_many')->info('RANGE: ' . $request->range . ' npp: ' . $n . ' ' . $user->user_id . ' time: ' . number_format($dt, 2, '.', ' ') . ' sec');

        //echo 'time: ' . number_format($dt,2,'.',' ') . ' sec';

        exit;

    }


    /*public function sendPaymentData($data)
    {

        $ch = curl_init('https://cabinet.test.uz/ru/correct-payment');
        curl_setopt_array($ch, array(
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOSIGNAL => 1, //to timeout immediately if the value is < 1000 ms
            CURLOPT_TIMEOUT_MS => 20, //The maximum number of mseconds to allow cURL functions to execute
            CURLOPT_VERBOSE => 1,
            CURLOPT_HEADER => 1
        ));
        $out = curl_exec($ch);

        //-------------parse curl contents----------------

        //$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        //$header = substr($out, 0, $header_size);
        //$body = substr($out, $header_size);

        curl_close($ch);


    }*/

    // url функция
    public function correctPayment()
    {

        PaymentHelper::correctPayment();

    }

    public function pushTest(){

        echo '<pre>';
        $buyerInfo = Buyer::getInfo(226082);
        print_r($buyerInfo);

        $message = [
            'ru' => [
                'title' =>'Повышение уровня',
                'text' => "Поздравляем! Ваш уровень был повышен до Gold. Покупайте еще больше за меньшие деньги!",
            ],
            'uz'=>[
                'title' =>'Arzonroqqa xarid qiling',
                'text' => "Tabriklaymiz! Darajangiz Goldga oshdi! Endi yanada ko'proq mahsulotlarni yanada arzonroqqa xarid qiling!",
            ]
        ];

        $options = [
            'type' => PushHelper::TYPE_NEWS_ALL,
            'buyer' => $buyerInfo, // 226082,
            'title' => $message[$buyerInfo['lang']]['title'],
            'text' => $message[$buyerInfo['lang']]['text'],
            'id' => 0,

        ];

        $res = PushHelper::send($options);

        print_r($res);

        exit;

        $message = [
            'ru' => [
                'title' => 'Создан договор',
                'text' => "На ваше имя был сформирован договор на сумму 1 250 000 сум. Подтвердите договор в приложении.",
            ],
            'uz' => [
                'title' => 'Shartnoma shakllantirildi',
                'text' => "Sizning nomingizga 1 250 000 so'mga shartnoma shakllantirildi. Shartnomani tasdiqlash uchun ilovaga o'ting.",
            ]
        ];

        $buyerInfo = Buyer::getInfo(226082); // 215268
        print_r($buyerInfo);

        $options = [
            'type'=>PushHelper::TYPE_CONTRACT,
            'buyer' => $buyerInfo, // 226082,
            'id' => 'id_name',
            'title' => $message[$buyerInfo['lang']]['title'],
            'text' => $message[$buyerInfo['lang']]['text'],
        ];

       print_r( PushHelper::send($options) );

    }

    public function sallaryTest(Request $request){

        if(!$request->has('pinfl')){
            exit('pinfl not set!');
        }

        $salary_result = SallaryHelpers::getSallary($request);

        $filename = date('Y.m.d').'_sallary.csv';
        $file_catalog = iconv('utf-8','windows-1251',json_encode($salary_result,JSON_UNESCAPED_UNICODE));

        file_put_contents($filename,$file_catalog);

        if(file_exists($filename)) {
            header( 'Content-type: '. mime_content_type($filename));
            header( 'Content-Disposition: attachment; filename=' . $filename );
            readfile($filename);
            exit;
        }else{
            return redirect('404');
        }

    }

    public function katmParse(){

        echo 'start<br>';
        if($katm = KatmScoring::with('buyer')->get()){
            echo 'count: '.count($katm) . '<br>';
           // dd($katm);
            foreach ($katm as $item) {

                echo $item->id;
                if(!is_null($item->buyer->gender)) continue;

                if (!isset($item->response)) {
                    echo ' no response data!<br>';
                    continue;
                }

                $result = json_decode($item->response, true);
                if (!isset($result['report'])) {
                    echo ' no report data!<br>';
                    continue;
                }
                $gender = null;
                $result = $result['report']['client'];
                if (isset($result['gender'])) {
                    //print_r($result);
                    if (isset($result['birth_date'])) $item->buyer->birth_date = date('Y-m-d', strtotime($result['birth_date']));
                    switch ($result['gender']) {
                        case 'Ж':
                            $gender = 2;
                            break;
                        case 'М':
                            $gender = 1;
                            break;
                    }
                    echo $gender . '<br>';
                }else{
                    echo 'gender no set<br>';
                }
                $item->buyer->gender = $gender;
                $item->buyer->region = $result['region'] ?? null;
                $item->buyer->local_region = $result['local_region'] ?? null;
                if(!$item->buyer->save()){
                    echo 'error save buyer<br>';
                }
                echo $item->id . ' ' . $item->buyer->id . ' ' .  $gender . '<br>';
            }
        }
        echo 'end<br>';

    }




}
