<?php

namespace App\Http\Controllers\Core;


use App\Models\Buyer;
use App\Models\Payment;
use App\Models\PaymentLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;


class MyUzcardController extends CoreController
{

    private $key = '49015cb6b202d0f0f70f606a80df38c8'; // ключ для смены токена


    /**
     *
    "transId" : "123451234512345",
    "transTime" : "2021-06-15 10:00:00",
    "personalAccount" : "998901234567",
    "amount" : "1000",
    "accessToken" : "d5487da589e04a29b8e6efcc8e61eaa2",
     *
     */


    public function pay(Request $request){

        Log::channel('myuzcard')->info($request);

        if(empty($request->accessToken)){
            $this->result['status'] = 'error';
            $this->result['code'] = '404';
            $this->error('Token not found!');
            return $this->result();
        }

        if(!$user = User::where('api_token',$request->accessToken)->first()){
            $this->result['status'] = 'error';
            $this->result['code'] = '404';
            $this->error('Token incorrect!');
            return $this->result();

        }

        // поиск клиента
        if( $buyer = Buyer::where('phone', correct_phone($request->personalAccount))->first() ) {

                if ( !empty($request->amount) && (int)$request->amount > 1000 ) { // сумма пополнения

                    $payment = new Payment();
                    $payment->user_id = $buyer->id;
                    $payment->transaction_id = $request->transId;
                    $payment->amount = $request->amount;
                    $payment->type = 'user';
                    $payment->payment_system = 'MYUZCARD';
                    $payment->status = 1;
                    $payment->state = 0;
                    $payment->reason = null;
                    $payment->save();

                    $paymentLog = new PaymentLog();
                    $paymentLog->payment_id = $payment->id;
                    $paymentLog->request = $request;
                    $paymentLog->response = null;
                    $paymentLog->status = 1;
                    $paymentLog->save();

                    // пополняем сумму в кабинете пользователя
                    $buyer->settings->personal_account += $payment->amount;
                    $buyer->settings->save();

                    /*// уведомить о пополнении сервер автосписания
                    $data = [
                        'user_id' => $buyer->id,
                        'amount' => $payment->amount,
                    ];
                    App\Helpers\PaymentHelper::fillAccount($data);*/

                    Log::channel('myuzcard')->info('payment success');

                    $this->result['status'] = 'success';
                    return $this->result();

                } else {
                    $this->result['status'] = 'error';
                    $this->error('Invalid amount. Minimal amount 1000 sum.');
                    Log::channel('myuzcard')->info('Invalid amount. Minimal amount 1000 sum.');
                }


        }else{

            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->error( 'Client not found!');

        }

        return $this->result();
    }

    public function getToken(Request $request){

        if($request->key == $this->key ){

            if($user = User::find(1001)) { // 1001 пользователь myuzcard
                $user->api_token = md5(Hash::make('myuzcard-' . time()));
                $user->save();
                $this->result['status'] = 'success';
                $this->result['data'] = ['api_token' => $user->api_token];
                return $this->result();
            }else{
                $this->result['status']='error';
                $this->result['response']['error'] = 'User not found!';

            }

        }else{
            $this->result['status']='error';
            $this->result['response']['error'] = 'Key incorrect';
        }

        return $this->result();

    }


}
