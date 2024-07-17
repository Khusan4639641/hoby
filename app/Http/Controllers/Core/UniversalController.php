<?php

namespace App\Http\Controllers\Core;

use App\Classes\Scoring\LastScoringLog;
use App\Classes\Scoring\ScoringRequestLog;
use App\Facades\OldCrypt;
use App\Helpers\CardHelper;
use App\Helpers\EncryptHelper;
use App\Helpers\PaymentHelper;
use App\Helpers\SmsHelper;
use App\Helpers\UniversalHelper;
use App\Http\Requests\SendSmsCodeUniversalRequest;
use App\Models\Buyer;
use App\Models\Card;
use App\Models\CardLog;
use App\Models\CardScoring;
use App\Models\CardScoringLog;
use App\Models\Contract;
use App\Models\ContractPaymentsSchedule;
use App\Models\CronPayment;
use App\Models\User;
use App\Services\API\V3\BaseService;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\Payment;
use App\Models\PaymentLog;
use App\Traits\SmsTrait;
use Illuminate\Support\Facades\Redis;
use App\Http\Controllers\Core\CardController;
use mysql_xdevapi\Exception;


class UniversalController extends CoreController
{

    private static $login = 'Test';
    private static $password = 'NQVOY6O9ijTQBboiClRNMAt';

    // логин и пароль для получения токена для автосписаний со всех карт клиента
    private static $login_auto = 'Test';
    private static $password_auto = 'QQ8V2sSy75Kx#^c!';

    private static $token = '$2y$10$W6x5ftJGFABvaI92YElS2.GXpBsOmRkuxXhJwW9OH7HoCEcFk22ZS';
    // private static $token_humo = '$2y$10$cZ388HY8AzTSmGg7s4N87.tkli4xCSy0vwNVZYfWeK6vHRglsTFI6';
    private static $token_auto = '884e1534-ec21-4072-aa68-218c707b7c57'; // для списания со всех карт клиента - 60 дней - 03.08.2021

    private static $url = 'https://core.unired.uz/api/v1/unired';
    private static $url_humo = 'https://core.unired.uz/api/v1/humo';
    private static $url_auto = 'https://credit.unired.uz/api/tokens';  // для списания со всех карт клиента

    // старые терминалы для solutions lab
    /*private static $merchant_uzcard = '90488584';
    private static $terminal_uzcard = '92408712';
    private static $merchant_humo = '009960536923402';
    private static $terminal_humo = '2361054Y';*/

    const PASSWORD = 620285;

    //
    public static function loginUzcard()
    {

        $id = uniqid();


        $input = json_encode([
            'id' => $id,
            'method' => 'login',
            'params' => [
                'login' => self::$login_auto,
                'password' => self::$password_auto,
            ],
        ]);

        $curl = curl_init(self::$url_auto_login);

        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, self::$login_auto . ':' . self::$password_auto);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $input);

        $result = curl_exec($curl);


        return $result;

    }

    //
    public static function loginHumo()
    {

        $id = uniqid();


        $input = json_encode([
            'id' => $id,
            'method' => 'login',
            'params' => [
                'login' => self::$login,
                'password' => self::$password,
            ],
        ]);

        $curl = curl_init(self::$url);

        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, self::$login . ':' . self::$password);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $input);

        $result = curl_exec($curl);


        return $result;

    }

    //
    public static function addTerminal($url_humo)
    {


        $id = 'test_' . uniqid(rand(), 1);


        $input = json_encode([
            'jsonrpc' => '2.0',
            'id' => $id,
            'method' => 'terminal.add',
            'params' => [
                'merchant' => self::$merchant_uzcard,
                'terminal' => self::$terminal_uzcard,
                'type' => 1,
                'port' => 1010,
                'purpose' => 'Online to’lovlar amalga oshirish',
                'point_code' => '100010104110',
                'originator' => 'test',
                'centre_id' => 'test eportal',
            ],
        ]);


        $result = self::backOffice($input, $url_humo);


        return $result;

    }


    // баланс, телефон, фио
    public static function getCardInfo($request)
    {
        Log::info('input');
        //Log::info($request);

        $card = EncryptHelper::decryptData($request->info_card['card_number']);
        $exp = EncryptHelper::decryptData($request->info_card['card_valid_date']);
        $exp = CardHelper::getAdditionalCardInfo($card, $exp)['exp'];

        $id = 'test_' . time() . uniqid(rand(), 10);

        $config = self::buildAuthorizeConfig();

        if($config['test_api_balance_switch']){

            if((CardHelper::checkTypeCard($card)['type'] == 2) && env('HUMO_TO_UNIVERSAL_BALANCE_SWITCH')){

                $input = json_encode([
                    'jsonrpc' => '2.0',
                    'id' => $id,
                    'method' => 'card.register',
                    'params' => [
                        'card_number' => $card,
                        'expire' => $exp,
                    ],
                ]);
                $res = self::backOfficeBalance($input, $request->url_humo);

            }elseif((CardHelper::checkTypeCard($card)['type'] == 1) && env('UZCARD_TO_UNIVERSAL_BALANCE_SWITCH')){

                $input = json_encode([
                    'jsonrpc' => '2.0',
                    'id' => $id,
                    'method' => 'card.register',
                    'params' => [
                        'card_number' => $card,
                        'expire' => $exp,
                    ],
                ]);
                $res = self::backOfficeBalance($input, $request->url_humo);

            }else{
                $input = json_encode([
                    'jsonrpc' => '2.0',
                    'id' => 'api_card_balance_'.$id,
                    'method' => 'card_register',
                    'params' => [
                        'number' => $card,
                        'expiryDate' => EncryptHelper::decryptData($request->info_card['card_valid_date']),
                    ],
                ]);

                $inputArr = \GuzzleHttp\json_decode($input);

                $res = self::testApiGetBalance($inputArr->params->number, $inputArr->params->expiryDate);
            }

        }else{

            $input = json_encode([
                'jsonrpc' => '2.0',
                'id' => $id,
                'method' => 'card.register',
                'params' => [
                    'card_number' => $card,
                    'expire' => $exp,
                ],
            ]);

            //$res = self::backOffice($input, $request->url_humo);
            $res = self::backOfficeBalance($input, $request->url_humo);
        }

        Log::channel('payment')->info(print_r($input, 1));
        Log::channel('payment')->info(json_encode($res, 1));


        $data = [];

        if (isset($res['result'])) {

            $data['result'] = [
                "card_number" => $request->has('is_cron') ? '' : EncryptHelper::encryptData($res['result']['card_number']),
                "expire" => $request->has('is_cron') ? '' : EncryptHelper::encryptData($res['result']['expire']),
                "phone" => $res['result']['phone'],
                "balance" => $res['result']['balance'],
                "state" => $res['result']['state'],
                "filial" => $res['result']['filial'],
                "is_corporate" => $res['result']['is_corporate'],
                "owner" => $res['result']['owner'],
                "account" => $res['result']['account'],
                'status' => 'success',
                'response' => $request->has('is_cron') ? $res : '',
            ];

        } else {
            $data['result'] = [
                "card_number" => '',
                "expire" => '',
                "phone" => '',
                "balance" => 0,
                "state" => '',
                "filial" => '',
                "is_corporate" => '',
                "owner" => '',
                "account" => '',
                'status' => 'error',
                'response' => $request->has('is_cron') ? $res : '',

            ];
        }

        return $data;

    }


    // баланс для cron
    public static function balanceCron($request)
    {
        Log::info('input');
        // Log::info($request);

        $card = EncryptHelper::decryptData($request->info_card['card_number']);
        $exp = EncryptHelper::decryptData($request->info_card['card_valid_date']);
        $exp = CardHelper::getAdditionalCardInfo($card, $exp)['exp'];

        $id = 'test_' . time() . uniqid(rand(), 10);

        $input = json_encode([
            'jsonrpc' => '2.0',
            'id' => $id,
            'method' => 'card.register',
            'params' => [
                'card_number' => $card,
                'expire' => $exp,
            ],
        ]);


        //$res = self::backOffice($input, $request->url_humo);
        $res = self::backOfficeBalance($input, $request->url_humo);

        Log::channel('payment')->info(print_r($input, 1));
        Log::channel('payment')->info(print_r($res, 1));

        $data = [];

        if (isset($res['result'])) {

            $data['result'] = [
                "card_number" => EncryptHelper::encryptData($res['result']['card_number']),
                "expire" => EncryptHelper::encryptData($res['result']['expire']),
                "phone" => $res['result']['phone'],
                "balance" => $res['result']['balance'],
                "state" => $res['result']['state'],
                "filial" => $res['result']['filial'],
                "is_corporate" => $res['result']['is_corporate'],
                "owner" => $res['result']['owner'],
                "account" => $res['result']['account'],
                'status' => 'success',
            ];

        } else {
            $data['result'] = [
                "card_number" => '',
                "expire" => '',
                "phone" => '',
                "balance" => 0,
                "state" => '',
                "filial" => '',
                "is_corporate" => '',
                "owner" => '',
                "account" => '',
                'status' => 'error',
            ];
        }

        return $data;

    }


    // список всех карт Humo по номеру телефона
    public static function getCardsList($request)
    {
        $url_humo = $request->type == 1 ? false : true;
        $id = 'test_' . time() . uniqid(rand(), 10);

        $input = json_encode([
            'jsonrpc' => '2.0',
            'method' => 'card.get',
            'params' => [
                'phone' => $request->phone,
            ],
            'id' => $id,
        ]);

        $result = self::backOffice($input, $url_humo);
        // $result = self::backOfficeBalance($input, $url_humo);

        Log::channel('cards')->info(print_r($input, 1));
        Log::channel('cards')->info(print_r($result, 1));

        return $result;

    }

    // списание
    public function payment($request)
    {
        $is_cron = isset($request->is_cron) ? 1 : 0;
        $url_humo = $request->type == 1 ? false : true;
        $card = EncryptHelper::decryptData($request->info_card['card_number']);
        $exp = EncryptHelper::decryptData($request->info_card['card_valid_date']);
        $exp = CardHelper::getAdditionalCardInfo($card, $exp)['exp'];

        $id = 'test_' . time() . uniqid(rand(999, 10000000), 1) . $card;
        $sum = $request->sum;
        $sum *= 100; // превращаем в тиины

        $config = self::buildAuthorizeConfig();

        if ($url_humo) {
            $merchant = $config['merchant_id_humo'];
            $terminal = $config['terminal_id_humo'];
        } else {
            $merchant = $config['merchant_id_uzcard'];
            $terminal = $config['terminal_id_uzcard'];
        }


        $input = json_encode([
            'jsonrpc' => '2.0',
            'id' => $id,
            'method' => 'payment',
            'params' => [
                'card_number' => $card,
                'expire' => $exp,
                'amount' => $sum,
                'merchant' => $merchant,
                'terminal' => $terminal,
            ],
        ]);

        $result = self::backOffice($input, $url_humo);

        Log::channel('payment')->info(print_r($input, 1));
        Log::channel('payment')->info(json_encode($result, 1));

        // записать в бд неуспешный лог списания
        if ($buyer = Buyer::find($request->buyer_id)) {
            $request->merge([
                'method' => 'payment',
                'card_id' => $request->card_id,
                'user_id' => $request->buyer_id,
                'req' => $input,
                'buyer' => $buyer,
            ]);

            if ($result) {
                if (!$result['status']) {

                    $request->merge([
                        'response' => json_encode($result),
                        'code' => $result['error']['code'],
                    ]);

                    $this->setCardLog($request);
                }
            } else {
                $request->merge([
                    'response' => 'server error 500',
                    'code' => 500,
                ]);

                $this->setCardLog($request);
            }
        }

        /////////////////////////////////////

        if (!$is_cron) {

            // не записываем транзакции в payment, если идентификатор с крона пришел
            $setData = self::setData($card, $request, $input, $result);

            if ($setData) {
                $this->result['status'] = 'success';
                $this->result['data'] = $result;
            } else {
                $this->result['status'] = 'error';
                $this->result['data'] = $result;
                $this->message('danger', __('card.uz_payment_error'));
            }
        } else {
            $this->result['status'] = $result['status'] ?? false;
            $this->result['data'] = $result;
        }


        return $this->result;

    }


    private static function setData($card, $request, $input, $result)
    {
        $type = CardHelper::checkTypeCard($card)['name'];

        //$payment_type =  isset($request['payment_type']) ? $request['payment_type'] : 'auto';  // user or auto


        if (isset($result['result']['payment']['id'])) {  // только успешные
            $payment = new Payment;
            if ($request->has('schedule_id'))
                $payment->schedule_id = $request->schedule_id;
            if ($request->has('payment_type'))
                $payment->type = $request['payment_type'];  // автосписание или пополнение
            if ($request->has('order_id'))
                $payment->order_id = $request->order_id;
            if ($request->has('contract_id'))
                $payment->contract_id = $request->contract_id;

            $payment->card_id = $request->card_id;
            $payment->amount = $request->sum;
            $payment->user_id = $request->buyer_id;;
            $payment->payment_system = $type;  // узкард, или хумо

            $paymentLog = new PaymentLog;
            $paymentLog->request = $input;
            $paymentLog->response = json_encode($result);

            $payment->transaction_id = $result['result']['payment']['id'];
            $payment->uuid = $result['result']['payment']['uuid'];   // for humo cancel
            $payment->status = 1;
            $paymentLog->status = 1;

            $payment->save();
            $paymentLog->payment_id = $payment->id;
            $paymentLog->save();

            return true;
        }

        return false;
    }

    private static function setDataCancel($request, $input, $result)
    {

        $refund = Payment::find($request->payment_id);

        if ($result['status']) {
            $payment = new Payment;

            $payment->schedule_id = $refund->schedule_id ?? null;
            $payment->type = 'refund';
            $payment->order_id = $refund->order_id ?? null;
            $payment->contract_id = $refund->contract_id ?? null;

            $payment->card_id = $refund->card_id;
            $payment->amount = $refund->amount * -1;
            $payment->user_id = $refund->user_id;
            $payment->payment_system = $refund->payment_system;
            $payment->uuid = $payment->uuid;

            $paymentLog = new PaymentLog;

            $paymentLog->request = $input;
            $paymentLog->response = json_encode($result);
            $payment->transaction_id = $refund->transaction_id;
            $payment->status = 1;
            $paymentLog->status = 1;

            $payment->save();
            $paymentLog->payment_id = $payment->id;
            $paymentLog->save();

            $refund->status = -1;
            $refund->save();

            // перерасчет денег
            /*if ($refund->contract_id != null && $refund->schedule_id != null) {  // если есть оплаченный контракт
                $contract = Contract::where('user_id', $refund->user_id)->first();

                $payments = Payment::where(['user_id' => $refund->user_id, 'transaction_id' => $refund->transaction_id])->get();
                foreach ($payments as $payment) {
                    if($payment->status == 5 ){   // пропустить лишние платежи
                        $c = CronPayment::where(['user_id' => $refund->user_id, 'amount' => $payment->amount])->first();
                        if($c->status == 6) continue;
                    }

                    $schedule = Schedule::where(['id', $payment->schedule_id])->first();

                    if ($schedule->total >= $payment->amount) {  // если платеж <= месячного долга
                        $schedule->amount += $payment->amount;

                    } else {
                        $pay = $payment->amount - $schedule->total; // разница, если платеж > месячного долга
                        $schedule->amount = $schedule->total;
                    }

                    if ($schedule->amount < 0) $schedule->amount = 0; // корректировка отрицательного числа в 0
                    $schedule->status = 0;
                    $schedule->paid_at = null;

                    $contract->balance -= $schedule->amount;
                    if ($contract->balance < 0) $contract->balance = 0; // корректировка отрицательного числа в 0

                    $schedule->save();
                    $contract->save();

                }
            }*/


            return true;
        }
        return false;
    }


    public function reverse($request)
    {

        if (isset($request->password) && $request->password != null) {

            $a = $request->password <=> self::PASSWORD;

            if ($a != 0) {
                $this->result['status'] = 'error';
                $this->message('danger', __('app.password_error'));
                return $this->result;
            }
        } else {
            $this->result['status'] = 'error';
            $this->message('danger', __('app.password_error'));
            return $this->result;
        }

        $url_humo = $request->type == 1 ? false : true;

        $id = 'test_' . uniqid(rand(), 1);

        if ($url_humo) {
            $params = [
                'payment_id' => $request->transaction_id,
                'uuid' => $request->uuid,
            ];
        } else {
            $params = [
                'payment_id' => $request->transaction_id,
            ];
        }

        $input = json_encode([
            'jsonrpc' => '2.0',
            'id' => $id,
            'method' => 'payment.cancel',
            'params' => $params,
        ]);

        $result = self::backOffice($input, $url_humo);

        Log::channel('payment')->info(print_r($input, 1));
        Log::channel('payment')->info(json_encode($result, 1));

        $setDataCancel = self::setDataCancel($request, $input, $result);

        if ($setDataCancel) {
            $this->result['status'] = 'success';
            $this->result['data'] = $result;
            $this->message('danger', __('app.reverse_payment_success'));
        } else {
            $this->result['status'] = 'error';
            $this->result['data'] = $result;
            $this->message('danger', __('app.reverse_payment_error'));
        }

        return $this->result;

    }

    ////////////////////////////////////////////////////////////////////////////////////
    // списание с карты 1 сум -  для регистрации карты и  проверки ее доступности для списания
    public function checkCardAvaliable($request)
    {

        $card = EncryptHelper::decryptData($request->buyer->cards[0]->card_number);
        $exp = EncryptHelper::decryptData($request->buyer->cards[0]->card_valid_date);
        $exp = CardHelper::getAdditionalCardInfo($card, $exp)['exp'];
        $type = CardHelper::checkTypeCard($card)['type'];


        $url_humo = $type == 1 ? false : true;
        $sum = 100; // тиины 1 сум

        $id = 'test_' . time() . uniqid(rand(), 1) . '-' . $request->buyer->id;
        $config = self::buildAuthorizeConfig();

        if ($url_humo) {
            $merchant = $config['merchant_id_humo'];
            $terminal = $config['terminal_id_humo'];
        } else {
            $merchant = $config['merchant_id_uzcard'];
            $terminal = $config['terminal_id_uzcard'];
        }

        if($config['test_api_payment_and_cancel_switch']){

                $method = 'payment';

                if ($url_humo) {
                    $merchant = $config['test_api_humo_merchant'];
                    $terminal = $config['test_api_humo_terminal'];
                } else {
                    $merchant = $config['test_api_uzcard_merchant'];
                    $terminal = $config['test_api_uzcard_terminal'];
                }

                $input = json_encode([
                    'jsonrpc' => '2.0',
                    'id' => 'api_card_payment_'. time() . uniqid(rand(), 10),
                    'method' => $method,
                    'params' => [
                        'pan' => $card,
                        'expire' => EncryptHelper::decryptData($request->buyer->cards[0]->card_valid_date),
                        'amount' => $sum,
                        'merchant' => $merchant,
                        'terminal' => $terminal
                    ],
                ]);

                $result = self::testApiPayment($card, EncryptHelper::decryptData($request->buyer->cards[0]->card_valid_date), $sum, $merchant, $terminal);

        }else{

            $method = 'payment';

            $input = json_encode([
                'jsonrpc' => '2.0',
                'id' => $id,
                'method' => 'payment',
                'params' => [
                    'card_number' => $card,
                    'expire' => $exp,
                    'amount' => $sum, // проверить
                    'merchant' => $merchant,
                    'terminal' => $terminal,
                ],
            ]);

            $result = self::backOffice($input, $url_humo);
        }

        Log::channel('payment')->info('списание с карты 1 сум -  регистрация, User ' . $request->buyer->id . ', phone ' . $request->buyer->phone);
        Log::channel('payment')->info(print_r($input, 1));
        Log::channel('payment')->info(json_encode($result, 1));

        if ($result) {  // если есть ответ

            $request->merge([
                'method' => $method,
                'req' => $input,
                'response' => json_encode($result),
            ]);

            if (isset($result['result']['payment']['id'])) {  // если успешно

                // db - успешные
                $this->setPaymentLog($request);

                if($config['test_api_payment_and_cancel_switch']){

                    $method = 'payment.cancel';

                    $input = json_encode([
                        'jsonrpc' => '2.0',
                        'id' => 'api_card_payment_cancel_'. time() . uniqid(rand(), 10),
                        'method' => 'payment.cancel',
                        'params' => [
                            'payment_id' => $result['result']['payment']['id'],
                        ],
                    ]);

                    $res = self::testApiCancel($result['result']['payment']['id']);
                }else{

                    // пробуем вернуть, если ошибка, зачисляем на ЛС
                    $id = 'test_' . time() . uniqid(rand(), 10);
                    $method = 'payment.cancel';
                    if ($url_humo) {
                        $params = [
                            'payment_id' => $result['result']['payment']['id'],
                            'uuid' => $result['result']['payment']['uuid'],
                        ];
                    } else {
                        $params = [
                            'payment_id' => $result['result']['payment']['id'],
                        ];
                    }

                    $input = json_encode([
                        'jsonrpc' => '2.0',
                        'id' => $id,
                        'method' => 'payment.cancel',
                        'params' => $params
                    ]);


                    $res = self::backOffice($input, $url_humo);
                }



                Log::channel('payment')->info('возврат 1 сум -  регистрация, User ' . $request->buyer->id . ', phone ' . $request->buyer->phone);
                Log::channel('payment')->info(print_r($input, 1));
                Log::channel('payment')->info(json_encode($res, 1));

                $request->merge([
                    'method' => $method,
                    'req' => $input,
                    'response' => json_encode($res),
                ]);

                if ($res) {
                    if (!$res['status']) {  // возврат не удался

                        $request->buyer->settings->personal_account += $sum / 100;
                        $request->buyer->settings->save();
                        Log::channel('payment')->info('пополнение 1 сум ЛС, возврат не удался -  регистрация, User ' . $request->buyer->id . ', phone ' . $request->buyer->phone);
                        // записать в бд неуспешный лог списания
                        $request->merge([
                            'code' => $res['error']['code'],
                        ]);

                        $this->setCardLog($request);

                    } else {  // возврат удался - успешные

                        $this->setPaymentLog($request);
                    }
                } else {
                    Log::channel('payment')->info('возврат не удался - server error 500');

                    // записать в бд неуспешный лог возврат
                    $request->merge([
                        'response' => 'server error 500',
                        'code' => 500,
                    ]);

                    $this->setCardLog($request);
                }

                return true;
            } else {
                // записать в бд неуспешный лог списания
                $request->merge([
                    'code' => $result['error']['code'],
                ]);

                $this->setCardLog($request);

                return false;
            }
        } else {
            Log::channel('payment')->info(print_r('server error 500', 1));

            $request->merge([
                'method' => $method,
                'req' => $input,
                'code' => 500,
                'response' => 'server error 500',
            ]);

            $this->setCardLog($request);

            // 500 server error
            $this->result['code'] = '500';
            return $this->result;
        }

    }


    // проверка баланса карты на 1 сум - для регистрации карты и проверки ее доступности для списания
    public function checkCardBalance($request)
    {

        $config = self::buildAuthorizeConfig();

        if (isset($request->card)) {
            $card_info = CardHelper::getAdditionalCardInfo($request->card, $request->exp);
            $card = $card_info['card'];
            $exp = $card_info['exp'];
        } else {
            $card = EncryptHelper::decryptData($request->buyer->cards[0]->card_number);
            $exp = EncryptHelper::decryptData($request->buyer->cards[0]->card_valid_date);
            $exp = CardHelper::getAdditionalCardInfo($card, $exp)['exp'];
        }

        $sum = 100; // тиины 1 сум

        $id = 'test_' . time() . uniqid(rand(), 10);
        $method = 'card.register';

        if($config['test_api_balance_switch']){

            if((CardHelper::checkTypeCard($card)['type'] == 2) && env('HUMO_TO_UNIVERSAL_BALANCE_SWITCH')){

                $input = json_encode([
                    'jsonrpc' => '2.0',
                    'id' => $id,
                    'method' => 'card.register',
                    'params' => [
                        'card_number' => $card,
                        'expire' => $exp,
                    ],
                ]);
                $res = self::backOffice($input, $request->url_humo);

            }elseif((CardHelper::checkTypeCard($card)['type'] == 1) && env('UZCARD_TO_UNIVERSAL_BALANCE_SWITCH')){

                $input = json_encode([
                    'jsonrpc' => '2.0',
                    'id' => $id,
                    'method' => 'card.register',
                    'params' => [
                        'card_number' => $card,
                        'expire' => $exp,
                    ],
                ]);
                $res = self::backOffice($input, $request->url_humo);

            }else{
                $input = json_encode([
                    'jsonrpc' => '2.0',
                    'id' => 'api_card_balance_'. time() . uniqid(rand(), 10),
                    'method' => 'card.register',
                    'params' => [
                        'number' => $card,
                        'expiryDate' => EncryptHelper::decryptData($request->buyer->cards[0]->card_valid_date),
                    ],
                ]);
                $res = self::testApiGetBalance($card, EncryptHelper::decryptData($request->buyer->cards[0]->card_valid_date));
            }


        }else{

            $input = json_encode([
                'jsonrpc' => '2.0',
                'id' => $id,
                'method' => 'card.register',
                'params' => [
                    'card_number' => $card,
                    'expire' => $exp,
                ],
            ]);

            $res = self::backOffice($input, $request->url_humo);
        }

        $request->merge([
            'method' => $method,
            'req' => $input
        ]);
        if ($res) {
            if ($res['status']) {
                if ($res['result']['balance'] >= $sum) { // проверка баланса карты на хотя бы 1 сум
                    return true;
                } else {
                    return false;
                }

                Log::channel('payment')->info(json_encode($res, 1));
            } else {
                // ошибка
                $request->merge([
                    'response' => json_encode($res),
                    'code' => $res['error']['code'],
                ]);

                $this->setCardLog($request);

                $this->result['code'] = $res['error']['code'];
                return $this->result;

            }
        } else {
            Log::channel('payment')->info(print_r('server error 500', 1));

            $request->merge([
                'response' => 'server error 500',
                'code' => 500,
            ]);

            $this->setCardLog($request);

            // 500 server error
            $this->result['code'] = '500';
            return $this->result;
        }

    }

    // неудачные запросы
    public function setCardLog($request)
    {

        if ($cardLogExist = CardLog::where(['user_id' => $request->buyer->id, 'card_id' => $request->buyer->cards[0]->id, 'code' => $request->code, 'status' => 0])->first()) {
            $cardLogExist->counter++;
            //$cardLogExist->updated_at = time();
            $cardLogExist->save();
        } else {
            $cardLog = new CardLog();
            $cardLog->user_id = $request->buyer->id;
            $cardLog->card_id = $request->buyer->cards[0]->id;
            $cardLog->method = $request->method;
            $cardLog->request = $request->req;
            $cardLog->response = $request->response;
            $cardLog->code = $request->code;
            $cardLog->status = $request->status ?? 0;
            $cardLog->save();
        }

        return true;
    }

    // удачные запросы
    public function setPaymentLog($request)
    {

        $paymentLog = new PaymentLog();
        $paymentLog->payment_id = 0;
        $paymentLog->card_id = $request->buyer->cards[0]->id;
        $paymentLog->request = $request->req;
        $paymentLog->response = $request->response;
        $paymentLog->status = 1;
        $paymentLog->save();
        return true;
    }

    /////////////////////////////////////////////////////////////////////////////////////////

    // проверка транзакции
    public static function paymentCheck($request)
    {
        $url_humo = $request->type == 1 ? false : true;

        $id = 'test_' . uniqid(rand(), 1);

        if ($request->type != 1) {
            $input = json_encode([
                'jsonrpc' => '2.0',
                'id' => $id,
                'method' => 'payment.check',
                'params' => [
                    'payment_id' => $request->transaction_id,
                    'uuid' => $request->uuid,
                ],
            ]);
        } else {
            $input = json_encode([
                'jsonrpc' => '2.0',
                'id' => $id,
                'method' => 'payment.check',
                'params' => [
                    'payment_id' => $request->transaction_id,
                ],
            ]);
        }


        $result = self::backOffice($input, $url_humo);


        return $result;

    }

    /**  досрочное погашение договора
     * @param Request $request
     * @return array|false|string
     */
    public function repayment(Request $request)
    {

        $this->result['status'] = 'error';
        $this->message('error', __('payment.service_is_temporarily_unavailable'));
        return $this->result;

        $info = "\n" . '-----------------------------------' . "\n";
        $info .= '|   month  |       paid     |  debt' . "\n";
        $info .= '-----------------------------------' . "\n";

        $payment = $request->payment;
        $errors = false;
        $currentDebts = 0;
        $month_debt = 0;
        $total = $payment['total'];
        $now = strtotime(Carbon::now()->format('Y-m-d 23:59:59'));

        if ($contract = Contract::where('id', $payment['contract_id'])->with('schedule', 'buyer')->first()) {

            // вычислить сумму долга
            if ($schedules = $contract->schedule) {
                foreach ($schedules as $schedule) {
                    if ($schedule->status == 1) continue;
                    $currentDebts += $schedule->balance;  // сумма фактического долга

                    $payment_date = strtotime($schedule->payment_date);
                    if ($payment_date <= $now) { // если месяц попал в крон
                        $month_debt += $schedule->balance;  // сумма которая в кроне
                    }
                }
            }

            if ($currentDebts < $total) $total = $currentDebts;  // если сумма долга меньше, чем тотал, то она и будет тотал

            // Если есть месяцы, которые попали в крон - Уменьшаем тотал на сумму ЛС
            /*if($month_debt > 0){
                if($contract->buyer->settings->personal_account >= $month_debt){
                    $total -= $month_debt;
                }else{
                    $total -= $contract->buyer->settings->personal_account;
                }
            }*/

            // проверить хватает ли денег у клиента
            if ($payment['type'] == "card") {

                if ($infoCard = $contract->buyer->cards->where('id', $payment['card_id'])->where('user_id', $contract->buyer->id)->where('status', 1)->first()) {
                    $request->merge(['info_card' => $infoCard->toArray()]);

                    $res = self::getCardInfo($request);
                    $card_balance = $res['result']['balance'] / 100;  // в сумах

                    if ($card_balance <= 0) {  // если на карте 0, вернуть ошибку
                        $errors = true;
                        $this->result['status'] = 'error';
                        $this->message('error', __('order.txt_repayment_error'));
                        return $this->result;

                    } elseif ($card_balance <= $total) {   // если на карте меньше, чем сумма долга
                        $total = $card_balance;  // спишем сколько есть на карте
                    }
                } else {
                    $errors = true;
                    $this->result['status'] = 'error';
                    $this->message('error', 'card not found');
                    return $this->result;
                }


            } else {  //  если с ЛС
                if (isset($contract->buyer->settings)) {  // если на ЛС 0, вернуть ошибку
                    if ($contract->buyer->settings->personal_account <= 0) {
                        $errors = true;
                        $this->result['status'] = 'error';
                        $this->message('error', __('order.txt_repayment_error'));
                        return $this->result;
                    } elseif ($contract->buyer->settings->personal_account < $total) {
                        $total = $contract->buyer->settings->personal_account;  // спишем сколько есть на ЛС
                    }
                } else {
                    $errors = true;
                    $this->result['status'] = 'error';   // если нет settings
                    return $this->result;
                }
            }

            Log::channel('payment')->info('Досрочное погашение с ' . $payment['type'] . ', Клиент ' . $contract->buyer->id . ', контракт: ' . $contract->id . ', cумма к досрочному погашению: ' . $total);

            // списание
            if ($payment['type'] == "card") {

                if ($infoCard) {
                    $card = EncryptHelper::decryptData($infoCard->card_number);
                    $cardtype = CardHelper::checkTypeCard($card)['type'];
                    $request->merge([
                        'type' => $cardtype, // 1 или 2 (uzcard/humo)
                        'payment_type' => 'user',  //  досрочное погашение самим клиентом - пополнение на всю сумму
                        "sum" => $total,   // фактическая сумма списания
                        "buyer_id" => $contract->buyer->id,
                        "card_id" => $payment['card_id'],
                        "contract_id" => $payment['contract_id'],
                    ]);

                    if ($total > 0) {
                        $response = self::payment($request);
                    } else {
                        $errors = true;
                        $this->result['status'] = 'error';
                        $this->message('error', 'total not found');
                        return $this->result;
                    }

                    if ($response['status'] == 'success') {  // удачно списали
                        $this->result['status'] = 'success';
                        $this->result['data'] = [];
                    } else {
                        $errors = true;
                        $this->result['status'] = 'error';
                    }

                }
            }

            if (!$errors) {

                // таблица списаний - для логов
                if ($schedules = $contract->schedule) {
                    $arr = [];
                    $arr_month = [];
                    $repaid = 0;  // сумма оплаченных месяцев
                    $hold_amount = 0;  // ВСЕГО резервируемая сумма на ЛС (для крона,если уже сумма попала в крон)
                    $arr_total = $total;  // сумма к погашению

                    foreach ($schedules as $schedule) {
                        $hold = 0;
                        if ($schedule->status == 1) continue;
                        $payment_date = strtotime($schedule->payment_date);

                        if ($arr_total >= $schedule->balance) {
                            $arr_amount = $schedule->balance; // сумма оплаты за месяц
                            $month_debt = 0;  // ост долга
                            $status = 1;
                            $arr_total -= $arr_amount;
                        } else {
                            $arr_amount = $arr_total;     // сумма оплаты за месяц
                            $month_debt = $schedule->balance - $arr_total; // ост долга
                            $status = 0;
                            $arr_total = 0;
                        }

                        if ($payment_date <= $now) { // если месяц попал в крон
                            $hold = $arr_amount;
                            $hold_amount += $arr_amount;  // резервируем всю сумму на ЛС

                            //если на ЛС хватает для резерва, не уменьшаем тотал, не пополняем на эту сумму ЛС
                            if ($contract->buyer->settings->personal_account >= $hold_amount) {
                                $arr_total += $arr_amount;  // увеличиваем обратно тотал
                                $hold_amount -= $arr_amount;  // уменьшаем сумму для попoлнения ЛС
                            }

                            $month_debt = $schedule->balance;
                            $arr_amount = 0;
                            $status = 0;
                        } else {
                            $repaid += $arr_amount; // всего сумма оплаченных месяцев
                        }

                        $arr_month[] = $schedule->id;

                        $arr[$schedule->id] = [
                            'month' => Carbon::parse($schedule->payment_date)->format('d.m.Y'),
                            'hold' => $hold,
                            'paid' => $arr_amount,
                            'month_debt' => $month_debt,
                            'status' => $status];
                    }

                }

                foreach ($arr as $k => $v) {
                    $info .= $v['month'] . ' |   ' . $v['paid'] . '       | ' . $v['month_debt'] . "\n";
                }
                if ($hold_amount > 0) Log::channel('payment')->info('Захолдирована сумма: ' . $hold_amount . ', списана сумма: ' . $repaid);
                Log::channel('payment')->info($info);

                // пополняем ЛС на холдированную сумму
                if ($hold_amount > 0) {
                    if ($payment['type'] == 'card') {  // если списали с карты, пополнить ЛС
                        $contract->buyer->settings->personal_account += $hold_amount;
                        $contract->buyer->settings->save();
                        Log::channel('payment')->info('Клиент ' . $contract->buyer->id . ' Пополнение ЛС: ' . $hold_amount);
                    }
                }

                // закрываем месяцы
                $setting_balance = 0;
                if ($schedules = $contract->schedule) {
                    foreach ($schedules as $schedule) {
                        if (!in_array($schedule->id, $arr_month)) continue;

                        if ($arr[$schedule->id]['paid'] > 0) {
                            $schedule->balance = $arr[$schedule->id]['month_debt'];   // ост долга
                            $schedule->status = $arr[$schedule->id]['status'];
                            // если месяц закрыт
                            if ($arr[$schedule->id]['status'] == 1) {
                                $schedule->paid_at = time();
                                $setting_balance += $schedule->price;  // лимит
                            }
                            $schedule->save();
                        }
                    }

                    // списываем с ЛС
                    if ($payment['type'] == "account") {
                        if ($repaid != 0) {
                            if (isset($contract->buyer->settings)) {
                                $sum = $contract->buyer->settings->personal_account;  // было
                                $contract->buyer->settings->personal_account -= $repaid;
                                $contract->buyer->settings->save();
                                Log::channel('payment')->info('Клиент ' . $contract->buyer->id . ' Снятие денег с ЛС. Сумма на ЛС: ' . $sum . ' Сумма к снятию: ' . $repaid . '. Остаток суммы ЛС: ' . $contract->buyer->settings->personal_account);
                            }
                        }
                    }

                    // вернуть лимит
                    if ($setting_balance > 0) {
                        $balance = $contract->buyer->settings->balance;
                        $contract->buyer->settings->balance += $setting_balance;
                        $contract->buyer->settings->save();
                        Log::channel('payment')->info('Лимит клиента: ' . $balance . ', возврат лимита: ' . $setting_balance . '. Текущий лимит: ' . $contract->buyer->settings->balance);
                    }

                    // контракт
                    $contract->balance -= $repaid;
                    if ($contract->balance <= 0.01) {
                        $contract->balance = 0;
                        $contract->status = 9;
                        Log::channel('payment')->info('Контракт № ' . $contract->id . ' полностью погашен');
                    }
                    $contract->save();
                }


                //*********************************************************************************************************************
                // проверим, если долг погашен, возвращаем $contract->recovery = 0 (если нет доп договора взыскания) - 07.01.22
                PaymentHelper::debtRelief($contract);
                //*********************************************************************************************************************

                // сохраняем транзакцию списания с ЛС
                if ($repaid >= 0.01) {
                    $transaction = new Payment();
                    $transaction->contract_id = $contract->id;
                    $transaction->order_id = $contract->order->id;
                    $transaction->user_id = $contract->buyer->id;
                    $transaction->amount = $repaid;
                    $transaction->payment_system = 'ACCOUNT';  //  (тк выше при списании всей суммы создается как транзакция пополнения)
                    $transaction->type = 'user_auto';
                    $transaction->status = 1;
                    $transaction->card_id = null;
                    $transaction->save();
                }
                $this->result['status'] = 'success';

                if ($hold_amount == 0) {  // если не холдированы
                    $this->message('success', __('order.txt_repayment_success', ['summ' => $repaid, 'currency' => __('app.currency')]));
                } else {
                    if ($repaid >= 0.01) {  // часть холдированы, часть закрылись
                        $this->message('success', __('order.txt_repayment_part_cron_hold', ['total' => ($repaid + $hold_amount), 'summ' => $repaid, 'user_summ' => $hold_amount, 'currency' => __('app.currency')]));
                    } else { // вся сумма холдированы
                        $this->message('success', __('order.txt_repayment_if_cron_hold', ['summ' => $hold_amount, 'currency' => __('app.currency')]));
                    }

                }
            } else {
                $this->result['status'] = 'error';
                $this->message('error', 'contract not found');
            }
            return $this->result;
        }
    }


    // получение всех транзакций по карте (за заданный период -- ??)
    public static function getTransactions($request)
    {
        $url_humo = $request->type == 1 ? false : true;
        $id = 'test_' . uniqid(rand(), 1);

        $input = json_encode([
            'jsonrpc' => '2.0',
            'id' => $id,
            'method' => 'card.history.local',
            'params' => [
                'card_number' => $request->card_number,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ],
        ]);

        $result = self::backOffice($input, $url_humo);


        return $result;
    }

    // получение всех транзакций (за заданный период --? ) Humo вся история карты
    public static function getHumoTransactions($request)
    {
        $url_humo = $request->type == 1 ? false : true;
        $id = 'test_' . uniqid(rand(), 1);

        $input = json_encode([
            'jsonrpc' => '2.0',
            'id' => $id,
            'method' => 'card.history',
            'params' => [
                'card_number' => $request->card_number,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ],
        ]);

        $result = self::backOffice($input, $url_humo);


        return $result;
    }

    //////////////////////////////////////////////////////////
    public static function backOffice($input, $url_humo = false)
    {

        $config = self::buildAuthorizeConfig();

        if($url_humo) {
            $curl = curl_init($config['url_humo']);
            $token = OldCrypt::decryptString($config['universal_token_uzcard']);
            //$token = OldCrypt::decryptString($config['universal_token_humo']);

        } else {
            $curl = curl_init($config['url_uzcard']);
            $token = OldCrypt::decryptString($config['universal_token_uzcard']);
        }

        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Unisoft-Authorization: Bearer ' . $token)
        );
        curl_setopt($curl, CURLOPT_POSTFIELDS, $input);

        try {
            $result = curl_exec($curl);
            $result = json_decode($result, JSON_UNESCAPED_UNICODE);  // проверить
        } catch (\Exception $e) {
            $result = [
                'status' => 'error'
            ];
            Log::info('universal error');
            Log::info($e);
        }

        return $result;
    }

    //////////////////////////////////////////////////////////
    public static function backOfficeBalance($input, $url_humo = false)
    {

        $config = self::buildAuthorizeConfig();

        if ($url_humo) {
            //$curl = curl_init($config['url_humo_balance']);
            $token = OldCrypt::decryptString($config['universal_token_uzcard']);
            //$token = OldCrypt::decryptString($config['universal_token_humo']);
        } else {
            //$curl = curl_init($config['url_uzcard']);
            $token = OldCrypt::decryptString($config['universal_token_uzcard']);
        }

        $curl = curl_init($config['url_humo_balance']);  // обе карты сюда

        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Unisoft-Authorization: Bearer ' . $token)
        );
        curl_setopt($curl, CURLOPT_POSTFIELDS, $input);

        try {
            $result = curl_exec($curl);
            $result = json_decode($result, JSON_UNESCAPED_UNICODE);  // проверить
        } catch (\Exception $e) {
            $result = [
                'status' => 'error'
            ];
            Log::info('universal error');
            Log::info($e);
        }


        return $result;
    }

    public static function backOfficeScoring($input, $buyer_id, $url_humo = false)
    {

        $isHumoScoringFake = (bool)env('IS_HUMO_SCORING_FAKE', false);
        if ($url_humo && $isHumoScoringFake) {
            $id = 0;
            $inputArr = json_decode($input, true);
            if (isset($inputArr['id'])) {
                $id = $inputArr['id'];
            }
            $jsonText = '{"jsonrpc": "2.0", "id": "' . $id . '", "status": true, "origin": "humo.scoring", "result": [], "host": { "host": "UniSoft", "time_stamp": "' . date('Y-m-d H:i:s') . '"}}';
            return json_decode($jsonText, true);
        }

        $config = self::buildAuthorizeConfig();

        if ($url_humo) {
            $curl = curl_init($config['url_humo']);
            $token = OldCrypt::decryptString($config['universal_token_humo']);
//            $token = OldCrypt::decryptString($config['universal_token_uzcard']);
        } else {
            $curl = curl_init($config['url_uzcard']);
            $token = OldCrypt::decryptString($config['universal_token_uzcard']);
        }

        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Unisoft-Authorization: Bearer ' . $token)
        );
        curl_setopt($curl, CURLOPT_POSTFIELDS, $input);

        $result = curl_exec($curl);

        $result = json_decode($result, JSON_UNESCAPED_UNICODE);  // проверить

        if ($url_humo) {
            if (isset($result['result'])) {
                $newResult = [];
                foreach ($result['result'] as $key => $item) {
                    if (!is_array($item)) {
                        $newResult[$key] = $item;
                    }
                }
                $result['result'] = $newResult;
            }
        }

        //save scoring request/response to card_scoring_request_logs table
        new ScoringRequestLog($buyer_id, json_decode($input, true)['params']['card_number'], $url_humo ? 'humo' : 'uzcard', json_decode($input, true), $result, 'universal');

        return $result;
    }

    // ?? получение скоринга ??  обычно скоринг идет из UniversalHelper::getScoring !!
    public static function getScoring($request)
    {

        // Данный метод не нужен и все сопутствующие в нем методы. Используется UniversalHelper::getScoring()

        Log::channel('cards')->info('UniversalCotroller::getScoring()');

        $card = EncryptHelper::decryptData($request->info_card['card_number']);
        $exp = EncryptHelper::decryptData($request->info_card['card_valid_date']);
        $exp = CardHelper::getAdditionalCardInfo($card, $exp)['exp'];

        $config = self::buildAuthorizeConfig();

        $id = 'test_' . uniqid(rand(), 1);

        $isCardTypeHumo = CardHelper::checkTypeCard($card)['type'] == 2;

        if($config['test_api_scoring_switch']){

            if((CardHelper::checkTypeCard($card)['type'] == 2) && env('HUMO_TO_UNIVERSAL_SCORING_SWITCH')){

                $input = json_encode([
                    'jsonrpc' => '2.0',
                    'id' => $id,
                    'method' => 'humo.scoring',
                    'params' => [
                        'card_number' => $card,
                        'expire' => EncryptHelper::decryptData($request->info_card['card_valid_date']),
                    ],
                ]);

            }elseif((CardHelper::checkTypeCard($card)['type'] == 1) && env('UZCARD_TO_UNIVERSAL_SCORING_SWITCH')){

                $input = json_encode([
                    'jsonrpc' => '2.0',
                    'id' => $id,
                    'method' => 'card.scoring',
                    'params' => [
                        'card_number' => $card,
                        'expire' => EncryptHelper::decryptData($request->info_card['card_valid_date']),
                        'start_date' => $request['start_date'],
                        'end_date' => $request['end_date'],
                    ],
                ]);

            }else{

                $input = json_encode([
                    'jsonrpc' => '2.0',
                    'id' => 'api_scoring_'.$id,
                    'method' => 'card.scoring',
                    'params' => [
                        'card_number' => $card,
                        'expiry' => EncryptHelper::decryptData($request->info_card['card_valid_date']),
                        'start_date' => $request['start_date'],
                        'end_date' => $request['end_date']
                    ],
                ]);
            }

        }elseif ($isCardTypeHumo) {
            $input = json_encode([
                'jsonrpc' => '2.0',
                'id' => $id,
                'method' => 'humo.scoring',
                'params' => [
                    'card_number' => $card,
                    'expire' => $exp,
                ],
            ]);
        } else {
            $input = json_encode([
                'jsonrpc' => '2.0',
                'id' => $id,
                'method' => 'card.scoring',
                'params' => [
                    'card_number' => $card,
                    'expire' => $exp,
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                ],
            ]);
        }

        Log::channel('cards')->info($input);

        if($config['test_api_scoring_switch']){

            if((CardHelper::checkTypeCard($card)['type'] == 2) && env('HUMO_TO_UNIVERSAL_SCORING_SWITCH')){

                $result = self::backOfficeScoring($input, $isCardTypeHumo, $request->buyer_id);

            }elseif((CardHelper::checkTypeCard($card)['type'] == 1) && env('UZCARD_TO_UNIVERSAL_SCORING_SWITCH')){

                $result = self::backOfficeScoring($input, $isCardTypeHumo, $request->buyer_id);

            }else{

                $result = self::testApiGetScoring($card, EncryptHelper::decryptData($request->info_card['card_valid_date']), $request['start_date'], $request['end_date'], $request->buyer_id);
            }

        }else{

            $result = self::backOfficeScoring($input, $isCardTypeHumo, $request->buyer_id);
        }

        if (isset($result['result'])) {
            Log::channel('cards')->info('scoring from: ' . __CLASS__ . ' ' . __METHOD__ . ' ' . __LINE__);
            $result['scoring'] = UniversalHelper::scoringScore($result['result']);
        } else {
            $result['scoring'] = ['scoring' => 0, 'ball' => 0]; // если нет ответа
        }

        $scoring = new CardScoring();
        $scoring->user_id = $request->buyer_id;
        $scoring->user_card_id = $request->card_id;
        $scoring->period_start = Carbon::parse($request->date_start)->format('Y-m-d H:i:s');
        $scoring->period_end = Carbon::parse($request->date_end)->format('Y-m-d H:i:s');
        $scoring->status = 1;
        $scoring->save();

        $scoringLog = new CardScoringLog();
        $scoringLog->user_id = $request->buyer_id;
        $scoringLog->status = $result['scoring']['scoring'] > 0 ? 1 : 0;
        $scoringLog->card_scoring_id = $scoring->id;
        $scoringLog->card_hash = md5($card); //$infoCard->card_number);
        $scoringLog->request = $input;
        $scoringLog->response = json_encode($result);
        $scoringLog->scoring = $result['scoring']['scoring'] > 0 ? $result['scoring']['scoring'] : 0;
        $scoringLog->ball = $result['scoring']['ball'];
        $scoringLog->scoring_count = 1;
        $scoringLog->save();

        Log::channel('cards')->info(json_encode($result, 1));

        return $result;

        // $query = $cardScoringLog = CardScoringLog::where('user_id',$request->buyer_id)->where('card_hash', md5($card));
        // Log::channel('cards')->info($query->toSql());
        // Log::channel('cards')->info($query->getBindings());

        /* return [
             'status' => 'error'
         ];*/

        // user_id и card_hash
        // $request->scoring_from_server - отмечена флаг запрос с сервера -  !!всегда должны попадать сюда, независимо откуда должны взять запрос
        if (/*$request->scoring_from_server && */ $cardScoringLog = CardScoringLog::where('user_id', $request->buyer_id)->where('card_hash', md5($card))->first()) {

            // проверяем локальную запись скоринг

            $result = json_decode($input, true);
            $card_encrypt = EncryptHelper::encryptData($card);
            $result['params']['card_number'] = $card_encrypt;

            $cardScoringLog->request = json_encode($result, JSON_UNESCAPED_UNICODE);
            $cardScoringLog->save();

            // дни
            $m1 = date('d');
            $m2 = date('d', strtotime($cardScoringLog->updated_at));

            // если дни одинаковые, берем из бд, иначе даем запрос к сервису
            if ($m1 == $m2) {
                $result = json_decode($cardScoringLog->response, true);

                Log::channel('cards')->info('scoring result LOCAL from DB:');
                Log::channel('cards')->info($result);

                Log::channel('cards')->info('scoring from: ' . __CLASS__ . ' ' . __METHOD__ . ' ' . __LINE__);
                $scoring = UniversalHelper::scoringScore($result['result']);

                Log::channel('cards')->info($scoring);
                Log::channel('cards')->info('------------------------scoring end<<<');

                if ((int)$cardScoringLog->scoring == 0) $cardScoringLog->scoring = $scoring['scoring'];
                if ((int)$cardScoringLog->ball == 0) $cardScoringLog->ball = $scoring['ball'];
                $cardScoringLog->save();

                return [
                    'status' => 'success',
                    'data' => $result['result'],
                    'result' => $result['result'],
                    'response' => $result,
                    'request' => $input,
                    'local' => true,
                    'scoring' => $scoring['scoring']
                ];
            }

        } else {
            // scoring log не найден - запись в бд не найдена

        }


    }

    // баланс, телефон, фио
    public static function getCardPhone($request)
    {
        $config = self::buildAuthorizeConfig();

        if($config['test_api_balance_switch']){

            if((CardHelper::checkTypeCard($request->card)['type'] == 2) && env('HUMO_TO_UNIVERSAL_BALANCE_SWITCH')){

                $id = 'test_' . uniqid(rand(), 1);

                $splitCardInfo = CardHelper::getAdditionalCardInfo($request->card, $request->exp);

                $input = json_encode([
                    'jsonrpc' => '2.0',
                    'id' => $id,
                    'method' => 'card.register',
                    'params' => [
                        'card_number' => $splitCardInfo['card'],
                        'expire' => $splitCardInfo['exp'],
                    ],
                ]);

                $result = self::backOffice($input, $request->url_humo);

            } elseif ((CardHelper::checkTypeCard($request->card)['type'] == 1) && env('UZCARD_TO_UNIVERSAL_BALANCE_SWITCH')){

                $id = 'test_' . uniqid(rand(), 1);

                $splitCardInfo = CardHelper::getAdditionalCardInfo($request->card, $request->exp);

                $input = json_encode([
                    'jsonrpc' => '2.0',
                    'id' => $id,
                    'method' => 'card.register',
                    'params' => [
                        'card_number' => $splitCardInfo['card'],
                        'expire' => $splitCardInfo['exp'],
                    ],
                ]);

                $result = self::backOffice($input, $request->url_humo);

            } else {
                $splitCardInfo = CardHelper::getAdditionalCardInfo($request->card, $request->exp);

                $input = json_encode([
                    'jsonrpc' => '2.0',
                    'id' => 'api_card_balance_' . time() . uniqid(rand(), 10),
                    'method' => 'card.register',
                    'params' => [
                        'number' => $splitCardInfo['card'],
                        'expiryDate' => $splitCardInfo['exp'],
                    ],
                ]);
                $result = self::testApiGetBalance($splitCardInfo['card'], $splitCardInfo['exp']);
            }

        }else{

            $id = 'test_' . uniqid(rand(), 1);

            $splitCardInfo = CardHelper::getAdditionalCardInfo($request->card, $request->exp);

            $input = json_encode([
                'jsonrpc' => '2.0',
                'id' => $id,
                'method' => 'card.register',
                'params' => [
                    'card_number' => $splitCardInfo['card'],
                    'expire' => $splitCardInfo['exp'],
                ],
            ]);

            $result = self::backOffice($input, $request->url_humo);
        }


        Log::channel('cards')->info(print_r($input, 1));
        Log::channel('cards')->info(json_encode($result, 1));

        return $result;
    }


    /** перескорить карту (если вип клиент дорегистрируется как не вип)
     * @param Request $request
     * @return array
     */
    public static function cardRescoring(Request $request)
    {

        $buyer = Buyer::where('id', $request->buyer_id)->with('cards')->first();
        $card_number = EncryptHelper::decryptData($buyer->cards[0]->card_number);
        $exp = EncryptHelper::decryptData($buyer->cards[0]->card_valid_date);

        $request->merge([
             'card' => $card_number,
             'exp' => $exp
            ]);
        $info = UniversalController::getCardPhone($request); // Universal
        $card_phone = $info['result']['phone'] ?? false; //номер смс информирования

        if($card_phone){
            $day = date('d', time());
            if ($day < 25) {
                $months = ' -6 month'; // текущий месяц + еще 6 прошедших
            } else {
                $months = ' -5 month'; // текущий месяц + еще 5 прошедших
            }
            $to = date('Ym25', time());
            $from = date('Ym01', strtotime($to . $months));

            $request->merge([
                'info_card' => [
                    'card_number' => $card_number,
                    'card_valid_date' => $exp
                ],
                'start_date' => $from,
                'end_date' => $to,
                'phone' => $buyer->phone,  // ?? user phone
                'card_phone' => $card_phone,  // ?? card phone
            ]);

            $scoring_result = UniversalHelper::getScoring($request);

            Log::channel('cards')->info('scoring-result');
            Log::channel('cards')->info($scoring_result);
            if (isset($scoring_result['response']['result'])) {
                Log::channel('cards')->info('scoring from: ' . __CLASS__ . ' ' . __METHOD__ . ' ' . __LINE__);
                $_scoring = UniversalHelper::scoringScore($scoring_result['response']['result']);
                $scoringResult = $_scoring['scoring'];

                $sum = isset($_scoring['sum']) ? $_scoring['sum'] : $_scoring['scoring'];

                Log::channel('cards')->info('VIP client changed status NOT VIP, changed settings limit by rescoring: ' . $buyer->settings->limit . ' :  on: ' . $sum);
                $buyer->settings->limit = $sum;
                $buyer->settings->balance = $sum;
                $buyer->settings->save();

                Log::channel('cards')->info('SCORING NOT CONFIRM buyer_id: ' . $buyer->id . ' :  sum: ' . $sum . ' ball: ' . $_scoring['ball']);
                $scoring = new CardScoring();
                $scoring->user_id = $buyer->id;
                $scoring->user_card_id = 0; // данная карта не проходит, ее не храним в cards  - ??
                $scoring->period_start = Carbon::parse($from)->format('Y-m-d H:i:s');
                $scoring->period_end = Carbon::parse($to)->format('Y-m-d H:i:s');
                $scoring->status = 1;
                $scoring->save();

                $scoringLog = new CardScoringLog();
                $scoringLog->user_id = $buyer->id;
                $scoringLog->card_scoring_id = $scoring->id;
                $scoringLog->card_hash = md5($card_number);
                $scoringLog->request = $scoring_result['request'];
                $scoringLog->response = json_encode($scoring_result['response'], JSON_UNESCAPED_UNICODE);
                $scoringLog->scoring = $sum;
                $scoringLog->ball = $_scoring['ball'];
                $scoringLog->scoring_count = 1;
                $scoringLog->status = (int)$scoringResult > 0;
                $scoringLog->save();

                // эта карта не пойдет, просим другую
                if ($scoringResult == 0) {
                    $buyer->status = 1;
                    $buyer->save();
                    $buyer->cards[0]->delete(); // удалить карту  - нельзя удалять
                    $buyer->settings->delete();  // чтобы была возможность проскорить новую карту

                    return [
                        'scoringResult' => $scoringResult,
                        'status' => 'error_card_scoring'
                    ];
                }

                $result = [
                    'status' => 'success',
                    'info' => 'card has been rescored'
                ];

            } else {
                return ['status' => 'error_scoring'];
            }
        }

        return $result;
    }

    /** привязать карту
     * @param Request $request
     * @return array
     */
    public function sendSmsCodeUniversal(SendSmsCodeUniversalRequest $request)
    {

        $card = $request->card;
        $current_time = Carbon::now();
        $cardSmsLimit = config('test.card_sms_limit');
        if(Redis::exists(':send-sms:' . $card)) {
            return [
                'status' => 'error',
                'info' => 'card_sms_limit',
                'message' => __('panel/buyer.sms_card_limit', ['cardSmsLimit' => $cardSmsLimit]),
                'access_after' => Redis::get(':send-sms:' . $card)
            ];
        }

        Log::channel('cards')->info('start card.verify universal');

        $config = self::buildAuthorizeConfig();

        $id = 'test_' . uniqid(rand(), 1);

        $splitCardInfo = CardHelper::getAdditionalCardInfo($request->card, $request->exp);

        $input = json_encode([
            'jsonrpc' => '2.0',
            'id' => $id,
            'method' => 'card.register',
            'params' => [
                'card_number' => $splitCardInfo['card'],
                'expire' => $splitCardInfo['exp'],
            ],
        ]);

        $info = UniversalController::getCardPhone($request); // Universal or test

        Log::channel('cards')->info($info);

        $user = Auth::user(); //

        // номер на который регистрировался
        $user_phone = isset($request->phone) ? correct_phone($request->phone) : correct_phone($user->phone); // если продавец создает клиента, берем номер, который вбил продавец
        $card_phone = $info['result']['phone'] ?? false; //номер смс информирования

        if($info['status'] === false) {
            if(isset($info['error']['message']['en'])) {
                $result = [ 'status' => 'error', 'info' => [__('billing/buyer.'.formatToLangVariable($info['error']['message']['en']))] ];
            } else {
                $result = [ 'status' => 'error', 'info' => [__('card.something_went_wrong')] ];
            }
            return $result;
        }

        if($info['status'] !== true) {
            if(isset($info['data']['message']) && $info['data']['message'] == 'Card not found') {
                $result = [ 'status' => 'error', 'info' => [__('card.card_not_found')] ];
            } else {
                $result = [ 'status' => 'error', 'info' => [__('billing/buyer.card_data_is_incorrect_try_later')] ];
            }
            return $result;
        }


        if ($card_phone) {
            if (mb_substr($user_phone, 8, 4) != mb_substr(correct_phone($card_phone), 8, 4)) {
                return ['status' => 'error', 'info' => 'error_phone_not_equals'];
            }

                $isHumo = CardHelper::checkTypeCard($request->card)['type'] == 2;

                if ($config['test_api_balance_switch']) {

                    if ((CardHelper::checkTypeCard($request->card)['type'] == 2) && env('HUMO_TO_UNIVERSAL_BALANCE_SWITCH')) {

                        $balanceResult = self::backOffice($input, $isHumo);

                    } elseif ((CardHelper::checkTypeCard($request->card)['type'] == 1) && env('UZCARD_TO_UNIVERSAL_BALANCE_SWITCH')) {

                        $balanceResult = self::backOffice($input, $isHumo);

                    } else {

                        $inputArr = \GuzzleHttp\json_decode($input);
                        $balanceResult = self::testApiGetBalance($inputArr->params->card_number, $inputArr->params->expire);
                    }

                } else {
                    $balanceResult = self::backOffice($input, $isHumo);
                }


                if (isset($balanceResult['result'])) {
                    $balance = $balanceResult['result']['balance'];
                    if ($balance <= 100) {
                        return [
                            'status' => 'error',
                            'info' => 'empty_balance',
                        ];
                    }
                } else {
                    return [
                        'status' => 'error',
                        'info' => 'failed_to_connect_to_the_service',
                        'message' => 'Не удалось соединиться с сервисом',
                    ];
                }
                // 29.04.2021  Здесь выполняем скоринг карты, если она не проходит, то просим ввести другую карту!


                $day = date('d', time());

                if ($day < 25) {
                    $months = ' -6 month'; // текущий месяц + еще 6 прошедших
                } else {
                    $months = ' -5 month'; // текущий месяц + еще 5 прошедших
                }

                $to = date('Ym25', time());

                $from = date('Ym01', strtotime($to . $months));

                $request->merge([
                    'info_card' => [
                        'card_number' => $request['card'],
                        'card_valid_date' => $request['exp']
                    ],
                    'start_date' => $from,
                    'end_date' => $to,
                    'phone' => $user_phone,  // ?? user phone
                    'card_phone' => $card_phone,  // ?? card phone
                ]);


                $scoring_result = UniversalHelper::getScoring($request);  //->card_number, $request->card_valid_date, $from, $to, true);

                Log::channel('cards')->info('scoring-result');
                Log::channel('cards')->info($scoring_result);

                if (isset($scoring_result['response']['result'])) {
                    Log::channel('cards')->info('scoring from: ' . __CLASS__ . ' ' . __METHOD__ . ' ' . __LINE__);
                    $_scoring = UniversalHelper::scoringScore($scoring_result['response']['result']);
                    $scoringResult = $_scoring['scoring'];

                    if ($request->has('buyer_id')) {  // ??
                        $user_id = $request->buyer_id;
                    } else if ($request->has('phone')) { // если регистрирует вендор
                        $buyer = Buyer::where('phone', $request->phone)->first();
                        $vip = $buyer->vip;
                        $user_id = $buyer->id;
                    } else {   // если регистрируется сам клиент
                        $vip = $user->vip;
                        $user_id = $user->id;
                    }

                    $sum = isset($_scoring['sum']) ? $_scoring['sum'] : $_scoring['scoring'];

                    $last_scoring_obj = new LastScoringLog($user_id, $request->info_card['card_number']);
                    $cardScoringLog = $last_scoring_obj->getLastScoring();
                    // проверяем локальную запись скоринг
                    if ($cardScoringLog && $last_scoring_obj->getMonthDifference() < 1) {
                        //not save
                    } else {
                        Log::channel('cards')->info('SCORING NOT CONFIRM buyer_id: ' . $user->id . ' :  sum: ' . $sum . ' ball: ' . $_scoring['ball']);
                        $scoring = new CardScoring();
                        $scoring->user_id = $user_id;
                        $scoring->user_card_id = 0; // данная карта не проходит, ее не храним в cards  - ??
                        $scoring->period_start = Carbon::parse($from)->format('Y-m-d H:i:s');
                        $scoring->period_end = Carbon::parse($to)->format('Y-m-d H:i:s');
                        $scoring->status = 1;
                        $scoring->save();

                        $scoringLog = new CardScoringLog();
                        $scoringLog->user_id = $user_id;// $user->id;
                        $scoringLog->card_scoring_id = $scoring->id;
                        $scoringLog->card_hash = md5(str_replace(' ', '', $request['card']));
                        $scoringLog->request = $scoring_result['request'];
                        $scoringLog->response = json_encode($scoring_result['response'], JSON_UNESCAPED_UNICODE);
                        $scoringLog->scoring = $sum;
                        $scoringLog->ball = $_scoring['ball'];
                        $scoringLog->scoring_count = 1;
                        $scoringLog->status = (int)$scoringResult > 0;
                        $scoringLog->save();
                    }
                } else {
                    return ['status' => 'error_scoring'];
                }


                // если клиент вип - то все равно пропускаем карту
                if ($scoringResult == 0 && $vip == 0) {
                    return [
                        'scoringResult' => $scoringResult,
                        'vip' => $vip,
                        'status' => 'error_card_scoring'
                    ];
                }

                $msg = "Kod: :code. resusnasiya.uz kartani bog'lash uchun ruxsat so'radi **** " . mb_substr($request['card'], -4)
                    . ' Tel: ' . callCenterNumber(2)
                ;
                $res = $this->sendSmsCode($request, true, $msg, 6);

            if (isset($res['status'])) {
                $result = $res;
            } else {
                $result = [
                    'status' => 'error',
                    'info' => 'service is not available'
                ];
            }
        } elseif($info['status'] === false) {
            if(isset($info['error']['message']['en'])) {
                $result = ['status' => 'error', 'info' => [__('billing/buyer.'). formatToLangVariable($info['error']['message']['en'])]];
            }
        } else {
            if(isset($info['data']['message'])) {
                $result = [ 'status' => 'error', 'info' => [__('billing/buyer.card_data_is_incorrect_try_later')] ];
            } else {
                $result = ['status' => 'error', 'info' => 'error_card_sms_off'];
            }
        }


        Log::channel('cards')->info('end humo.verify');

        if($result['status'] === 'success') {
            $access_after = $current_time->addSeconds($cardSmsLimit)->toISOString();
            Redis::set(':send-sms:' . $card, $access_after, "ex", $cardSmsLimit);
            $result['access_after'] = $access_after;
        }
        // TODO: SMS Limit on error too.

        return $result;
    }

    /**
     * Verify sms code
     *
     * @param Request $request
     * @return mixed
     */
    public function checkSmsCodeUniversal(Request $request)
    {
        Log::info('REQUEST check-sms-code-uz:');
        Log::info($request);
        if (!$request->has('card_number') || !$request->has('card_valid_date')) {
            $this->result = [
                "status" => 'error',
                "code"   => '404',
                'error'  => __('api.buyer_not_found')
            ];
            return $this->result();
        }
        $encSms = $this->checkSmsCode($request);
        if ($encSms['status'] == 'success') {
            $this->result['status'] = 'success';
            $this->result['data'] = $request->all();
            if($request->has('buyer_id')) {
//              Переписываем номер телефона которые отправлено смс код
                if($encSms['data']['user_id']!=$request->buyer_id){
                    $this->result = [
                        "status" => 'error',
                        "code" => '404',
                        'error' => __('api.buyer_not_found')
                    ];
                    return $this->result();
                }
            }
            $cards = new CardController();
            $this->result = $cards->add($request);
        } else {
            $this->result['status'] = 'error';
        }
        return $this->result();
    }

    /**
     * add humo cards to users cards by CRON
     *
     * @param Request $request
     * @return mixed
     */
    public function addCardsHumo()
    {

        $request = new Request();
        $cards_quid = [];

        $contracts = Contract::whereIn('status', [3, 4, 1])->select('user_id')->with('buyer')->whereHas('buyer', function ($q) {
            $q->where('status', 4);
        })->distinct('user_id')->get();


        foreach ($contracts as $contract) {
            if (isset($contract->buyer->cards)) {              // если такая карта уже есть, не добавляем
                foreach ($contract->buyer->cards as $card) {
                    $cards_quid[] = $card->guid;
                }
            }
        }


        foreach ($contracts as $contract) {

            if (isset($contract->buyer)) {
                $phone = $contract->buyer->phone;


                $request->merge([
                    'phone' => $phone,
                    'type' => 2
                ]);

                $result = $this->getCardsList($request);

                if (isset($result['result']['cards'])) {
                    $new_cards = $result['result']['cards'];


                    foreach ($new_cards as $new_card) {
                        if (isset($new_card['number'])) {
                            $card_number = EncryptHelper::encryptData($new_card['number']);
                            $card_valid_date = EncryptHelper::encryptData($new_card['expire']);
                            $card_phone = EncryptHelper::encryptData($new_card['phone']);
                            $type = EncryptHelper::encryptData('HUMO');
                            $guid = md5($new_card['number']);

                            if (!in_array($guid, $cards_quid)) {
                                $request->merge([
                                    'info_card' => [
                                        'card_number' => $card_number,
                                        'card_valid_date' => $card_valid_date
                                    ]
                                ]);

                                $resl = $this->getCardInfo($request);
                                $user_cards = new Card();
                                $user_cards->user_id = $contract->user_id;
                                $user_cards->card_name = $resl['result']['owner'];
                                $user_cards->card_number = $card_number;
                                $user_cards->card_valid_date = $card_valid_date;
                                $user_cards->phone = $card_phone;
                                $user_cards->type = $type;
                                $user_cards->guid = $guid;
                                $user_cards->status = 0;
                                $user_cards->hidden = 0;
                                $user_cards->card_number_prefix = substr($new_card['number'], 0, 8);

                                if ($user_cards->save()) {
                                    $cards_quid[] = $guid;
                                }
                            }
                        } else {
                            foreach ($new_card as $new_card) {
                                $card_number = EncryptHelper::encryptData($new_card['number']);
                                $card_valid_date = EncryptHelper::encryptData($new_card['expire']);
                                $card_phone = EncryptHelper::encryptData($new_card['phone']);
                                $type = EncryptHelper::encryptData('HUMO');
                                $guid = md5($new_card['number']);

                                if (!in_array($guid, $cards_quid)) {
                                    $request->merge([
                                        'info_card' => [
                                            'card_number' => $card_number,
                                            'card_valid_date' => $card_valid_date
                                        ]
                                    ]);

                                    $resl = $this->getCardInfo($request);
                                    $user_cards = new Card();
                                    $user_cards->user_id = $contract->user_id;
                                    $user_cards->card_name = $resl['result']['owner'];
                                    $user_cards->card_number = $card_number;
                                    $user_cards->card_valid_date = $card_valid_date;
                                    $user_cards->phone = $card_phone;
                                    $user_cards->type = $type;
                                    $user_cards->guid = $guid;
                                    $user_cards->status = 0;
                                    $user_cards->hidden = 0;
                                    $user_cards->card_number_prefix = substr($new_card['number'], 0, 8);

                                    if ($user_cards->save()) {
                                        $cards_quid[] = $guid;
                                    }
                                }
                            }
                        }


                    }
                }
            }
        }
        return true;

    }


    /**
     * Получить авторизационныя данные для шлюза
     * @return array
     */
    private static function buildAuthorizeConfig(): array
    {
        $config = [
            'login' => config('test.universal_login'),
            'password' => config('test.universal_password'),

            'url_humo' => config('test.universal_url_humo'),
            'url_humo_balance' => config('test.universal_balance_humo'),
            'url_uzcard' => config('test.universal_url_uzcard'),

            'universal_token_uzcard' => config('test.universal_token_uzcard'),
            'universal_token_humo' => config('test.universal_token_humo'),

            'terminal_id_uzcard' => config('test.universal_terminal_id_uzcard'),
            'merchant_id_uzcard' => config('test.universal_merchant_id_uzcard'),

            'terminal_id_humo' => config('test.universal_terminal_id_humo'),
            'merchant_id_humo' => config('test.universal_merchant_id_humo'),

            'terminal_uzcard_autopay' => config('test.universal_terminal_uzcard_autopay'),
            'merchant_uzcard_autopay' => config('test.universal_merchant_uzcard_autopay'),

            'test_api_balance_switch'=> config('test.test_api_balance_switch'),
            'test_api_scoring_switch'=> config('test.test_api_scoring_switch'),
            'test_api_payment_and_cancel_switch' => config('test.test_api_payment_and_cancel_switch'),
            'test_api_login' => config('test.test_api_login'),
            'test_api_password' => config('test.test_api_password'),
            'test_api_url_card_balance' => config('test.test_api_url_card_balance'),
            'test_api_url_card_scoring' => config('test.test_api_url_card_scoring'),
            'test_api_url_card_payment' => config('test.test_api_url_card_payment'),
            'test_api_url_card_payment_cancel' => config('test.test_api_url_card_payment_cancel'),

            'test_api_uzcard_merchant' => config('test.test_api_uzcard_merchant'),
            'test_api_uzcard_terminal'=> config('test.test_api_uzcard_terminal'),
            'test_api_humo_merchant'=> config('test.test_api_humo_merchant'),
            'test_api_humo_terminal'=> config('test.test_api_humo_terminal'),

        ];

        return $config;
    }


    public static function testApiGetBalance($card_number, $expiry_date){

        $config = self::buildAuthorizeConfig();
        //название параметров number, expiryDate отличаются поэтому создаем новый input
        $input = json_encode([
            'jsonrpc' => '2.0',
            'id' => 'api_card_balance_'. time() . uniqid(rand(), 10),
            'method' => 'card.register',
            'params' => [
                'number' => $card_number,
                'expiryDate' => $expiry_date,
            ],
        ]);

        $curl = curl_init($config['test_api_url_card_balance']);

        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, $config['test_api_login'].':'.$config['test_api_password']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json')
        );

        curl_setopt($curl, CURLOPT_POSTFIELDS, $input);

        try {
            $result = curl_exec($curl);
            $result = json_decode($result, JSON_UNESCAPED_UNICODE);  // проверить
        } catch (\Exception $e) {
            $result = [
                'status' => 'error'
            ];
            Log::info('test api error');
            Log::info($e);
        }
        return $result;
    }


    public static function testApiGetScoring($card_number, $expiry_date, $start_date, $end_date, $buyer_id){

        $config = self::buildAuthorizeConfig();

        $input = json_encode([
            'jsonrpc' => '2.0',
            'id' => 'api_scoring_test_'. time() . uniqid(rand(), 10),
            'method' => 'card.scoring',
            'params' => [
                'card_number' => $card_number,
                'expiry' => $expiry_date,
                'start_date' => $start_date,
                'end_date' => $end_date
            ],
        ]);

        $encodedInput = \GuzzleHttp\json_decode($input);

        $curl = curl_init($config['test_api_url_card_scoring']);

        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, $config['test_api_login'].':'.$config['test_api_password']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json')
        );

        curl_setopt($curl, CURLOPT_POSTFIELDS, $input);

        $result = curl_exec($curl);
        $result = json_decode($result, JSON_UNESCAPED_UNICODE);

        if (isset($result['result'])) {

            $newResult = [];

            foreach($result['result'] as $item){

                if( strtotime($item['date']) >= strtotime( date('M-Y', strtotime($encodedInput->params->start_date)) ) && strtotime($item['date']) <= strtotime( date( 'M-Y', strtotime($encodedInput->params->end_date) ) ) ){
                    $newResult[$item['date']] = $item['salaries']['amount'] + $item['p2pCredit']['amount'];
                }

            }
            $result['result'] = $newResult;
        }
        $result['status'] = true;

        //save scoring request/response to card_scoring_request_logs table
        new ScoringRequestLog($buyer_id, json_decode($input, true)['params']['card_number'], CardHelper::checkTypeCard(json_decode($input, true)['params']['card_number'])['type'] == 2 ? 'humo' : 'uzcard', json_decode($input, true), $result, 'test_api');

        return $result;
    }


    public static function testApiPayment($card_number, $expiry_date, $amount, $merchant, $terminal){

        $config = self::buildAuthorizeConfig();

        $input = json_encode([
            'jsonrpc' => '2.0',
            'id' => 'api_card_payment_'. time() . uniqid(rand(), 10),
            'method' => 'payment',
            'params' => [
                'pan' => $card_number,
                'expire' => $expiry_date,
                'amount' => $amount,
                'merchant' => $merchant,
                'terminal' => $terminal
            ],
        ]);

        $curl = curl_init($config['test_api_url_card_payment']);

        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, $config['test_api_login'].':'.$config['test_api_password']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json')
        );

        curl_setopt($curl, CURLOPT_POSTFIELDS, $input);

        $result = curl_exec($curl);
        $result = json_decode($result, JSON_UNESCAPED_UNICODE);

        return $result;
    }


    public static function testApiCancel($payment_id){

        $config = self::buildAuthorizeConfig();

        $input = json_encode([
            'jsonrpc' => '2.0',
            'id' => 'api_card_payment_'. time() . uniqid(rand(), 10),
            'method' => 'payment.cancel',
            'params' => [
                'payment_id' => $payment_id
            ],
        ]);

        $curl = curl_init($config['test_api_url_card_payment_cancel']);

        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, $config['test_api_login'].':'.$config['test_api_password']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json')
        );

        curl_setopt($curl, CURLOPT_POSTFIELDS, $input);

        $result = curl_exec($curl);
        $result = json_decode($result, JSON_UNESCAPED_UNICODE);

        return $result;

    }
}
