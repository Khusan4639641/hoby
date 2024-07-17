<?php

namespace App\Helpers;

use App\Facades\OldCrypt;
use App\Models\Card;
use App\Models\CardType;
use App\Services\API\V3\BaseService;
use Faker\Provider\Base;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use App\Extensions\ExSoapClient;

class CardHelper
{

    /**
     * Разбор карты для получения дополнительной информации
     * @param String $card
     * @param String $expiry
     * @return array
     */
    public static function getAdditionalCardInfo(String $card, $expiry = '' ){

        Log::info('getAdditionalCardInfo: '.$card . ' ' . $expiry);

        $card = str_replace(' ','', $card);

        if(strpos($expiry,'/')>0){
            list($exp_m, $exp_y) = explode('/', $expiry);
        }else{
            $exp_m = mb_substr($expiry, 0, 2);
            $exp_y = mb_substr($expiry, 2, 2);
        }

        return [
            'card'  => $card,
            'bank_c' => mb_substr($card, 4, 2),
            'card_h' => mb_substr($card, 6, 10),
            'exp_m' => $exp_m,
            'exp_y' => $exp_y,
            'exp' => $exp_y . $exp_m
        ];
    }

    /**
     * Определение типа карты HUMO/UZCARD
     * @param String $card
     * @return array
     */
    public static function checkTypeCard(String $card)
    {
        $typeInfo = ['type' => 0, 'name' => '']; // по умолчанию
        $card = str_replace(' ', '', $card);

        if(strlen($card) === Card::CARD_LENGTH) {
            $cardPrefix = substr($card, 0, 4);
            $cardType = json_decode(Redis::get('card_prefix:' . $cardPrefix));

            if(is_null($cardType)) {
                $cardType = CardType::where('prefix', $cardPrefix)->first();
                if(isset($cardType)) {
                    Redis::set('card_prefix:'. $cardType->prefix, json_encode([
                            'name' => strtoupper($cardType->name),
                            'type_id' => $cardType->type_id,
                        ])
                    );
                }
            };

            if(isset($cardType)) {
                $typeInfo['name'] = strtoupper($cardType->name);
                $typeInfo['type'] = $cardType->type_id;
            }
        }
        return $typeInfo;
    }

    /**
     * Вспомогательная функция отправки JSON запросов
     * @param array $config
     * @param $req
     * @return String
     */
    public static function requestUZCard( array $config, $req ): array{
        Log::channel('cards')->info(print_r($config,1));
        Log::channel('cards')->info(print_r($req,1));
        Log::channel('cards')->info(print_r(OldCrypt::decryptString($config['login']) . ":" . OldCrypt::decryptString($config['password']),1));
        //Log:info($config);

        $curl = curl_init($config['url']);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, OldCrypt::decryptString($config['login']) . ":" . OldCrypt::decryptString($config['password']));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);

        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $req);

        $_result = curl_exec($curl);
        $result = json_decode($_result, JSON_UNESCAPED_UNICODE);

        if(!is_array($result)){
            $result = [];
        }

        return $result;
    }

    /**
     * Вспомогательная функция отправки JSON запросов
     * @param array $config
     * @param $req
     * @return String
     */
    public static function requestUniversalCard(array $config, $req): array{
        Log::channel('cards')->info(print_r($config,1));
        Log::channel('cards')->info(print_r($req,1));

        $curl = curl_init($config['url']);

        $headers = [];

        if(empty($config['token'])) {
            Log::channel('cards')->info(print_r(OldCrypt::decryptString($config['login']) . ":" . OldCrypt::decryptString($config['password']), 1));
            // авторизация по токену
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BEARER);
            $headers[] = 'Authorization: Bearer ' . $config['token'];

        }else{
            // авторизация по логину и паролю
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($curl, CURLOPT_USERPWD, OldCrypt::decryptString($config['login']) . ':' . OldCrypt::decryptString($config['password']));

        }

        $headers[] = 'Content-Type: application/json';

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $req);

        $_result = curl_exec($curl);
        $result = json_decode($_result, JSON_UNESCAPED_UNICODE);

        return $result;
    }

    /**
     * Запрос на получение CLIENT ID по номеру карты в HUMO
     * @param $client
     * @param $cardInfo
     * @return array
     */
    public static function requestHumoCard($client, $cardInfo){
        $exSession = 'test-' . time();

        $customer = $client->listCustomers([  // работает
            'BANK_C' => $cardInfo['bank_c'],
            'GROUPC' => '01',
            'EXTERNAL_SESSION_ID' => $exSession,
        ], [
            'CARD' => $cardInfo['card'],
            'BANK_C' => $cardInfo['bank_c'],
            'LOCKING_FLAG' => 1
        ]);

        Log::channel('cards')->info(print_r($cardInfo,1));

        if(is_soap_fault($customer)) {
            $result['status'] = 'error';
            $result['respone']['data'] = $customer->getTraceAsString();
            Log::channel('cards')->error(print_r($customer->getTraceAsString(),1));
            return $result;
        }

        $client_id = $customer['Details']->row->item[0]->value;

        $card = $client_id . '-' . $cardInfo['bank_c'];

        return ['status'=> 'success', 'data'=>$card];
    }

    /**
     * Запрос номера телефона привязанному к карте по CLIENT ID
     * @param $client
     * @param $card
     * @return array
     */
    public static function requestHumoMobile($client, $card){
        $xml = '<urn:export>
                     <cardholderID>'.$card.'</cardholderID>
                     <bankid>MB_STD</bankid>
                </urn:export>';
        $soapBody = new \SoapVar($xml, \XSD_ANYXML, NULL, NULL, NULL, 'urn:AccessGateway');
        $objectInfo = $client->MethodNameIsIgnored($soapBody);
        Log::channel('cards')->info(print_r($objectInfo,1));
        if(is_soap_fault($objectInfo)) {
            $result['status'] = 'error';
            $result['respone']['data'] = $objectInfo->getTraceAsString();
            Log::channel('cards')->info(print_r($objectInfo->getTraceAsString(),1));
            return $result;
        }

        $arPhone = (array)$objectInfo['Phone'];
        $info['name'] = $objectInfo['cardholderName'];
        $info['phone'] = str_replace('+','', $arPhone['msisdn']);
        Log::channel('cards')->info(print_r($info,1));

        return ['status'=> 'success', 'data'=>$info];
    }

    public static function requestHumoScoring($client, $infoCard, $aScroingInfo){
        $exSession = 'test-' . time(); $i = 0; $arr = []; $scoring = ['data'=>[], 'response'=>[]];

        $beginDate = $aScroingInfo['date_start']; $endDate = $aScroingInfo['date_end']; $d = '';
        while (strtotime($beginDate) <= strtotime($endDate)) {
            $d = new \DateTime($beginDate);
            $scoring['data'][$d->format('Y.m')] = 0;
            $d->modify( 'last day of next month' );
            $beginDate = $d->format( 'Y-m-d' );
        }
        $scoring['data'][$d->format('Y.m')] = 0;

        $tranType = ['110','111','113','114','115','206','208','225','227','229','314','315','316','614',
            '11b','11c','11C','11E','11G','11L','11V',
            '31a','31A','31b','31B','31D','31E','31G','31g','31K','31R','31W',
            '51a','51c','51G'];

        $tranHistory = $client->queryTransactionHistory([
            'BANK_C' => $infoCard['bank_c'],
            'GROUPC' => '01',
            'EXTERNAL_SESSION_ID' => $exSession,
        ], [
            'CARD' => $infoCard['card'],
            'BEGIN_DATE' => $aScroingInfo['date_start'],
            'END_DATE' => $aScroingInfo['date_end'],
            'BANK_C' => $infoCard['bank_c'],
            'GROUPC' => '01',
            'LOCKING_FLAG' => 1
        ]);
        $scoring['response']['request'] = $client->__getLastRequest();
        if(is_soap_fault($tranHistory)) {
            $scoring['response']['response'] = $tranHistory->getTraceAsString();
            return $scoring;
        }
        $scoring['response']['response'] = $client->__getLastResponse();

        if(isset($tranHistory['Details']->row)) {
            foreach ($tranHistory['Details']->row as $row) {
                foreach ($row->item as $item => $value) {
                    if ($value->name == 'TRAN_TYPE') {
                        $arr[$i][$value->name] = $value->value;
                    }
                    if ($value->name == 'TRAN_AMT') {
                        $arr[$i][$value->name] = $value->value;
                    }
                    if ($value->name == 'TRAN_DATE_TIME') {
                        $arr[$i][$value->name] = $value->value;
                    }
                }
                $i++;
            }

            foreach ($arr as $item => $value) {
                if (in_array($value['TRAN_TYPE'], $tranType)) { // если это пополнение
                    list($dt, $tm) = explode('T', $value['TRAN_DATE_TIME']);
                    list($year, $month) = explode('-', $dt);
                    $index = $year . '.' . $month;
                    if (!isset($scoring[$index])) $scoring['data'][$index] = 0;
                    $scoring['data'][$index] = (int)$scoring['data'][$index] + (int)$value['TRAN_AMT'];
                }
            }
            foreach ($scoring['data'] as $date => $sum)
                $scoring['data'][$date] = $sum > $aScroingInfo['sum'] ? true : false;
        }
        return $scoring;
    }

    /**
     * @param $config
     * @param $client
     * @param $infoCard
     * @param $transactionId
     * @return mixed
     */
    public static function requestHumoRefund($config, $client, $infoCard, $transactionId){
        $merchantId = $config['merchant_id'];
        $terminalId = $config['terminal_id'];

        $bank = [
            '01'=>'Ipoteka', '21'=>'Turkiston', '26'=>'Infin', '02'=>'UzPSB', '03'=>'Agrobank', '04'=>'Asaka',
            '08'=>'Xalqbank', '12'=>'NBU', '13'=>'Mkredit', '14'=>'Savdogar', '15'=>'Turon', '16'=>'Hamkor',
            '17'=>'IpakYuli', '18'=>'Trastbank', '19'=>'Aloqa', '20'=>'KDB', '23'=>'Universal', '24'=>'Ravnaq',
            '25'=>'Davr', '27'=>'OFB', '28'=>'HiTech', '29'=>'UTBank', '30'=>'Saderat', '09'=>'AsiaAllianc',
            '06'=>'KishloqKB', '32'=>'MadadInvest', '10'=>'Kapital', '31'=>'AgroExpBank'
        ];

        foreach($bank as $k => $v){
            if($k == $infoCard['bank_c']){
                $centreId = $v;
            }
        }

        $xml = '<urn:ReturnPayment>
                    <paymentID>'.$transactionId.'</paymentID>
                    <item>
                    <name>merchant_id</name>
                    <value>'.$merchantId.'</value>
                    </item>
                    <item><name>centre_id</name>
                    <value>'.$centreId.'</value>
                    </item>
                    <item>
                    <name>terminal_id</name>
                    <value>'.$terminalId.'</value>
                    </item>
                    <paymentOriginator>user</paymentOriginator>
                </urn:ReturnPayment>';
        $soapBody = new \SoapVar($xml, \XSD_ANYXML, NULL, NULL, NULL, 'urn:PaymentServer');
        $objectInfo = $client->MethodNameIsIgnored($soapBody);

        $encRequest = json_encode($soapBody);

        if(is_soap_fault($objectInfo)) {
            $result['status'] = 'error';
            $result['response']['data'] = $objectInfo->getTraceAsString();
            Log::channel('cards')->info(print_r($objectInfo->getTraceAsString(),1));
        }else{
            $result['status'] = 'success';
            $result['response']['data'] = $objectInfo;
            $result['response']['request'] = $encRequest;
            Log::channel('cards')->info(print_r($objectInfo,1));
        }

        return $result;
    }

    /**
     * @param $config
     * @param $client
     * @param $infoCard
     * @param $sum
     * @return mixed
     */
    public static function requestHumoPayment($config, $client, $infoCard, $sum){
        $merchantId = $config['merchant_id'];
        $terminalId = $config['terminal_id'];

        $bank = [
            '01'=>'Ipoteka', '21'=>'Turkiston', '26'=>'Infin', '02'=>'UzPSB', '03'=>'Agrobank', '04'=>'Asaka',
            '08'=>'Xalqbank', '12'=>'NBU', '13'=>'Mkredit', '14'=>'Savdogar', '15'=>'Turon', '16'=>'Hamkor',
            '17'=>'IpakYuli', '18'=>'Trastbank', '19'=>'Aloqa', '20'=>'KDB', '23'=>'Universal', '24'=>'Ravnaq',
            '25'=>'Davr', '27'=>'OFB', '28'=>'HiTech', '29'=>'UTBank', '30'=>'Saderat', '09'=>'AsiaAllianc',
            '06'=>'KishloqKB', '32'=>'MadadInvest', '10'=>'Kapital', '31'=>'AgroExpBank'
        ];

        foreach($bank as $k => $v){
            if($k == $infoCard['bank_c']){
                $centreId = $v;
            }
        }

        $items = ['pan' => $infoCard['card'], 'expiry' => $infoCard['exp'], 'ccy_code' => 860,
            'amount' => $sum, 'merchant_id' => $merchantId, 'terminal_id' => $terminalId,
            'point_code' => '100010104110', 'centre_id' => $centreId];

        $xml = '<ebppif1:Payment>
                 <billerRef>SOAP_SMS</billerRef>
                 <payinstrRef>SOAP_SMS</payinstrRef>
                   <details>';
        foreach ($items as $name=>$value)
            $xml .= '<item>
                        <name>'.$name.'</name>
                        <value>'.$value.'</value>
                     </item>';
        $xml    .= '</details>
                <paymentOriginator>user</paymentOriginator>
                </ebppif1:Payment>';
        $soapBody = new \SoapVar($xml, \XSD_ANYXML, NULL, NULL, NULL, 'urn:PaymentServer');

        $encRequest = json_encode($soapBody);

        $objectInfo = $client->MethodNameIsIgnored($soapBody);
        //Log::channel('cards')->info(print_r($objectInfo,1));
        if(is_soap_fault($objectInfo)) {
            $result['status'] = 'error';
            $result['respone']['data'] = $objectInfo->getTraceAsString();
            Log::channel('cards')->info(print_r($objectInfo->getTraceAsString(),1));
            return $result;
        }
        Log::channel('cards')->info(print_r($objectInfo,1));

        $paymentId = $objectInfo['paymentID'];
        $xml = '<ebppif1:Payment>
	               <paymentID>'.$paymentId.'</paymentID>
	               <confirmed>true</confirmed>
	               <finished>true</finished>
	               <paymentOriginator>user</paymentOriginator>
                </ebppif1:Payment>';
        $soapBody = new \SoapVar($xml, \XSD_ANYXML, NULL, NULL, NULL, 'urn:PaymentServer');
        $objectInfo = $client->MethodNameIsIgnored($soapBody);

        if(is_soap_fault($objectInfo)) {
            $result['status'] = 'error';
            $result['response']['data'] = $objectInfo->getTraceAsString();
            Log::channel('cards')->info(print_r($objectInfo->getTraceAsString(),1));
        }else{
            $result['status'] = 'success';
            $result['response']['data'] = $objectInfo;
            $result['response']['request'] = $encRequest;
            Log::channel('cards')->info(print_r($objectInfo,1));
        }

        return $result;
    }

    /**
     * @param $client
     * @param $infoCard
     * @return mixed
     */
    public static function requestHumoBalance($client, $infoCard){
        $xml = '<urn:getCardAccountsBalance>
	                <primaryAccountNumber>'.$infoCard['card'].'</primaryAccountNumber>
	            </urn:getCardAccountsBalance>';
        $soapBody = new \SoapVar($xml, \XSD_ANYXML, NULL, NULL, NULL, 'urn:IIACardServices');
        $objectInfo = $client->MethodNameIsIgnored($soapBody);
        Log::channel('cards')->info(print_r($objectInfo,1));
        if(is_soap_fault($objectInfo)) {
            $result['status'] = 'error';
            $result['respone']['data'] = $objectInfo->getTraceAsString();
            Log::channel('cards')->info(print_r($objectInfo->getTraceAsString(),1));
            return $result;
        }
        return $objectInfo['balance'];
    }

    /**
     * Создание подключения по soap протоколу
     * @param array $config
     * @param string $type
     * @param bool $bWsdl - с учетом WSDL или без него
     * @return ExSoapClient
     */
    public static function connectedHumoCard(array $config, $type='scoring', $bWsdl = true){
        $options = [
            'trace' => 1,
            'location' => $config['url_'.$type],
            'login' => OldCrypt::decryptString($config['login']),
            'password' => OldCrypt::decryptString($config['password']),
            'exceptions' => 0,
            'cache_wsdl' => WSDL_CACHE_MEMORY,
            'stream_context' => stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ])
        ];
        if($bWsdl) {
            $wsdl = 'file://' . Storage::disk('wsdl')->getDriver()->getAdapter()->applyPathPrefix('Issuing_PP.wsdl');
        }else {
            $wsdl = NULL;
            $options = array_merge($options, ['soap_version' => SOAP_1_2, 'use' => SOAP_LITERAL, 'style' => SOAP_DOCUMENT, 'uri' => $config['url_'.$type]]);
        }


        ini_set('default_socket_timeout', 300);
        ini_set('soap.wsdl_cache_enabled',0);
        ini_set('soap.wsdl_cache_ttl',0);
        ini_set('memory_limit','1024M');
        Log::channel('cards')->info(print_r($config,1));
        Log::channel('cards')->info(print_r(OldCrypt::decryptString($config['login']) . ":" . OldCrypt::decryptString($config['password']),1));
        Log::channel('cards')->info($wsdl);
        Log::channel('cards')->info(print_r($options,1));
        $soapClient = new ExSoapClient($wsdl, $options);

        return $soapClient;

    }

    // логи карты по типу
    public static function getImage($type){

        switch ($type){
            case 'HUMO':
                $image = 'humo.png';
                break;
            case 'UZCARD':
                $image = 'uzcard.png';
                break;

            default:
                $image = 'card_empty.png';

        }

        return $image;


    }

    // маскированный номер карты
    public static function getCardNumberMask($card){
        return '**** ' . substr($card, -4, 4);
    }

    // маскированный номер карты c первыми цифрами
    public static function getCardMask($card){
        return substr($card, 0, 4) . '****' . substr($card, -4);
    }

    // изменить статус карты
    public static function changeStatus(&$card,$status){
        $card->status = $status;
        if($card->save()) {
            return true;
        }
        return false;
    }

    public static function changeStatusByCardId($card_id,$status){
        if($card = Card::find($card_id)) {
            $card->status = $status;
            if($card->save()) return true;
        }
        return false;
    }

}
