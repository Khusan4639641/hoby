<?php

namespace App\Http\Controllers\Core;


use App\Facades\OldCrypt;
use App\Helpers\UpayHelper;
use App\Models\Buyer;
use App\Models\Payment;
use App\Models\PaymentLog;
use App\Models\PayService as Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Request;


class PayController extends CoreController
{

    /**
     * payController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->model = app(Model::class);
    }


    // 24.04.2021 - оплата с помощью UPay
    public function payment(Request $request){
        $params = $request->all();
        $service = Model::find($params['id']);
        $buyer = Buyer::find($params['user_id']);
        if($service){
            if($params['amount'] != '' && is_numeric($params['amount']) && $params['amount'] > 0) {
                if($buyer && $buyer->settings->zcoin >= $params['amount']){

                    $config = $this->config();
                    $account = $params['account'];
                    $serviceId = $params['service_id'];
                    $uid = $params['user_id'];
                    $sum = $params['amount'];
                    $sum = $sum * 100;

                    $account = preg_replace('/^\+?998/','',$account);

                    $payment = new Payment();
                    $paymentLog = new PaymentLog();

                    $client = UpayHelper::connectedUpay($config);
                    /*$check = UpayHelper::BankAccountCheck($client, $config, $account, $serviceId);
                    if($check['status'] == 'error'){
                        $this->result['status'] = 'error';
                        $this->message('success', $check['message']);
                    }else{*/

                    $pay = UpayHelper::BankPayment($client, $config, $serviceId, $account, $sum);
                    if($pay['status'] == 'error'){
                        $this->result['status'] = 'error';
                        $this->message('success', $pay['message']);
                        $paymentLog->status = 0;
                        $payment->status = 0;
                    }elseif($pay['status'] == 'success'){
                        //Уменьшаем бонусы
                        $buyer->settings->zcoin -=  $params['amount'];
                        $buyer->settings->save();

                        $paymentLog->status = 1;
                        $payment->status = 1;
                        $payment->transaction_id = $pay['transaction_id'];

                        $this->result['status'] = 'success';
                        $this->message('success', __('cabinet/zpay.txt_paid'));
                    }

                    $payment->user_id = $uid;
                    $payment->amount = 0-$sum/100;
                    $payment->type = 'user';
                    $payment->payment_system = 'TEST';
                    $payment->save();

                    $paymentLog->payment_id = $payment->id;
                    $paymentLog->request = $pay['response']['request'];
                    $paymentLog->response = $pay['response']['response'];
                    $paymentLog->save();
                    //}
                }else {
                    $this->result['status'] = 'error';
                    $this->message( 'danger', __( 'cabinet/zpay.err_buyer_zcoin' ) );
                }
            }else {
                $this->result['status'] = 'error';
                $this->message( 'danger', __( 'cabinet/zpay.err_amount_format' ) );
            }
        }else{
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message( 'danger', __( 'app.err_not_found' ) );
        }

        return $this->result();
    }

    private function config(){
        $config = [

        ];
        return $config;
    }
}
