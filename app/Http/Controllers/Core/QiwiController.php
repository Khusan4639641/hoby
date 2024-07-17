<?php


namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\User;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class QiwiController extends CoreController
{

    // проверка юзера
    public function QiwiCheck(Request $request)
    {

        if ($request->has('phone')) {
            if ($phone = correct_phone($request->phone)) {
                Log::channel('Qiwi')->info($phone);

                if ($buyer = User::where('phone', $phone)->where('status' , 4)->first()) { // только верифицированные могут пополнять

                    $this->result = [
                        "status" => 'true',
                        "code" => '200',
                        "fio" => $buyer->getFioAttribute(),
                    ];
                }else{
                    $this->result = [
                        "status" => 'false',
                        "code" => '404',
                        "message" => 'Client not found or not verified',
                    ];
                }
            }

        }else{
            $this->result = [
                "status" => 'false',
                "code" => '404',
                "message" => 'Incorrect phone data',
            ];
        }
        Log::channel('Qiwi')->info($this->result() );
        return $this->result();



    }

    //Оплата с платежной системы Apelsin
    public function QiwiPay(Request $request) {

        Log::channel('Qiwi')->info($request );

        if ($buyer = Buyer::where('phone', $request->phone)->where('status' , 4)->first()) { // только верифицированные могут пополнять
            $amount = $request->amount / 100;  // в тиинах присылают


            if($request->has('transactionId') && $request->has('amount') && $request->amount > 0) {

                if(!Payment::where('transaction_id' , $request->transactionId)->first()){
                    $payment = new Payment();
                    $payment->user_id = $buyer->id;
                    $payment->transaction_id = $request->transactionId;
                    $payment->amount = $amount;
                    $payment->type = 'user';
                    $payment->payment_system = 'QIWI';
                    $payment->status = 1;
                    $payment->state = 1; // хз зачем

                    $payment->save();

                    $buyer->settings->personal_account += $payment->amount;
                    $buyer->settings->save();

                    /*// уведомить о пополнении сервер автосписания
                    $data = [
                        'user_id' => $buyer->id,
                        'amount' => $payment->amount,
                    ];
                    App\Helpers\PaymentHelper::fillAccount($data);*/

                    $this->result = [
                        "status" => 'true',
                        "code" => '200',
                        "message" => 'Successful transaction'
                    ];

                }else{
                    $this->result = [
                        "status" => 'false',
                        "code" => '404',
                        "message" => 'TransactionId already exist',
                    ];
                }

            }else{
                $this->result = [
                    "status" => 'false',
                    "code" => '404',
                    "message" => 'Incorrect transaction data',
                ];
            }
        }else{
            $this->result = [
                "status" => 'false',
                "code" => '404',
                "message" => 'Client not found or not verified',
            ];
        }

        Log::channel('Qiwi')->info($this->result() );
        return $this->result();

    }

    // проверка транзакции
    public function QiwiCheckTransaction(Request $request) {

    }

    // отмена транзакции
    public function QiwiCancel(Request $request) {
        if($request->has('transactionId')){
            Log::channel('Qiwi')->info($request );

            If($old_payment = Payment::where('transaction_id' , $request->transactionId)->where('type', 'refund' )->first()){
                $this->result = [
                    "status" => 'false',
                    "code" => '404',
                    "message" => 'Reverse transactionId already exist',
                ];
                return $this->result();
            }

            if($old_payment = Payment::where('transaction_id' , $request->transactionId)->first()){

                    $payment = new Payment();
                    $payment->user_id = $old_payment->user_id;
                    $payment->transaction_id = $old_payment->transaction_id;
                    $payment->amount = $old_payment->amount * -1;
                    $payment->type = 'refund';
                    $payment->payment_system = 'QIWI';
                    $payment->status = 1;
                    $payment->state = 1; // хз зачем

                    $payment->save();

                    $buyer = Buyer::find($old_payment->user_id);
                    $buyer->settings->personal_account -= $old_payment->amount;
                    $buyer->settings->save();

                    $this->result = [
                        "status" => 'true',
                        "code" => '200',
                        "message" => 'ROK Successful transaction'
                    ];
            }else{
                $this->result = [
                    "status" => 'false',
                    "code" => '404',
                    "message" => 'TransactionId not exist',
                ];
            }
            Log::channel('Qiwi')->info($this->result() );
            return $this->result();
        }
    }


}
