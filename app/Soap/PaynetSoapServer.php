<?php

namespace App\Soap;
use Illuminate\Support\Facades\Log;

/**
 * Class PaynetSoapServer
 * @package App\Extensions
 */
class PaynetSoapServer {

    private $username = 'test_paynet';
    private $password = 'asj37Ff2%38g3';
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
        $phone=false;
        $client_id = false;
        foreach ($params as $key=>$param){
            Log::info($param);
            if($client_id){
                $phone = $param;
                break;
            }
            if($param=='client_id'){
                $client_id = true;
                continue;
            }
        }

        return $phone;
    }

    public function PerformTransaction(PerformTransactionArguments $PerformTransactionArguments){

        Log::info('soap perform args');
        Log::info($PerformTransactionArguments);

        $providerTrnId = 2021 . rand(100,1000);

        if( !$this->login($PerformTransactionArguments) ){
            return $this->result(['errorMsg'=>'Error. Username or login incorrect','status'=>100,'providerTrnId'=>$providerTrnId]);
        }

        if($PerformTransactionArguments->amount<100000){
            return $this->result(['errorMsg'=>'Error. Minimum amount value must be 1000 sum','status'=>101,'providerTrnId'=>$providerTrnId]);
        }
        if($PerformTransactionArguments->transactionId ==''){
            return $this->result(['errorMsg'=>'Error. transactionId not set','status'=>103,'providerTrnId'=>$providerTrnId]);
        }
        if(!$phone = $this->getPhone($PerformTransactionArguments->parameters)){
            return $this->result(['errorMsg'=>'Error. client_id not set','status'=>102,'providerTrnId'=>$providerTrnId]);
        }

        Log::info('soap perform');

        $options = [
            'url' => 'paynet/perform',
            'data'=>[
                'amount' =>$PerformTransactionArguments->amount,
                'serviceId' =>$PerformTransactionArguments->serviceId,
                'transactionId' =>$PerformTransactionArguments->transactionId,
                'transactionTime' =>$PerformTransactionArguments->transactionTime,
                'phone' => $phone
            ]
        ];



            return $this->result([
                'errorMsg'=>'Success',
                'status' => 0,
                'providerTrnId' =>$providerTrnId
            ]);

        return $this->result([
            'errorMsg'=>'Error',
            'status' => 101,
            'providerTrnId' =>$providerTrnId
        ]);

    }





}
