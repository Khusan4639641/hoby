<?php

namespace App\Helpers;

use App\Classes\Scoring\LastScoringLog;
use App\Classes\Scoring\ScoringRequestLog;
use App\Facades\OldCrypt;
use App\Models\CardScoring;
use App\Models\CardScoringLog;
use App\Models\User;
use App\Classes\Scoring\ScoreCalculate;
use App\Classes\Scoring\ScoringData;
use App\Models\Payment;
use App\Models\PaymentLog;
use App\Services\ScoringService;
use Illuminate\Support\Facades\Log;

class UniversalHelper // extends CoreController
{
    // 12.04 - получены новые токены для test
    private static $login = 'test';
    private static $password = 'NQVOY6O9ijTQBboiClRNMAt';


    private static $token = '$2y$10$W6x5ftJGFABvaI92YElS2.GXpBsOmRkuxXhJwW9OH7HoCEcFk22ZS';
    private static $token_humo = '$2y$10$cZ388HY8AzTSmGg7s4N87.tkli4xCSy0vwNVZYfWeK6vHRglsTFI6';

    private static $url = 'https://core.unired.uz/api/v1/unired';
    private static $url_humo = 'https://core.unired.uz/api/v1/humo';

    private static $merchant_uzcard = '90488584';
    private static $terminal_uzcard = '92408712';

    private static $merchant_humo = '009960536923402';
    private static $terminal_humo = '2361054Y';

    //
    public static function loginUzcard()
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

        $card = EncryptHelper::decryptData($request->info_card['card_number']);
        $exp = EncryptHelper::decryptData($request->info_card['card_valid_date']);
        $exp = CardHelper::getAdditionalCardInfo($card, $exp)['exp'];

        $id = 'test_' . uniqid(rand(), 1);


        $input = json_encode([
            'jsonrpc' => '2.0',
            'id' => $id,
            'method' => 'card.register',
            'params' => [
                'card_number' => $card,
                'expire' => $exp,
            ],
        ]);


        $result = self::backOffice($input, $request->url_humo);

        Log::channel('cards')->info(print_r($input, 1));
        Log::channel('cards')->info(print_r($result, 1));

        return $result;

    }

    // список всех карт по номеру телефона
    public static function getCardsList($phone, $url_humo)
    {
        $id = 'test_' . uniqid(rand(), 1);
        $input = json_encode([
            'jsonrpc' => '2.0',
            'method' => 'card.get',
            'params' => [
                'phone' => $phone,
            ],
            'id' => $id,
        ]);
        $result = self::backOffice($input, $url_humo);
        Log::channel('cards')->info(print_r($input, 1));
        Log::channel('cards')->info(print_r($result, 1));
        return $result;
    }

    //
    public function payment($request)
    {

        $card = EncryptHelper::decryptData($request->info_card['card_number']);
        $exp = EncryptHelper::decryptData($request->info_card['card_valid_date']);
        $exp = CardHelper::getAdditionalCardInfo($card, $exp)['exp'];

        $id = 'test_' . uniqid(rand(), 1);
        $url_humo = $request->url_humo;

        /*
        if($url_humo){
            $merchant = self::$merchant_humo;
            $terminal= self::$terminal_humo;
        }else{
            $merchant = self::$merchant_uzcard;
            $terminal= self::$terminal_uzcard;
        }
        */

        $config = self::buildAuthorizeConfig();

        if ($url_humo) {
            $merchant = $config['merchant_id_humo'];
            $terminal = $config['terminal_id_humo'];
        }else{
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
                'amount' => $request->sum, // проверить
                'merchant' => $merchant,
                'terminal' => $terminal,
            ],
        ]);


        $result = self::backOffice($input, $request->url_humo);

        Log::channel('cards')->info(print_r($input, 1));
        Log::channel('cards')->info(print_r($result, 1));

        $setData = self::setData($card, $request, $input, $result);

        if ($setData) {
            $this->result['status'] = 'success';
            $this->result['data'] = $result;
        } else {
            $this->result['status'] = 'error';
            $this->result['data'] = $result;
            $this->message('danger', __('card.uz_payment_error'));
        }

        return $this->result;

    }


    private static function setData($card, $request, $input, $result)
    {

        $type = CardHelper::checkTypeCard($card)['name'];

        if (isset($result['result']['payment']['id'])) {  // только успешные
            $payment = new Payment;
            if ($request->has('schedule_id'))
                $payment->schedule_id = $request->schedule_id;
            if ($request->has('type'))
                $payment->type = 'auto';  // автосписания?
            if ($request->has('order_id'))
                $payment->order_id = $request->order_id;
            if ($request->has('contract_id'))
                $payment->contract_id = $request->contract_id;

            $payment->card_id = $request->card_id;
            $payment->amount = $request->sum / 100;
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

            $payment->schedule_id = $refund->schedule_id;
            $payment->type = 'refund';
            $payment->order_id = $refund->order_id;
            $payment->contract_id = $refund->contract_id;

            $payment->card_id = $refund->card_id;
            $payment->amount = $refund->amount * -1;
            $payment->user_id = $refund->user_id;
            $payment->payment_system = $refund->payment_system;

            $paymentLog = new PaymentLog;

            $paymentLog->request = $input;
            $paymentLog->response = json_encode($result);
            $payment->transaction_id = $refund->transaction_id;
            $payment->status = 1;
            $paymentLog->status = 1;


            $payment->save();
            $paymentLog->payment_id = $payment->id;
            $paymentLog->save();

            return true;
        }
        return false;
    }


    //
    public function reverse($request)
    {

        $id = 'test_' . uniqid(rand(), 1);

        if ($request->url_humo) {
            $input = json_encode([
                'jsonrpc' => '2.0',
                'id' => $id,
                'method' => 'payment.cancel',
                'params' => [
                    'payment_id' => $request->transaction_id,
                    'uuid' => $request->uuid,
                ],
            ]);
        } else {
            $input = json_encode([
                'jsonrpc' => '2.0',
                'id' => $id,
                'method' => 'payment.cancel',
                'params' => [
                    'payment_id' => $request->transaction_id,
                ],
            ]);
        }


        $result = self::backOffice($input, $request->url_humo);

        Log::channel('cards')->info(print_r($input, 1));
        Log::channel('cards')->info(print_r($result, 1));

        $setDataCancel = self::setDataCancel($request, $input, $result);

        if ($setDataCancel) {
            $this->result['status'] = 'success';
            $this->result['data'] = $result;
        } else {
            $this->result['status'] = 'error';
            $this->result['data'] = $result;
            $this->message('danger', __('card.uz_payment_error'));
        }

        return $this->result;

    }

//
    public static function paymentCheck($request)
    {

        $id = 'test_' . uniqid(rand(), 1);

        if ($request->url_humo) {
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


        $result = self::backOffice($input, $request->url_humo);


        return $result;

    }


    public static function backOffice($input, $url_humo = false)
    {

        $config = self::buildAuthorizeConfig();

        if ($url_humo) {
            $curl = curl_init($config['url_humo']);
            $token = OldCrypt::decryptString($config['universal_token_humo']);

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

        return $result;
    }

    private static function backOfficeScoring($input, $url_humo = false, $buyer_id)
    {
        // работает по url узкарда, но только по токену хумо, но с любыми картами

        $isHumoScoringFake = (bool) env('IS_HUMO_SCORING_FAKE', false);
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
//            $token = OldCrypt::decryptString($config['universal_token_uzcard']); // так же как в CardContoller
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
                foreach ($result['result'] as $key => $item)
                {
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

    public static function getScoringV2($request)
    {
        Log::channel('cards')->info('UniversalHelper->getScoringV2()');
        $card_number = (string)$request->get('card_number');
        $card_valid_date = (string)$request->get('card_valid_date');
        if (!env('SKIP_test', false)) {
            $request = ScoringHelper::gettest($card_number, $card_valid_date);
            $scoring = ScoringService::test($request);
            if (!$scoring['error']) {
                Log::channel('cards')->info('Успешный ответ скоринга в системе test' .PHP_EOL. json_encode($scoring));
                return $scoring;
            }
        }
        Log::channel('cards')->info('Запрос скоринга в системе Universal');
        $request = ScoringHelper::getUniversal($card_number, $card_valid_date);
        $scoring = ScoringService::Universal($request);
        Log::channel('cards')->info('Ответ скоринга в системе Universal' .PHP_EOL. json_encode($scoring));
        return $scoring;
    }

    public static function getScoring($request)
    {

        Log::channel('cards')->info('UniversalHelper->getScoring()');

        $id = 'test_' . uniqid(rand(), 1);

        $cardInfo = CardHelper::getAdditionalCardInfo($request->card, $request->exp);

        $isCardTypeHumo = CardHelper::checkTypeCard($request->info_card['card_number'])['type'] == 2;

        $config = self::buildAuthorizeConfig();

        if($config['test_api_scoring_switch'])
        {

            if((CardHelper::checkTypeCard($request->info_card['card_number'])['type'] == 2) && env('HUMO_TO_UNIVERSAL_SCORING_SWITCH')){

                $input = json_encode([
                    'jsonrpc' => '2.0',
                    'id' => $id,
                    'method' => 'humo.scoring',
                    'params' => [
                        'card_number' => $cardInfo['card'],
                        'expire' => $cardInfo['exp'],
                    ],
                ]);

            }elseif((CardHelper::checkTypeCard($request->info_card['card_number'])['type'] == 1) && env('UZCARD_TO_UNIVERSAL_SCORING_SWITCH')){

                $input = json_encode([
                    'jsonrpc' => '2.0',
                    'id' => $id,
                    'method' => 'card.scoring',
                    'params' => [
                        'card_number' => $cardInfo['card'],
                        'expire' => $cardInfo['exp'],
                        'start_date' => $request['start_date'],
                        'end_date' => $request['end_date'],
                    ],
                ]);

            }
            else
            {
                if(isset($request->exp)) {
                    $cardExp = $cardInfo['exp'];
                } else {
                    $cardExp = $request->info_card['card_valid_date'];
                }

                $input = json_encode([
                    'jsonrpc' => '2.0',
                    'id' => 'api_humo_'.$id,
                    'method' => 'card.scoring',
                    'params' => [
                        'card_number' => $cardInfo['card'],
                        'expiry' => $cardExp,
                        'start_date' => $request['start_date'],
                        'end_date' => $request['end_date']
                    ],
                ]);
            }
        }
        elseif ($isCardTypeHumo)
        {

            $input = json_encode([
                'jsonrpc' => '2.0',
                'id' => $id,
                'method' => 'humo.scoring',
                'params' => [
                    'card_number' => $cardInfo['card'],
                    'expire' => $cardInfo['exp'],
                ],
            ]);
        }
        else
        {

            $input = json_encode([
                'jsonrpc' => '2.0',
                'id' => $id,
                'method' => 'card.scoring',
                'params' => [
                    'card_number' => $cardInfo['card'],
                    'expire' => $cardInfo['exp'],
                    'start_date' => $request['start_date'],
                    'end_date' => $request['end_date'],
                ],
            ]);
        }


        Log::channel('cards')->info('>>>scoring start-----------------');

        Log::channel('cards')->info($input);
        Log::channel('cards')->info('scoring result from remote service:');

        $user_id = 0;
        if ($request->has('buyer_id')) {
            $user_id = $request->buyer_id;
        } elseif ($user = User::wherePhone($request->phone)->first()) {
            $user_id = $user->id;
            Log::channel('cards')->info('buyer ID: '.$user_id);
        }

        $last_scoring_obj = new LastScoringLog($user_id, $request->info_card['card_number']);
        $cardScoringLog = $last_scoring_obj->getLastScoring();

        // проверяем локальную запись скоринг
        // user_id и card_hash
        if ($cardScoringLog && $last_scoring_obj->getMonthDifference() < 1 ) {

            $result = json_decode($cardScoringLog->response, true);

            Log::channel('cards')->info($input);
            Log::channel('cards')->info('scoring result LOCAL from DB:');
            Log::channel('cards')->info($result);

            Log::channel('cards')->info('scoring from: ' . __CLASS__ . ' ' . __METHOD__ . ' ' . __LINE__);
            $scoring = UniversalHelper::scoringScore($result['result']);

            Log::channel('cards')->info($scoring);
            Log::channel('cards')->info('------------------------scoring end<<<');

            $cardScoringLog->scoring = $scoring['scoring'];
            $cardScoringLog->ball = $scoring['ball'];
            $cardScoringLog->save();

            return [
                'status' => 'success',
                'result' => $result['result'],
                'response' => $result,
                'request' => $input,
                'local' => true,
                'scoring' => $scoring,
            ];
        }else{

            if($config['test_api_scoring_switch']){

                if((CardHelper::checkTypeCard($request->info_card['card_number'])['type'] == 2) && env('HUMO_TO_UNIVERSAL_SCORING_SWITCH')){

                    $result = self::backOfficeScoring($input, $isCardTypeHumo, $user_id);

                }elseif((CardHelper::checkTypeCard($request->info_card['card_number'])['type'] == 1) && env('UZCARD_TO_UNIVERSAL_SCORING_SWITCH')){

                    $result = self::backOfficeScoring($input, $isCardTypeHumo, $user_id);

                }else{

                    $result = self::testApiGetScoring($cardInfo['card'], $cardExp, $request['start_date'], $request['end_date'], $user_id);
                }

            }else{

                $result = self::backOfficeScoring($input, $isCardTypeHumo, $user_id);
            }
        }




        Log::channel('cards')->info($result);
        Log::channel('cards')->info('------------------------scoring end<<<');

        if (isset($result['status'])) {
            Log::info('IN status scoring: ' . $result['status']);
        } else {
            Log::info('IN status scoring: ' . $result);
        }

        $scoring_result = [
            'scoring' => 0,
            'ball' => 0,
        ];

        if (isset($result['result'])) {
            Log::channel('cards')->info('scoring from: ' . __CLASS__ . ' ' . __METHOD__ . ' ' . __LINE__);
            $scoring_result = UniversalHelper::scoringScore($result['result']);
        }

        if (isset($result['status']) && $result['status'] == 'true' && isset($result['result'])) {
            return [
                'status' => 'success',
                'data' => $result['result'],
                'response' => $result,
                'request' => $input,
                'local' => false,
                'scoring' => $scoring_result
            ];
        }

        return [
            'status' => 'error',
            'data' => null,
            'response' => $result,
            'request' => $input,
            'local' => false,
            'scoring' => [
                'scoring' => 0,
                'ball' => 0,
            ]
        ];

    }

    // 08.04.2021 определение суммы рассрочки - ожидаем:  $scoringData['result']
    public static function scoringScore($scoringData){

        Log::info('Method: scoringScore');
        Log::info($scoringData);

        $isCorrect = false;
        foreach ($scoringData as $key => $item) {
            if (!is_int($key)) {
                $isCorrect = true;
                break;
            }
        }
        $scoringMaxMonths = env('SCORING_MAX_MONTHS', 3);
        if ($isCorrect) {
            Log::info("Scoring by months");
            $scoreCalculate = new ScoreCalculate((new ScoringData($scoringData, env('SCORING_BORDER_DAY_OF_CURRENT_MONTH', 20), $scoringMaxMonths))->result(), $scoringMaxMonths);
        } else {
            Log::info("Scoring by indexes");
            $scoreCalculate = new ScoreCalculate($scoringData, $scoringMaxMonths);
        }

        $result = ['scoring' => $scoreCalculate->getScore(), 'ball' => $scoreCalculate->getBall()];
        Log::info($result);

        return $result;

        /**  ответ скоринга
        "result" => array [▼
        "Apr-2021" => 200000000
        "Mar-2021" => 1684178000
        "Feb-2021" => 1407108000
        "Jan-2021" => 354600000
        "Dec-2020" => 1866864800
        "Nov-2020" => 1000687200
        "Oct-2020" => 100000000
        ] */

            $i = 0;
            $sum = [
                1 => 0,
                2 => 0,
                3 => 0,
                4 => 0,
                5 => 0,
                6 => 0
            ];

            /*$scoring_sum = [
                1 => 1000000,
                2 => 3000000,
                3 => 6000000,
                4 => 9000000,
                5 => 12000000,
                6 => 15000000
            ];*/

        $scoring_sum = Config::get('test.scoring_sum');


            $d = date('d', time()); // текущий день месяца
            $day = $d < 25;

            $s = 0;
            if(is_array($scoringData) && count($scoringData)>0) {

                foreach ($scoringData as $month => $summ) {
                    // пропустить последний месяц, т.к. он не полный или день до 25
                    if($i == 0 && (count($scoringData) > 6 || $day)) {
                        // проверка , если сумма соответствует минимальной, то учитывать данный месяц
                        // if( $summ<500000000 && $summ<400000000 && $summ<300000000 && $summ<200000000 && $summ<75000000 && $summ<35000000 ){
                        if($summ < 35000000){
                            continue; // пропускаем, сумма за последний месяц меньше допустимой
                        }
                    }

                    // сумма передается в тийинах и проверяется также тийинами
                    $sum[1] += (int)($summ >= 35000000);  // для 1 млн
                    $sum[2] += (int)($summ >= 75000000);  // для 3 млн
                    $sum[3] += (int)($summ >= 200000000);  // для 6 млн
                    $sum[4] += (int)($summ >= 300000000);  // для 9 млн
                    $sum[5] += (int)($summ >= 400000000);  // для 12 млн
                    $sum[6] += (int)($summ >= 500000000);  // для 15 млн

                    $s += $summ; // общая сумма
                    $i++;
                }

                Log::info('баллы скоринга');
                $res = '';
                $limit = [1=>'1M',2=>'3M',3=>'6M',4=>'9M',5=>'12M',6=>'15M'];
                foreach ($sum as $k=>$item){
                    $res .= $limit[$k] . ': ' . $item . "\n";
                }
                Log::info($res);

                if ($sum[6] > 4)    return ['scoring'=>15000000,'ball'=>$sum[6]];
                if ($sum[5] > 4)    return ['scoring'=>12000000,'ball'=>$sum[5]];
                if ($sum[4] > 4)    return ['scoring'=>9000000,'ball'=>$sum[4]];
                if ($sum[3] > 4)    return ['scoring'=>6000000,'ball'=>$sum[3]];
                if ($sum[2] > 4 && $s>=500000000 ) return ['scoring'=>3000000,'ball'=>$sum[2]];
                if ($sum[1] > 4 && $s>=200000000 ) return ['scoring'=>1000000,'ball'=>$sum[1]];

                $result = '';
                for ( $s=6; $s>0; $s--){
                    if($sum[$s]>=2){
                        $result = ['scoring'=>0, 'sum'=>$scoring_sum[$s],'ball'=>$sum[$s]];
                        break;
                    }
                }

                Log::channel('cards')->info('scoring not set result:');
                Log::channel('cards')->info($result);


                return $result=='' ? ['scoring'=>0,'ball'=>0] : $result;

            }else{
                Log::info("NO SCORING");

            }

        return ['scoring'=>0,'ball'=>0];

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
            'url_uzcard' => config('test.universal_url_uzcard'),

            'universal_token_uzcard' => config('test.universal_token_uzcard'),
            'universal_token_humo' => config('test.universal_token_humo'),

            'terminal_id_uzcard' => config('test.universal_terminal_id_uzcard'),
            'merchant_id_uzcard' => config('test.universal_merchant_id_uzcard'),

            'terminal_id_humo' => config('test.universal_terminal_id_humo'),
            'merchant_id_humo' => config('test.universal_merchant_id_humo'),

            'test_api_scoring_switch'=> config('test.test_api_scoring_switch'),
            'test_api_login' => config('test.test_api_login'),
            'test_api_password' => config('test.test_api_password'),
            'test_api_url_card_scoring' => config('test.test_api_url_card_scoring'),
        ];

        return $config;
    }


    public static function testApiGetScoring($card_number, $expiry_date, $start_date, $end_date, $buyer_id){

        $config = self::buildAuthorizeConfig();

        $input = json_encode([
            'jsonrpc' => '2.0',
            'id' => 'api_card_scoring_'. time() . uniqid(rand(), 10),
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


}
