<?php

namespace App\Http\Controllers\Core;


use App\Models\Buyer;
use App\Models\Payment;
use App\Models\PaymentLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;


class UpayController extends CoreController
{

    private $key = '99ff912e28a84ea971480b89ee31367e'; // ключ для смены токена

    /**

    upayTransId	Integer	Уникальный номер транзакции в системе UPAY.
    upayTransTime	String	Время оплаты.Формат: YYYY-MM-DD HH:mm:ss.
    upayPaymentAmount	Integer	Сумма оплаты.
    personalAccount	String	Логин, номер телефона или номер договора за который была произведена оплата. По этому параметру поставщик услуг сможет определить куда и откуда была осуществлена оплата. Например, при оплате услуг мобильных операторов personalAccount будет равен номеру на который произведена оплата.
    accessToken	String	Сгенерированный ключ для безопасности отправки данных (оговаривается интеграторами)
     *
     */


    public function pay(Request $request){

        Log::channel('upay')->info('pay');
        Log::channel('upay')->info($request);

        if(empty($request->accessToken)){
            $result['status'] = 100;
            $result['message']= 'Токен не задан!';
            Log::channel('upay')->info('Token empty!');
            return $result;
        }
        if(!$request->has('personalAccount')){
            $result['status'] = 104;
            $result['message'] ='Телефон не задан';
            Log::channel('upay')->info('Phone empty!');
            return $result;
        }

        // проверка входящих параметров от upay
        $sign = md5($request->upayTransId . $request->personalAccount . $request->upayPaymentAmount .  $this->key);

        if( $sign != $request->accessToken ){
            $result['status'] = 101;
            $result['message'] ='Неверный токен!';
            Log::channel('upay')->info('Token incorrect!');
            return $result;
        }

        // поиск клиента
        if( $buyer = Buyer::where('phone', correct_phone($request->personalAccount))->where('status',4)->first() ) {

                if ( !empty($request->upayPaymentAmount) && (int)$request->upayPaymentAmount >= 1000 ) { // сумма пополнения

                    $payment = new Payment();
                    $payment->user_id = $buyer->id;
                    $payment->transaction_id = $request->upayTransId;
                    $payment->amount = $request->upayPaymentAmount;
                    $payment->type = 'user';
                    $payment->payment_system = 'UPAY';
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

                    //return $buyer->settings->personal_account;

                    if(isset($buyer->settings)) {
                        // пополняем сумму в кабинете пользователя
                        $buyer->settings->personal_account += $payment->amount;
                        $buyer->settings->save();

                        /*// уведомить о пополнении сервер автосписания
                            $data = [
                                'user_id' => $buyer->id,
                                'amount' => $payment->amount,
                            ];
                            App\Helpers\PaymentHelper::fillAccount($data);*/

                        Log::channel('upay')->info('payment success');

                        $result['status'] = 1;
                        $result['message'] = 'Успешно';

                        return $result;
                    }else{
                        $result['status'] = 103;
                        $result['message'] = 'Клиент не найден или не верифицирован';
                        Log::channel('upay')->info('Client not found!');
                    }

                } else {
                    $result['status'] = 102;
                    $result['message'] ='Неверная сумма. Минимальная сумма 1000 сум.';
                    Log::channel('upay')->info('Invalid amount. Minimal amount 1000 sum.');
                }


        }else{

            $result['status'] = 103;
            $result['message'] = 'Клиент не найден или не верифицирован';
            Log::channel('upay')->info('Client not found!');

        }

        return $result;
    }

    // проверка юзера
    public function check(Request $request)
    {

        Log::channel('upay')->info('check');
        Log::channel('upay')->info($request);

        if(empty($request->accessToken)){
            $result['status'] = 100;
            $result['message']= 'Токен не задан!';
            Log::channel('upay')->info('Token incorrect!');
            return $result;
        }

        // проверка входящих параметров от upay
        $sign = md5($request->personalAccount . $this->key);

        if( $sign != $request->accessToken ){
            $result['status'] = 101;
            $result['message']= 'Неверный токен!';

            Log::channel('upay')->info('Token incorrect!');

            return $result;

        }

        if ($request->has('personalAccount')) {

            $phone = correct_phone($request->personalAccount);

            Log::channel('upay')->info('check user: ' . $phone);

            if ($buyer = Buyer::where('phone', $phone)->where('status' , 4)->first()) { // только верифицированные могут пополнять

                if(isset($buyer->settings)) {
                    $result['status'] = 1;
                    $result['message'] = 'Успешно';
                    $result['username'] = $buyer->name . ' ' . $buyer->surname . ' ' . $buyer->patronymic;
                }else{
                    $result['status'] = 103;
                    $result['message'] = 'Клиент не найден или не верифицирован';
                }

            }else{
                $result['status'] = 103;
                $result['message'] = 'Клиент не найден или не верифицирован';
            }

        }else{
            $result['status'] = 104;
            $result['message'] ='Телефон не задан';
        }

        Log::channel('upay')->info($result );

        return $result;

    }


    /* public function getToken(Request $request){

        if($request->key == $this->key ){

            if($user = User::find(1000)) { // 1001 пользователь upay
                $user->api_token = md5(Hash::make('upay-' . time()));
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

    } */

}
