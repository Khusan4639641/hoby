<?php

namespace App\Http\Controllers\Core;

use App\Helpers\TelegramHelper;
use App\Helpers\PaymentHelper;
use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\BuyerSetting;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class PaymeController extends Controller{
    //
    const ERROR_INTERNAL_SYSTEM         = -32400;
    const ERROR_INSUFFICIENT_PRIVILEGE  = -32504;
    const ERROR_INVALID_JSON_RPC_OBJECT = -32600;
    const ERROR_METHOD_NOT_FOUND        = -32601;
    const ERROR_INVALID_AMOUNT          = -31001;
    const ERROR_TRANSACTION_NOT_FOUND   = -31003;
    const ERROR_INVALID_ACCOUNT         = -31050;
    const ERROR_COULD_NOT_CANCEL        = -31007;
    const ERROR_COULD_NOT_PERFORM       = -31008;


    public function init(){

        $requestBody = file_get_contents('php://input');

        $request = json_decode($requestBody,true);

        Log::channel('payme')->info($requestBody);

        $key = 'Paycom:' . config('test.payme_key');

        // authorize session
        $headers = getallheaders();

        if (!$headers || !isset($headers['Authorization']) ||
            !preg_match('/^\s*Basic\s+(\S+)\s*$/i', $headers['Authorization'], $matches) ||
            base64_decode($matches[1]) != $key
        ){
            Log::channel('payme')->error($matches[1] . ' | ' . base64_decode($matches[1]) . ' | ' . $key);
            Log::channel('payme')->error('Ошибка -32504 Не достаточно привелегий при входе');
            $arr_error = array('ru'=>'Недостаточно привелегий при входе','en'=>'Not enough privileges to execute method','uz'=>'Usulni bajarishga huquqlar etarli emas');
            return $this->error(
                self::ERROR_INSUFFICIENT_PRIVILEGE,
                $arr_error,
                $request['method']
            );
        }
        // handle request
        switch ($request['method']) {
            case 'CheckPerformTransaction':
                return $this->CheckPerformTransaction($request);
                break;
            case 'CheckTransaction':
                return $this->CheckTransaction($request);
                break;
            case 'CreateTransaction':
                return $this->CreateTransaction($request);
                break;
            case 'PerformTransaction':
                return $this->PerformTransaction($request);
                break;
            case 'CancelTransaction':
                return $this->CancelTransaction($request);
                break;
            case 'ChangePassword':
                return $this->ChangePassword($request);
                break;
            default:
                Log::channel('payme')->warning('Ошибка 32601 не верно указан метод при инициализации');
                $arrError = array('ru'=>'Метод не найден','en'=>'Method doesnt exist','uz'=>'Amaliyotni bajarib bo`lmadi');
                return $this->error(
                    self::ERROR_METHOD_NOT_FOUND,
                    $arrError,
                    $request['method']
                );
                break;
        }
    }

    private function CheckPerformTransaction($request){
        Log::channel('payme')->info("CheckPerformTransaction: \n Request: \n".print_r($request, 1));
        //Проверка на лицевой счет
       //$buyer = Buyer::find($request['params']['account']['client_id']);
        $buyer = Buyer::where('phone',$request['params']['account']['Phone'])->whereIn('status',[1, 2, 4, 12])->first();
        if($buyer == null) {
            Log::channel('payme')->error('Ошибка -31050 Лицевой счет не найден');
            $arrError = array('ru'=>'Ввведеные данные не корректны','en'=>'Entered data is not correct','uz'=>'Kiritilgan ma`lumotlar noto`g`ri');
            return $this->error(
                self::ERROR_INVALID_ACCOUNT,
                $arrError
            );
        }

        $amount = $request['params']['amount']/100; // 27.05 переводим из тийинов в суммы

        // Если сумма меньше 1000 сум ошибка

        if($amount<1000){
            $arrError = array('ru'=>'Ошибка суммы недостаточно','en'=>'Error invalid amount','uz'=>'Transakciya topilmadi');
            return $this->error(
                self::ERROR_INVALID_AMOUNT,
                $arrError
            );
        }
        //Если сумма и лицевой счет сходится то отправляем true

        $response = [];
        $response['jsonrpc'] = '2.0';
        $response['result']['allow']  = true;
        return $response;

    }

    private function CheckTransaction( $request ){
        Log::channel('payme')->info("CheckTransaction: \n Request: \n".print_r($request, 1));
        // Проверка отправлены ли все параметры
        if(!isset($request['params']['id'])){
            $arrError = array('ru'=>'Невозможно выполнить операцию','en'=>'Unable to complete the operation','uz'=>'Amaliyotni bajarib bo`lmadi');
            Log::channel('payme')->error('Error -31008 in request from payme');
            return $this->error(
                self::ERROR_COULD_NOT_PERFORM,
                $arrError
            );

        }

        $paymeTransact = Payment::where('transaction_id', $request['params']['id'])->where('payment_system', 'PAYME')->where('amount','>', 0)->first();
        Log::channel('payme')->info("CheckTransaction: \n payment: \n".print_r($paymeTransact, 1));

        if($paymeTransact) {

            $response = [];
            $response['jsonrpc'] = '2.0';
            $response['result']['create_time'] = intval($paymeTransact->create_at);

            if($paymeTransact->perform_at == null || $paymeTransact->perform_at == 0){
                $response['result']['perform_time'] = 0;
            }else{
                $response['result']['perform_time'] = strtotime($paymeTransact->perform_at)*1000;
                // $response['result']['perform_time'] = intval($paymeTransact->perform_time);
            }
            if($paymeTransact->cancel_at == null || $paymeTransact->cancel_at == 0){
                $response['result']['cancel_time'] = 0;
            }else{
                $response['result']['cancel_time'] = strtotime($paymeTransact->cancel_at)*1000; // intval
            }
            $response['result']['transaction'] = $paymeTransact->transaction_id;
            $response['result']['state'] = $paymeTransact->state;
            $response['result']['reason'] = $paymeTransact->reason;
            return $response;
        }else{
            $arrError = array('ru'=>'Транзакция не найдена','en'=>'Transaction not found','uz'=>'Transakciya topilmadi');
            //$this->debug('Ошибка -31003 Транзакция не найдена');
            //Log::channel('cards')->warning('Ошибка -31003 Транзакция не найдена');
            Log::channel('payme')->warning('Ошибка -31003 Транзакция не найдена');
            return $this->error(
                self::ERROR_INVALID_ACCOUNT,
                $arrError
            );
        }
    }

    private function CreateTransaction($request){
        Log::channel('payme')->info("CreateTransaction: \n Request: \n".print_r($request, 1));
        // Проверка отправлено-ли все параметры
        if(isset($request['time'])){
            $arrError = array('ru'=>'Невозможно выполнить операцию','en'=>'Unable to complete the operation','uz'=>'Amaliyotni bajarib bo`lmadi');
            //$this->debug('Error -31008 in request from payme');
            //Log::channel('cards')->error('Error -31008 in request from payme');
            Log::channel('payme')->error('Error -31008 in request from payme');
            return $this->error(
                self::ERROR_COULD_NOT_PERFORM,
                $arrError
            );

        }
        $amount = $request['params']['amount']/100; // 27.05 переводим из тийинов в суммы

       // $user = User::whereRoleIs('buyer')->where('id', $request['params']['account']['client_id'])->first();
        $user = User::whereRoleIs('buyer')->where('phone', $request['params']['account']['Phone'])->whereIn('status',[1, 2, 4, 12])->first();
        if($user == null) {
            //$this->debug('Ошибка -31050 Лицевой счет не найден');
            //Log::channel('cards')->info('Ошибка -31050 Лицевой счет не найден');
            Log::channel('payme')->info('Ошибка -31050 Лицевой счет не найден');
            $arrError = array('ru'=>'Ввведеные данные не корректны','en'=>'Entered data is not correct','uz'=>'Kiritilgan ma`lumotlar noto`g`ri');
            return $this->error(
                self::ERROR_INVALID_ACCOUNT,
                $arrError
            );
        }

        //Проверка на лицевой счет

        if($amount<1000){
            $arrError = array('ru'=>'Ошибка, суммы недостаточно','en'=>'Error invalid amount','uz'=>'Transakciya topilmadi');
            return $this->error(
                self::ERROR_INVALID_AMOUNT,
                $arrError
            );
        }

        $payment = Payment::where(['transaction_id'=>$request['params']['id']])->where('payment_system', 'PAYME')->first();

        if($payment == null) {
            //Создаем запись в истории биллинга
            /*
			-- `paycom_transaction_id` char(25) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Номер или идентификатор транзакции в биллинге мерчанта. Формат строки определяется мерчантом.',
			`paycom_time` varchar(13) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Время создания транзакции Paycom.', - created_at
			`paycom_time_datetime` datetime DEFAULT NULL COMMENT 'Время создания транзакции Paycom.', - created_at
			`create_time` datetime NOT NULL COMMENT 'Время добавления транзакции в биллинге мерчанта.', - created_at
			`perform_time` datetime DEFAULT NULL COMMENT 'Время проведения транзакции в биллинге мерчанта', - perform_at
			`cancel_time` datetime DEFAULT NULL COMMENT 'Время отмены транзакции в биллинге мерчанта.', - cancel_at
			-- `amount` int(11) NOT NULL COMMENT 'Сумма платежа в тийинах.',
			`state` int(11) NOT NULL DEFAULT '0' COMMENT 'Состояние транзакции',
			`reason` tinyint(2) DEFAULT NULL COMMENT 'причина отмены транзакции.',
			`receivers` varchar(500) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'JSON array of receivers',
			-- `order_id` bigint(20) NOT NULL COMMENT 'договор',
			`cms_order_id` char(20) COLLATE utf8_unicode_ci NOT NULL COMMENT 'номер договора CMS',
			`is_flag_test` enum('Y','N') COLLATE utf8_unicode_ci NOT NULL,
 */


            //Создаем транзакцию
            $payment = new Payment();
            $payment->contract_id = null;
            $payment->order_id = null;
            $payment->user_id = $user->id; //$request['params']['account']['Phone']; //$request['params']['account']['client_id'];
            $payment->schedule_id = null;
            $payment->card_id = null;
            //$payment->insurance_request_id = null;
            $payment->transaction_id = $request['params']['id'];
            $payment->amount = $amount;
            $payment->type = 'user';
            $payment->payment_system = 'PAYME';
            $payment->state = 1;
            $payment->reason = null;
            $payment->created_at =  time(); //intval($request['params']['time']); // 27,05 добавлено intval
            $payment->create_at = intval($request['params']['time']);
            // $payment->perform_time = intval($request['params']['time']); // manual add 01.06
            $payment->cancel_at = null;
            $payment->perform_at = null;
            $payment->status = 1;

            $payment->save();
            //Сохранение транзакции в лог файле
            //$this->debug_transactions($request);
            $response = [];
            $response['jsonrpc'] = '2.0';
            $response['result']['create_time'] = isset($request['params']['time']) ? $request['params']['time'] : time()*1000; //$request['params']['time']; // Carbon::parse($payment->created_at)->milliseconds;
            $response['result']['transaction'] = $request['params']['id'];

            $response['result']['state'] = 1;
            return $response;
        }else{


            // Вызов метода "CreateTransaction" с новой транзакцией. Состояние счета: "В ожидании оплаты"
            if($payment!=null && $payment->state!=1){
                $arrError = array('ru'=>'Невозможно выполнить операцию','en'=>'Unable to complete the operation','uz'=>'Amaliyotni bajarib bo`lmadi');
                Log::channel('payme')->error('Error -31008 in request from payme');
                return $this->error(
                    self::ERROR_COULD_NOT_PERFORM,
                    $arrError
                );
            }

            $response = [];
            $response['jsonrpc'] = '2.0';
            $response['result']['create_time'] = isset($request['params']['time']) ? $request['params']['time'] : time()*1000; //$request['params']['time']; // Carbon::parse($payment->created_at)->milliseconds;
            $response['result']['transaction'] = $request['params']['id'];
            //$response['result']['pay_state'] = $payment->state;
            $response['result']['state'] = 1;
            return $response;


        }
    }

    private function PerformTransaction($request){
        // Проверка отправлены ли все параметры
        if(!isset($request['params']['id'])){
            //$this->debug('Error in request from payme');
            Log::channel('payme')->error('Error in request from payme');
            $arrError = array('ru'=>'Невозможно выполнить операцию','en'=>'Unable to complete the operation','uz'=>'Amaliyotni bajarib bo`lmadi');
            return $this->error(
                self::ERROR_COULD_NOT_PERFORM,
                $arrError
            );
        }
        Log::channel('payme')->info("PerformTransaction: \n Request: \n".print_r($request, 1));
        //Поиск транзакции
        $payment = Payment::where('transaction_id', $request['params']['id'])->where('payment_system', 'PAYME')->first();

        // Log::channel('payme')->info("PerformTransaction: \n payment: \n".print_r($payment, 1));

        if($payment != null){
            //Пополнение лицевого счета клиента
            if($payment->state != 2) {
                if($buyer = Buyer::/*with('settings')->*/where('id', $payment->user_id)->whereIn('status',[1, 2, 4, 12])->first()) {

                    if($buyerSettings = BuyerSetting::where('user_id',$payment->user_id)->first()) { // $buyer->settings) {

                        $start = $buyerSettings->personal_account; // $buyer->settings->personal_account;

                        //$buyer->settings->personal_account += $payment->amount; // увеличение баланса клиента

                        $buyerSettings->personal_account += $payment->amount;

                        $end = $buyerSettings->personal_account;

                        //$end = $buyer->settings->personal_account;

                        if ( $buyerSettings->save() ) { // $buyer->settings->save() ) {

                            $buyer->save();
//////////////////////////////////////////////////////////////////////////////////////////////////////
                            /*// уведомить о пополнении сервер автосписания
                            $data = [
                                'user_id' => $buyer->id,
                                'amount' => $payment->amount,
                            ];
                            PaymentHelper::fillAccount($data);*/
//////////////////////////////////////////////////////////////////////////////////////////////////////
                            $payment->status = 1;

                            if ($payment->perform_at == 0 || $payment->perform_at == NULL) {
                                $payment->perform_at = date('Y-m-d H:i:s', time());
                            }

                            $payment->state = 2;

                            $payment->save();

                            $client = Http::withOptions([
                                'cert' => [storage_path('cert_external/client_1.pfx'),  config('test.external_cert_pass')],
                                'curl'     => [CURLOPT_SSLCERTTYPE => 'P12'],
                            ]);

                            $created_at = Carbon::make($payment->created_at);
                            $create_at = Carbon::createFromTimestampMs($payment->create_at);
                            $perform_time = Carbon::make($payment->perform_time);
                            $cancel_at = Carbon::make($payment->cancel_at);
                            $performAt = Carbon::make($payment->performAt);

                            $response = $client->post(config('test.external_service_url').'create/transaction', [
                                'contractId'         => $payment->contract_id,
                                'orderId'            => $payment->order_id,
                                'userId'             => $payment->user_id,
                                'scheduleId'         => $payment->schedule_id,
                                'cardId'             => $payment->card_id,
                                'transactionId'      => $payment->id,
                                'uuid'               => $payment->uuid,
                                'amount'             => $payment->amount * 100,
                                'type'               => $payment->type,
                                'paymentSystem'      => 'PAYME',
                                'state'              => $payment->state,
                                'reason'             => $payment->reason,
                                'createdAt'          => $created_at->format('c'),
                                'createAt'           => $create_at ? $create_at->format('c') : null,
                                'performTime'        => $perform_time ? $perform_time->format('c') : null,
                                'updatedAt'          => $payment->updated_at->format('c'),
                                'cancelAt'           => $cancel_at ? $cancel_at->format('c') : null,
                                'performAt'          => $performAt ? $performAt->format('c') : null,
                            ]);
                            if ($response->failed()){
                                Log::channel('payme_external')->info(__METHOD__, [
                                    'code' => $response->status(),
                                    'body' => $response->body()
                                ]);
                            }

                            Log::channel('payme')->info("Save Payme user_id: " . $buyer->id . ' сумма пополнения: ' . $payment->amount . ' было: '. $start . ' стало: ' . $end );

                        } else {
                            Log::channel('payme')->info("ERROR save Payme user_id: " . $payment->user_id);
                        }

                    }else{
                        Log::channel('payme')->info("ERROR not found buyer->settings user_id: " . $payment->user_id);
                    }

                }else{
                    $arrError = array('ru'=>'Ввведеные данные не корректны','en'=>'Entered data is not correct','uz'=>'Kiritilgan ma`lumotlar noto`g`ri');
                    Log::channel('payme')->info('Ошибка -31050 Лицевой счет не найден');
                    return $this->error(
                        self::ERROR_INVALID_ACCOUNT,
                        $arrError
                    );
                }
            }else{ //
                Log::channel('payme')->info('ERROR. PAYME не записалось в ЛС user_id: ' . $payment->user_id . ' payment->id' . $payment->id . ' payment->state ' . $payment->state );
                Log::channel('payme')->info($request);
                Log::channel('payme')->error('Error in request from payme');
                TelegramHelper::sendByChatId('1726060082', 'ERROR. PAYME не записалось в ЛС user_id: ' . $payment->user_id . ' payment->id' . $payment->id . ' payment->state ' . $payment->state );

                $arrError = array('ru'=>'Невозможно выполнить операцию','en'=>'Unable to complete the operation','uz'=>'Amaliyotni bajarib bo`lmadi');
                return $this->error(
                    self::ERROR_COULD_NOT_PERFORM,
                    $arrError
                );



            }

        }else{
            $arrError = array('ru'=>'Транзакция не найдена','en'=>'Transaction not found','uz'=>'Transakciya topilmadi');
            Log::channel('payme')->warning('Ошибка -31003 Подготовленная транзакция не найдена');
            return $this->error(
                self::ERROR_TRANSACTION_NOT_FOUND,
                $arrError
            );
        }

        $response = [];
        $response['jsonrpc'] = '2.0';
        $response['result']['transaction'] = $payment->transaction_id;
        $response['result']['perform_time'] =  strtotime($payment->perform_at)*1000; //time()*1000;
        $response['result']['state'] = 2; // было 2
        return $response;
    }

    private function CancelTransaction($request)
    {
        Log::channel('payme')->info("CancelTransaction: \n Request: \n" . print_r($request, 1));
        $transaction = Payment::where([
            ['transaction_id', $request['params']['id']],
            ['payment_system', Payment::PAYMENT_SYSTEM_PAYME],
            ['created_at', '>=', date('Y-m-01 00:00:00')]
        ])->first();
        $user = Buyer::find($transaction->user_id)->load('settings');
        $cancel_at = now();

        if (!$transaction) { // not found = -31003
            $arrError = array('ru' => 'Транзакция не найдена', 'en' => 'Transaction not found', 'uz' => 'Transakciya topilmadi');
            Log::channel('payme')->warning('Ошибка -31003 транзакция в методе CancelTransaction не найдена');
            return $this->error(
                self::ERROR_TRANSACTION_NOT_FOUND,
                $arrError
            );
        }
        Log::channel('payme')->info("CancelTransaction: \n Model: \n" . print_r($transaction, 1));

        if ($transaction->state > 0) {
            $refund = $transaction->replicate()->fill([
                'status'    => 1,
                'amount'    => (-1 * $transaction->amount),
                'type'      => Payment::PAYMENT_TYPE_REFUND,
                'cancel_at' => null
            ]);
            if ($transaction->state === 1) {
                try {
                    DB::transaction(function () use ($transaction, $request, $cancel_at, $refund) {
                        $transaction->update([
                            'reason'    => $request['params']['reason'],
                            'status'    => -1,
                            'state'     => -1,
                            'cancel_at' => $cancel_at
                        ]);

                        $refund->save();
                    });
                } catch (\Throwable $exception){
                    return $this->error(
                        self::ERROR_COULD_NOT_CANCEL
                    );
                }

                return $this->response($transaction->transaction_id, $transaction->cancel_at, -1);
            }

            if ($transaction->state === 2) { // 1
                if ($user->settings->personal_account >= $transaction->amount) {
                    try {
                        DB::transaction(function () use ($transaction, $request, $cancel_at, $refund, $user) {
                            $transaction->update([
                                'reason'    => $request['params']['reason'],
                                'status'    => -1,
                                'state'     => -2,
                                'cancel_at' => $cancel_at
                            ]);

                            $refund->save();

                            $user->settings->personal_account -= $transaction->amount; // снятие пополненных средств с баланса клиента
                            $user->settings->save();
                        });
                    } catch (\Throwable $exception){
                        return $this->error(
                            self::ERROR_COULD_NOT_CANCEL
                        );
                    }

                    $client = Http::withOptions([
                        'cert' => [storage_path('cert_external/client_1.pfx'), config('test.external_cert_pass')],
                        'curl' => [CURLOPT_SSLCERTTYPE => 'P12'],
                    ]);

                    $created_at   = Carbon::make($refund->created_at);
                    $create_at    = Carbon::createFromTimestampMs($refund->create_at);
                    $perform_time = Carbon::make($refund->perform_time);
                    $cancel_at    = Carbon::make($refund->cancel_at);
                    $performAt    = Carbon::make($refund->performAt);

                    $response = $client->post(config('test.external_service_url') . 'cancel/transaction', [
                        'contractId'    => $refund->contract_id,
                        'orderId'       => $refund->order_id,
                        'userId'        => $refund->user_id,
                        'scheduleId'    => $refund->schedule_id,
                        'cardId'        => $refund->card_id,
                        'transactionId' => $transaction->id,
                        'uuid'          => $refund->uuid,
                        'amount'        => $refund->amount * 100,
                        'type'          => $refund->type,
                        'paymentSystem' => 'PAYME',
                        'state'         => $refund->state,
                        'reason'        => $refund->reason,
                        'createdAt'     => $created_at->format('c'),
                        'createAt'      => $create_at ? $create_at->format('c') : null,
                        'performTime'   => $perform_time ? $perform_time->format('c') : null,
                        'updatedAt'     => $refund->updated_at->format('c'),
                        'cancelAt'      => $cancel_at ? $cancel_at->format('c') : null,
                        'performAt'     => $performAt ? $performAt->format('c') : null,
                    ]);

                    if ($response->failed()) {
                        Log::channel('payme_external')->info(__METHOD__, [
                            'code' => $response->status(),
                            'body' => $response->body()
                        ]);
                    }

                    return $this->response($transaction->transaction_id, $transaction->cancel_at, -2);
                }

                $arrError = array('ru' => 'Ошибка суммы для возврата недостаточно', 'en' => 'Error invalid amount', 'uz' => 'Transakciya topilmadi');
                Log::channel('payme')->warning('Ошибка -31007 сумма для метода CanselTransaction не достаточна');
                return $this->error(
                    self::ERROR_COULD_NOT_CANCEL,
                    $arrError
                );
            }
        }
        return $this->response($transaction->transaction_id, $cancel_at, $transaction->state);
    }

    private function ChangePassword($request){
        //TODO
        return $this->error(
            self::ERROR_INTERNAL_SYSTEM
        );
    }

    public static function message($message){
        return ['ru' => $message['ru'], 'uz' => $message['uz'], 'en' => $message['en']];
    }

    private function response($transaction_id, $cancel_at, $state)
    {
        return response()->json([
            'result' => [
                'transaction' => $transaction_id,
                'cancel_time' => Carbon::parse($cancel_at)->getTimestampMs(),
                'state' => $state,
            ]
        ]);
    }

    public function error($code, $message = null, $data = null){
        $response = array();
        $response['jsonrpc'] = '2.0';
        $response['error']['code']   = $code;
        $response['error']['message']   = self::message($message);
        $response['id']      = ''; //$request['params']['id'];
        return $response;
    }

}
