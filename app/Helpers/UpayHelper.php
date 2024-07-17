<?php
namespace App\Helpers;

use SoapClient;

class UpayHelper {

    const FINE_TRAFFIC_POLICE = 238; // Штрафы ГУБДД

    /**
     * @param $config
     * @return SoapClient
     */
    public static function connectedUpay($config) {

        $wsdl = 'http://api.upay.uz/STAPI/STWS?wsdl';

        return new SoapClient($wsdl, [
            'trace' => 1,
            'UserName' => $config['login'],
            'Password' => $config['password'],
            'StPimsApiPartnerKey' => $config['key'],
            'exceptions' => 0,
            'cache_wsdl' => WSDL_CACHE_MEMORY,
            'stream_context' => stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ])
        ]);
    }

    /**
     * updated_at можете всегда ставить значения 0
     * category_id если все категории хотите получить, то значение 0
     * 1 = Мобильные операторы
     * 2 = Интернет провайдеры
     * 10 = Гос услуги
     */
    /**
     * @param $client
     * @param $config
     * @param int $categoryId
     * @return mixed
     */
    public static function getServiceList($client, $config, $categoryId = 1){

        $result = $client->getServiceList([
            'getServiceListRequest' => [
                'StPimsApiPartnerKey' => $config['key'],
                'CategoryId' => $categoryId,
                'Update_at' => '0',
                'Version' => '',
                'Lang' => 'ru',
            ]
        ]);

        return $result;

    }
// получить сумму штрафа

    /**
     * @param $client
     * @param $config
     * @param $account
     * @return mixed
     */
    public static function getSum($client, $config, $account){

        $result = $client->findPersonalAccount([
            'PersonalAccountRequest' => [
                'StPimsApiPartnerKey' => $config['key'],
                'PersonalAccount' => $account,
                'UserCredentials' => [
                    'Login' => $config['credentials_login'],
                    'Password' => $config['credentials_login'],
                ],
                'ServiceId' => self::FINE_TRAFFIC_POLICE,
                'RegionCode' => '',
                'SubRegionCode' => '',
                'Version' => '',
                'Lang' => 'ru',
            ]
        ]);

        return $result;

    }

    /**
     * @param $client
     * @param $config
     * @param $serviceId
     * @param $account
     * @param $amountWithTiyin
     * @return array
     */
    public static function BankPayment($client, $config, $serviceId, $account, $amountWithTiyin){
        $partnerTransId = time();
        $token = md5($config['login'] .  $serviceId . $account . $amountWithTiyin . $partnerTransId . $config['password']);

        $result = $client->bankPayment([
            'bankPaymentRequest' => [
                'StPimsApiPartnerKey' => $config['key'],
                'AccessToken' => $token,
                'ServiceId' => $serviceId,
                'RegionCode' => '',
                'SubRegionCode' => '',
                'Account' => $account,
                'Amount' => '',
                'BankTransId' => '',
                'Type' => '',
                'From' => '',
                'To' => '',
                'AmountWithTiyin' => $amountWithTiyin,
                'PartnerTransId' => $partnerTransId,
                'Lang' => 'ru',
            ]

        ]);

        if(isset($result->return->UpayTransId)){
            $transId = $result->return->UpayTransId;
            $res = ['status' => 'success', 'transaction_id' => $transId, 'response' => ['response'=>$client->__getLastResponse(), 'request' => $client->__getLastRequest()]];
        }else{
            $error = $result->return->Result->code;
            $description = $result->return->Result->Description;
            $res = ['status' => 'error', 'code' => $error, 'message' => $description, 'response' => ['response'=>$client->__getLastResponse(), 'request' => $client->__getLastRequest()]];
        }
        return $res;
    }

    public static function BankCheckAccount($client, $config, $account, $serviceId){
        //$token = md5(UserName + serviceId + regionCode + subRegionCode + account + Password);
        $token = md5($serviceId + 1 + 1 + $account);

        $result = $client->BankCheckAccount([  // работает
            'bankCheckAccountRequest' =>[
                'StPimsApiPartnerKey' => $config['key'],
                'AccessToken' => $token,
                'RegionCode' => '1',
                'SubRegionCode' => '1',
                'Account' => $account,
                'ServiceId' => $serviceId,
                'Lang' => 'ru',
            ]
        ]);

        if(isset($result->return->Result->code)){
            $error = $result->return->Result->code;
            $description = $result->return->Result->Description;
            $res = ['status' => 'error', 'code' => $error, 'message' => $description];
        }else{
            $res = ['status' => 'success', 'result' => $result->return->Result];
        }
        return $res;
    }


    public static function getRegionsList($client, $config, $serviceId){

        $result = $client->getRegionsList([
            'getRegionsListRequest' =>[
                'StPimsApiPartnerKey' => $config['key'],
                'ServiceId' => $serviceId,
                'Update_at' => 0,
                'Lang' => 'ru',
            ]
        ]);

        return $result;
    }
    public function updateRegions(){


    }
    public function getRegions($id = null){

    }

    /**
     * получить баланс компании на счету Upay
     */
    public static function getBalance($client, $config){

        $result = $client->bankCheckBalance([
            'bankCheckBalanceRequest' => [
                'StPimsApiPartnerKey' => $config['key'],
                'Username' => $config['login'],
                'Password' => $config['password'],
                'Lang' => 'ru',
            ]

        ]);

        if(isset($result->return->Result->code) && $result->return->Result->code == 'OK'){
            $balance = $result->return->Balance;  // сразу сумма
            $res = ['status' => 'success', 'balance' => $balance];

        }else{
            $res = ['status' => 'error', 'balance' => "try later"];  // ??
        }

        return $res;
    }
}
