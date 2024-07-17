<?php

namespace App\Http\Controllers\Core;


use App\Facades\OldCrypt;
use App\Helpers\SellerBonusesHelper;
use App\Helpers\UpayHelper;
use App\Models\Buyer;
use App\Models\Payment;
use App\Models\PaymentLog;
use App\Models\PayService as Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Request;


class ZpayController extends CoreController
{

    /**
     * ZpayController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->model = app(Model::class);
    }

    /**
     * получить баланс компании на счету Upay
     */
    public function getBalance(){
        $config = $this->config();
        $client = UpayHelper::connectedUpay($config);
        $result = UpayHelper::getBalance($client, $config);

        return $result['balance'];
    }


    public function pay(Request $request){
        $params = $request->all();

        $service = Model::find($params['id']);
        if($service){
            if($params['amount'] != '' && is_numeric($params['amount']) && $params['amount'] > 0) {

                if(SellerBonusesHelper::isAcceptableToPay($params['user_id'])){
                    if(SellerBonusesHelper::isEnoughFunds($params['user_id'], $params['amount'])){  // если есть zcoins и сумма оплаты не превышает их

                        $config = $this->config();
                        $account = $params['account'];  // телефон или логин платежного сервиса
                        $serviceId = $service->service_id; //платежный сервис
                        $amount = $params['amount'];
                        $sumForBankPayment = $amount * 100;

                        $account = preg_replace('/^\+?998/','',$account);

                        $client = UpayHelper::connectedUpay($config);

                        $pay = UpayHelper::BankPayment($client, $config, $serviceId, $account, $sumForBankPayment); // оплата

                        if($pay['status'] == 'error'){
                            $this->result['status'] = 'error';
                            $this->message('success', $pay['message']);
                            /*$paymentLog->status = 0;
                            $payment->status = 0;*/
                        }elseif($pay['status'] == 'success'){
                            SellerBonusesHelper::pay($params['user_id'], $pay['transaction_id'], $amount, $pay['response']['request'], $pay['response']['response']);
                            $this->result['status'] = 'success';
                            $this->message('success', __('cabinet/zpay.txt_paid'));
                        }

                    }else {
                        $this->result['status'] = 'error';
                        $this->message( 'danger', __( 'cabinet/zpay.err_buyer_zcoin' ) );
                    }
                }else {
                    $this->result['status'] = 'error';
                    $this->message( 'danger', __( 'cabinet/zpay.acceptable_date_expired' ) );
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
            'login'=> config('test.upay_login'),
            'password'=> OldCrypt::decryptString(config('test.upay_password')),
            'key'=> config('test.upay_key'),
            'credentials_login'=> config('test.upay_credentials_login'),
            'credentials_password'=> OldCrypt::decryptString(config('test.upay_credentials_password'))
        ];
        return $config;
    }
}
