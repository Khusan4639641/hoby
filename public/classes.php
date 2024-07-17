<?php

class PerformTransactionArguments {
    public $amount;
    public $parameters;
    public $serviceId;
    public $transactionId;
    public $transactionTime;
}
class CheckTransactionArguments {
    public $serviceId;
    public $transactionId;
    public $transactionTime;
}
class CancelTransactionArguments {
    public $parameters;
    public $serviceId;
    public $transactionId;
    public $transactionTime;
}
class GetInformationArguments {
    public $parameters;
    public $serviceId;
    public $pay_amount;
}
class GetStatementArguments {
    public $serviceId;
    public $dateFrom;
    public $dateTo;
}

/*class GetStatetmentResult{
    public $statements;

} -*/

class Log{
    public static function info($data){
        $date =date('Y-m-d');
        $f = fopen('../storage/logs/paynet/paynet_'.$date.'.log','a');
        if( is_object($data) || is_array($data)){
            $res = json_encode($data,JSON_UNESCAPED_UNICODE);
        }else{
            $res = $data;
        }
        fwrite($f,date('Y-m-d H:i:s') .': ' . $res . PHP_EOL);
        fclose($f);
    }
}

class PaynetService {

    private $username = 'test_paynet';
    private $password = 'asj37Ff2-38g3';
    private $token = '76d66c5a5356104a8fc6784e007d9c33';

    private function login($data){
        if($this->username==$data->username && $data->password==$this->password){
            return true;
        }
        return false;

    }

    private function result($data){

        $data['timeStamp']= date('Y-m-d\TH:i:s+05:00');

        Log::info($data);

        return $data;
    }

    private function getPhone($params){

        //$phone=false;
        //$client_id = false;
        Log::info($params);

        if(isset($params->paramKey) && $params->paramKey=='client_id' ) return $params->paramValue;

        // $phone=false;
        // $client_id = false;


        foreach ($params as $param){
            if($param->paramKey=='client_id'){
                $_params[$param->paramKey] = $param->paramValue;
                break;
            }
            /*
            if($client_id){
                $phone = $param;
                break;
            }
            if($param=='client_id'){
                $client_id = true;
                continue;
            } -*/

        }
        Log::info('phone');
        Log::info($_params);
        return $_params['client_id'] ?? '';
    }

    private function parse($params){

        $_params = [];
        $client_id = false;
        $old_key = '';


        foreach ($params as $key => $param) {
            $_params[$param->paramKey] = $param->paramValue;
            //Log::info($param);

            /* foreach ($params as $key=>$param){
                //Log::info($param);
                if($client_id){
                    $_params[$old_key] = $param;
                    break;
                }
                if($param=='client_id'){
                    $_params[$key] = '';
                    $old_key = $key;
                    $client_id = true;
                    continue;
                }
            } */

        }

	if(isset($params->paramKey) && $params->paramKey=='client_id' ) {
            $_params['client_id'] = $params->paramValue;
        }
        Log::info($_params);
        return $_params;

    }

    public function PerformTransaction(PerformTransactionArguments $request){

        Log::info('PerformTransaction');
        Log::info($request);

        $providerTrnId = 0;

        if( !$this->login($request) ){
           return $this->result(['errorMsg'=>'Error. Username or login incorrect','status'=>412,'providerTrnId'=>$providerTrnId]);
        }

        if($request->amount<100000){
            return $this->result(['errorMsg'=>'Error. Minimum amount value must be 1000 sum','status'=>413,'providerTrnId'=>$providerTrnId]);
        }
        if($request->transactionId ==''){
            return $this->result(['errorMsg'=>'Error. transactionId not set','status'=>411,'providerTrnId'=>$providerTrnId]);
        }
        if($request->serviceId ==''){
            return $this->result(['errorMsg'=>'Error. serviceId not set','status'=>411,'providerTrnId'=>$providerTrnId]);
        }

        $phone = $this->getPhone($request->parameters);

        Log::info('phone: '.$phone);
        if($phone == ''){
            return $this->result(['errorMsg'=>'Error. client_id not set','status'=>411,'providerTrnId'=>$providerTrnId]);
        }

        $options = [
            'url' => 'paynet/perform',
            'data'=>[
                'amount' =>$request->amount,
                'serviceId' =>$request->serviceId,
                'transactionId' =>$request->transactionId,
                'transactionTime' => $request->transactionTime ?? date('Y-m-d\TH:i:s'),
                'phone' => $phone
            ]
        ];
        $result = $this->send($options);

        Log::info('result');
        Log::info($result);

        if(isset($result['status']) && $result['status']=='success'){

            return $this->result([
                'errorMsg'=>'Success',
                'status' => $result['code'] ?? 0,
                'providerTrnId' =>$result['providerTrnId'] ?? 0,
                'parameters' => ['paramKey'=>'balance','paramValue'=>$result['balance']],
            ]);
        }

        return $this->result([
            'errorMsg'=>  $result['error'] ?? 'Error',
            'status' => $result['code'] ?? 102,
            'providerTrnId' => 0
        ]);

    }

    public function CheckTransaction(CheckTransactionArguments $request){

        Log::info('CheckTransaction');
        Log::info($request);

        $providerTrnId = 0;

        if( !$this->login($request) ){
            return $this->result(['errorMsg'=>'Error. Username or login incorrect','status'=>412,'providerTrnId'=>$providerTrnId]);
        }

        if($request->transactionId ==''){
            return $this->result(['errorMsg'=>'Error. transactionId not set','status'=>411,'providerTrnId'=>$providerTrnId]);
        }
        if($request->serviceId ==''){
            return $this->result(['errorMsg'=>'Error. serviceId not set','status'=>411,'providerTrnId'=>$providerTrnId]);
        }

        $options = [
            'url' => 'paynet/check',
            'data'=>[
                'serviceId' =>$request->serviceId,
                'transactionId' =>$request->transactionId,
                'transactionTime' =>$request->transactionTime ?? date('Y-m-d\TH:i:s'),
                ]
        ];

        $result = $this->send($options);
        Log::info($result);

        if(isset($result['status']) && $result['status']=='success'){

            return $this->result([
                'errorMsg'=>'Success',
                'status' => $result['code'] ?? 0,
                'providerTrnId' => $result['providerTrnId'] ?? 0,
                'transactionState'=>$result['transactionState'],
                'transactionStateErrorStatus'=>0,
                'transactionStateErrorMsg'=>'Success'
            ]);
        }


        return $this->result([
            'errorMsg'=>  $result['error'] ?? 'Error',
            'status' => $result['code'] ?? 102,
            'providerTrnId' => 0,
            'transactionState'=>$result['transactionState'] ?? 0,
            'transactionStateErrorStatus'=>0,
            'transactionStateErrorMsg'=>'Error'
        ]);


    }

    public function CancelTransaction(CancelTransactionArguments $request){

        Log::info('CancelTransaction');
        Log::info($request);

        $providerTrnId = 0;

        if( !$this->login($request) ){
            return $this->result(['errorMsg'=>'Error. Username or login incorrect','status'=>412,'providerTrnId'=>$providerTrnId]);
        }

        if($request->transactionId ==''){
            return $this->result(['errorMsg'=>'Error. transactionId not set','status'=>411,'providerTrnId'=>$providerTrnId]);
        }
        if($request->serviceId ==''){
            return $this->result(['errorMsg'=>'Error. serviceId not set','status'=>411,'providerTrnId'=>$providerTrnId]);
        }

        $options = [
            'url' => 'paynet/cancel',
            'data'=>[
                'serviceId' =>$request->serviceId,
                'transactionId' =>$request->transactionId,
                'transactionTime' =>$request->transactionTime ?? date('Y-m-d\TH:i:s'),
            ]
        ];

        $result = $this->send($options);

        if(isset($result['status']) && $result['status']=='success'){
            return $this->result([
                'errorMsg'=>'Success',
                'status' => $result['code'] ?? 0,
                'providerTrnId' =>$result['providerTrnId'] ?? 0,
                'transactionState'=>$result['transactionState']
            ]);
        }

        return $this->result([
            'errorMsg'=>  $result['error'] ?? 'Error',
            'status' => $result['code'] ?? 103,
            'providerTrnId' => 0,
            'transactionState' => 2
        ]);

    }

    public function GetInformation(GetInformationArguments $request){

        Log::info('GetInformation');
        Log::info($request);

        if( !$this->login($request) ){
            return $this->result(['errorMsg'=>'Error. Username or login incorrect','status'=>412]);
        }

        $params = $this->parse($request->parameters);

        Log::info($params);

        if( !isset($params['client_id'])){
            return $this->result(['errorMsg'=>'Error. client_id not set','status'=>411]);
        }
        if($request->serviceId ==''){
            return $this->result(['errorMsg'=>'Error. serviceId not set','status'=>411]);
        }

        $options = [
            'url' => 'paynet/information',
            'data'=>[
                'serviceId' =>$request->serviceId,
                'transactionId' =>$request->transactionId,
                'transactionTime' =>$request->transactionTime ?? date('Y-m-d\TH:i:s'),
                'phone' => $params['client_id']
            ]

        ];

        $result = $this->send($options);

        if(isset($result['status']) && $result['status']=='success'){
            return $this->result([
                'errorMsg'=>'Success',
                'status' => $result['code'] ?? 0,
            ]);
        }

        return $this->result([
            'errorMsg'=>  $result['error'] ?? 'Error',
            'status' => $result['code'] ?? 103,
        ]);

    }

    public function GetStatement(GetStatementArguments $request){

        Log::info('GetStatement');
        Log::info($request);

        if( !$this->login($request) ){
            return $this->result(['errorMsg'=>'Error. Username or login incorrect','status'=>412]);
        }

        if( !isset($request->dateFrom) ){
            return $this->result(['errorMsg'=>'Error. transaction_time not set','status'=>411]);
        }

        if( !isset($request->dateTo) ){
            return $this->result(['errorMsg'=>'Error. transaction_time_to not set','status'=>411]);
        }

        $options = [
            'url' => 'paynet/statement',
            'data'=>[
                'serviceId' => $request->serviceId,
                'dateFrom' => $request->dateFrom,
                'dateTo' => $request->dateTo,
                // 'transactionId' =>$request->transactionId,
            ]
        ];

        $result = $this->send($options);
        Log::info($result);

        if(isset($result['status']) && $result['status']=='success'){

            $_result = [
                'errorMsg' => 'Success',
                'status' => $result['code'] ?? 0,
                'timeStamp'=> date('Y-m-d\TH:i:s+05:00'),
                'statements' => $result['data'],

            ];


            return $this->result($_result);


            $_result = array_merge($_result,$result['data']);

            return $this->result($_result);

            header('Content-Type: text/xml');

            echo '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://uws.provider.com"><SOAP-ENV:Body><ns1:GetStatementResult><errorMsg>Success</errorMsg><status>0</status><timeStamp>2021-11-23T08:23:51+05:00</timeStamp><statements><amount>111110</amount><providerTrnId>405</providerTrnId><transactionId>111</transactionId><transactionTime>0025-05-13T10:00:00.000+05:00</transactionTime></statements><statements><amount>111110</amount><providerTrnId>410</providerTrnId><transactionId>123</transactionId><transactionTime>2021-11-19T12:12:11.000+05:00</transactionTime></statements><statements><amount>111110</amount><providerTrnId>411</providerTrnId><transactionId>1233</transactionId><transactionTime>2021-11-19T10:00:00.000+05:00</transactionTime></statements><statements><amount>111110</amount><providerTrnId>412</providerTrnId><transactionId>200</transactionId><transactionTime>0025-05-13T10:00:00.000+05:00</transactionTime></statements><statements><amount>111110</amount><providerTrnId>415</providerTrnId><transactionId>101</transactionId><transactionTime>0025-05-13T10:00:00.000+05:00</transactionTime></statements><statements><amount>111110</amount><providerTrnId>419</providerTrnId><transactionId>202</transactionId><transactionTime>2021-11-19T10:00:00.000+05:00</transactionTime></statements><statements><amount>125000</amount><providerTrnId>421</providerTrnId><transactionId>203</transactionId><transactionTime>2021-11-19T10:00:00.000+05:00</transactionTime></statements><statements><amount>111110</amount><providerTrnId>423</providerTrnId><transactionId>205</transactionId><transactionTime>2021-11-19T10:00:00.000+05:00</transactionTime></statements><statements><amount>111110</amount><providerTrnId>424</providerTrnId><transactionId>206</transactionId><transactionTime>2021-11-19T10:00:00.000+05:00</transactionTime></statements><statements><amount>111110</amount><providerTrnId>425</providerTrnId><transactionId>207</transactionId><transactionTime>2021-11-19T10:00:00.000+05:00</transactionTime></statements><statements><amount>111110</amount><providerTrnId>426</providerTrnId><transactionId>208</transactionId><transactionTime>2021-11-19T10:00:00.000+05:00</transactionTime></statements></ns1:GetStatementResult></SOAP-ENV:Body></SOAP-ENV:Envelope>';

            exit;


           // return $this->result($_result);

        }

        return $this->result([
            'errorMsg'=>  $result['error'] ?? 'Error',
            'status' => $result['code'] ?? 103,
        ]);

    }


    private function send($options){

        $headers = [];
        $headers[] = 'Authorization: Bearer ' . $this->token;
        $headers[] = 'Content-Type: application/json';

        $server = $_SERVER['SERVER_NAME'];

        $curl = curl_init( 'https://'.$server.'/api/v1/' . $options['url']);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BEARER);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($options['data'],JSON_UNESCAPED_UNICODE));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_TIMEOUT , 30);
        curl_setopt($curl, CURLOPT_ENCODING , '');

        Log::info('curl-options');
        Log::info('https://'.$server.'/api/v1/' . $options['url']);
        Log::info($options);

        try {
            $result = curl_exec($curl);
            Log::info($result);

            $result = json_decode($result, JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            $result = [
                'status' => 'error',
                'error' => json_encode($e,JSON_UNESCAPED_UNICODE),
                'msg' => curl_error(),
            ];
        }

        Log::info($result);
        return $result;
    }
}
