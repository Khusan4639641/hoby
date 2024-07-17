<?php

namespace App\Http\Controllers\Core;

use App\Helpers\CardHelper;
use App\Helpers\EncryptHelper;
use App\Helpers\SmsHelper;
use App\Models\Buyer;
use App\Models\CardPnfl;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Traits\SmsTrait;
use Illuminate\Support\Facades\Redis;
use App\Http\Controllers\Core\CardController;
use mysql_xdevapi\Exception;


class UniversalPnflController extends CoreController
{

    // логин и пароль для получения токена для автосписаний со всех карт клиента
    private static $login_auto = 'test';
    private static $password_auto = '#^c!';

    private static $url = '';

    const PASSWORD = 620285;

    // генерация токена на 60 дней
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

    // получить client_id
    public static function getClientId($request)
    {

        $input = json_encode([
            'jsonrpc' => '2.0',
            'method' => 'client.create',
            'params' => [
                'pnfl' => $request->pnfl,
                'tin' => '111111111',
                'lastName' => $request->lastName,
                'firstName' => $request->firstName,
                'middleName' => $request->middleName,
                'birthDate' => $request->birthDate,
                'passportSeries' => $request->passportSeries,
                'passportNumber' => $request->passportNumber,
                'passportIssueDate' => $request->passportIssueDate,
                'passportExpDate' => $request->passportExpDate,
            ],
        ]);


        $result = self::backOffice($input);

        Log::channel('payment_pnfl')->info(print_r($input, 1));
        Log::channel('payment_pnfl')->info(print_r($result, 1));

        return $result;


    }

    // получить все карты клиента
    public static function getCards($request)
    {

        $input = json_encode([
            'jsonrpc' => '2.0',
            'method' => 'client.cards',
            'params' => [
                'client_id' => $request->clientId,
            ],
        ]);

        $result = self::backOffice($input);
        //dd($result);

        Log::channel('payment_pnfl')->info(print_r($input, 1));
        Log::channel('payment_pnfl')->info(print_r($result, 1));

        return $result;

    }

    // получить информацию в реальном времени всех карт клиента
    public static function getInfo($request)
    {
        $info = [];

        $input = json_encode([
            'jsonrpc' => '2.0',
            'method' => 'client.cards',
            'params' => [
                'client_id' => $request->clientId,
            ],
        ]);

        $result = self::backOffice($input);
        if(isset($result['status']) && $result['status'] == true){

            if (isset($result['result'])) {
                $new_cards = $result['result'];

                foreach ($new_cards as $key => $card) {
                    $info['balance'][$card['id']] = $card['balance'];  // в тиинах
                    $info['status'][$card['id']] = $card['status'];  // доступна или нет для списания
                    $info['card_phone'][$card['id']] = $card['phone'];  // тел смс информирования
                }
            }
        }
        return $info;

    }

    // балансе по конкретной карте
    public static function getBalance($request)
    {

        $input = json_encode([
            'jsonrpc' => '2.0',
            'method' => 'balance.get',
            'params' => [
                'card_token' =>[
                    $request->card_id,
                ]
            ],
        ]);

        $result = self::backOffice($input);

        Log::channel('payment_pnfl')->info(print_r($input, 1));
        Log::channel('payment_pnfl')->info(print_r($result, 1));

        if(isset($result['status']) && $result['status'] == true){

            if(!isset($result['result'])){ // если структура поменялась , вернуть 0 в балансе
                $result = [
                    'status' => true,
                    'balance' => 0
                ];
            }

            $result = [
                'status' => true,
                'balance' => $result['result'][0]['balance']/100  // в тиинах
            ];

        }else{
            $result = [
                'status' => false,
                'message' => $result['error']['message']??null,
            ];
        }

        return $result;


    }

    // создание контракта на клиента
    public static function createContractId($request)
    {

        $config = self::buildAuthorizeConfig();
        $ext = 'test-' . uniqid(10) . time();

        $input = json_encode([
            'jsonrpc' => '2.0',
            'method' => 'contract.create',
            'params' => [
                'pnfl' => $request->pnfl,
                'tin' => '111111111',  // inn
                'lastName' => $request->lastName,
                'firstName' => $request->firstName,
                'middleName' => $request->middleName,
                'birthDate' => $request->birthDate,
                'passportSeries' => $request->passportSeries,
                'passportNumber' => $request->passportNumber,
                'passportIssueDate' => $request->passportIssueDate,
                'passportExpDate' => $request->passportExpDate,
                'merchant_id' => $config['merchant'],
                'terminal_id' => $config['terminal'],
                'contract_id' => 'H11057',  // ??? любые левые символы
                'expiry' => date('m.d.Y'),  // ??? любая дата в формате 15.10.2025
                'amount' => 100,  // ???  зачем, науке не известно o_O
                'ext' => $ext,
            ],
        ]);

        $result = self::backOffice($input);

        Log::channel('payment_pnfl')->info(print_r($input, 1));
        Log::channel('payment_pnfl')->info(print_r($result, 1));

        if($result['status'] == true){
            $result = [
                'status' => true,
                'contract_id' => $result['result']['id']
            ];

        }else{
            try {

                //если контракт уже создан, вернем его
                $res = self::getContractId($request->clientId);

                if($res['status'] == true){
                    $result = [
                        'status' => true,
                        'contract_id' => isset($res['result'][0]['contract_id']) ? $res['result'][0]['contract_id'] : $res['result']['contract_id'] // проверять!! всегда меняется
                    ];

                }else{
                    $result = [
                        'status' => false,
                        'message' => $res['error']['message']
                    ];
                }

            } catch (\Exception $e) {
                $result = [
                    'status' => false,
                    'message' => 'try later'
                ];
                Log::info('universal Pnfl error');
                Log::info($e);
            }

        }

        return $result;

    }

    // получить ID существующих контрактов
    public static function getContractId($clientId)
    {

        $input = json_encode([
            'jsonrpc' => '2.0',
            'method' => 'client.contracts',
            'params' => [
                'client_id' => $clientId,
            ],
        ]);

        $result = self::backOffice($input);

        Log::channel('payment_pnfl')->info(print_r($input, 1));
        Log::channel('payment_pnfl')->info(print_r($result, 1));

        return $result;

    }


    // оплата по карте
    public static function payment($request)
    {

        $config = self::buildAuthorizeConfig();
        $ext = 'test-' .  time() . uniqid(rand(),10)/* . $request->card_id . $request->contract_id*/;

        $amount = (int)(round($request->amount) * 100); // tiin

        $input = json_encode([
            'jsonrpc' => '2.0',
            'method' => 'contract.payment',
            'params' => [
                'amount' => $amount,
                'ext' => $ext,
                'contract_id' => $request->contract_id,
                'card_id' => $request->card_id,
                'merchant' =>  $config['merchant'],
                'terminal' =>  $config['terminal'],
            ],
        ]);

        $result = self::backOffice($input);

        Log::channel('payment_pnfl')->info(print_r($input, 1));
        Log::channel('payment_pnfl')->info(print_r($result, 1));

        return $result;


    }


    // проверка транзакции
    public static function checkTransaction($request)
    {

        $input = json_encode([
            'jsonrpc' => '2.0',
            'method' => 'trans.ext',
            'params' => [
                'ext_id' => $request->transaction_id,

            ],
        ]);

        $result = self::backOffice($input);

        Log::channel('payment_pnfl')->info(print_r($input, 1));
        Log::channel('payment_pnfl')->info(print_r($result, 1));

        if($result['status'] == true){
            if($result['result']['status'] === 'OK'){
                $result = [
                    'status' => true,
                    'message' => 'Successful transaction'
                ];
            }
        }else{
            $result = [
                'status' => false,
                'message' => $result['error']['message']
            ];
        }

        return $result;

    }



    public static function backOffice($request)
    {

        $config = self::buildAuthorizeConfig();

        $curl = curl_init(self::$url);
        $token = env('PNFL_SERVICE_TOKEN', '5be27d0d-6c51-40da-ac51-0427653d742d');


        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token)
        );
        curl_setopt($curl, CURLOPT_POSTFIELDS, $request);

        try {
            $result = curl_exec($curl);
            $result = json_decode($result, JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            $result = [
                'status' => false,
                'message' => 'try later'
            ];
            Log::info('universal Pnfl error');
            Log::info($e);
        }


        return $result;
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

            'terminal' => config('test.universal_terminal_uzcard_autopay'),
            'merchant' => config('test.universal_merchant_uzcard_autopay'),

        ];

        return $config;
    }


}
