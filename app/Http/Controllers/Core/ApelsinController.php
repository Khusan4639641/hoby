<?php


namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\User;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApelsinController extends CoreController
{

    // проверка юзера
    public function apelsinCheck(Request $request)
    {

        if ($request->has('phone')) {
            if ($phone = correct_phone($request->phone)) {
                Log::channel('apelsin')->info($phone);

                if ($buyer = User::where('phone', $phone)->whereIn('status',[1, 2, 4, 12])->first()) { // только верифицированные могут пополнять

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
        Log::channel('apelsin')->info($this->result() );
        return $this->result();



    }

    //Оплата с платежной системы Apelsin
    public function apelsinPay(Request $request) {

        Log::channel('apelsin')->info($request );

        if ($buyer = Buyer::where('phone', $request->phone)->whereIn('status', [1, 2, 4, 12])->first()) { // только верифицированные могут пополнять
            $amount = $request->amount / 100;  // в тиинах присылают


            if($request->has('transactionId') && $request->has('amount') && $request->amount > 0) {

                if(!Payment::where('transaction_id' , $request->transactionId)->first()){
                    $payment = new Payment();
                    $payment->user_id = $buyer->id;
                    $payment->transaction_id = $request->transactionId;
                    $payment->amount = $amount;
                    $payment->type = 'user';
                    $payment->payment_system = 'APELSIN';
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

                    $client = Http::withOptions([
                        'cert' => [storage_path('cert_external/client_1.pfx'),  config('test.external_cert_pass')],
                        'curl' => [CURLOPT_SSLCERTTYPE => 'P12'],
                    ]);

                    $created_at = Carbon::make($payment->created_at);
                    $create_at    = Carbon::make($payment->create_at);
                    $performAt    = Carbon::make($payment->performAt);


                    $response = $client->post(config('test.external_service_url').'create/transaction', [
                        'contractId'         => $payment->contract_id,
                        'orderId'            => $payment->order_id,
                        'userId'             => $payment->user_id,
                        'phone'              => $buyer->phone,
                        'scheduleId'         => $payment->schedule_id,
                        'cardId'             => $payment->card_id,
                        'transactionId'      => $payment->id,
                        'clickTransactionId' => $payment->transaction_id,
                        'uuid'               => $payment->uuid,
                        'amount'             => $payment->amount * 100,
                        'type'               => $payment->type,
                        'paymentSystem'      => 'resusPAY',
                        'createdAt'          => $created_at->format('c'),
                        'createAt'           => $create_at ? $create_at->format('c') : null,
                        'updatedAt'          => $payment->updated_at->format('c'),
                        'performAt'          => $performAt ? $performAt->format('c') : null,
                    ]);

                    if ($response->failed()) {
                        Log::channel('apelsin_external')->info(__METHOD__, [
                            'code' => $response->status(),
                            'body' => $response->body()
                        ]);
                    }


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

        Log::channel('apelsin')->info($this->result() );
        return $this->result();

    }

    // проверка транзакции
    public function apelsinCheckTransaction(Request $request) {

    }

    // отмена транзакции
    public function apelsinCancel(Request $request)
    {
        if (!$request->has('transactionId')) {
            return $this->response('false', '404', 'TransactionId not exist');
        }

        Log::channel('apelsin')->info(__METHOD__, $request->all());
        $transaction = Payment::where([
            ['transaction_id', $request->transactionId],
            ['payment_system', Payment::PAYMENT_SYSTEM_APELSIN],
            ['created_at', '>=', date('Y-m-01 00:00:00')],
            ['status', 1]
        ])->first();

        if (!$transaction) {
            return $this->response('false', '404', 'TransactionId not exist');
        }

        if (Payment::where([['transaction_id', $request->transactionId], ['payment_system', Payment::PAYMENT_SYSTEM_APELSIN], ['status', -1]])->first()) {
            return $this->response('false', '404', 'Reverse transactionId already exist');
        }

        $buyer = Buyer::find($transaction->user_id)->load('settings');

        if ($buyer->settings->personal_account >= $transaction->amount) {
            DB::transaction(function () use ($buyer, $transaction) {
                $transaction->update([
                    'status' => -1,
                    'state' => 1,
                    'cancel_at' => now()
                ]);

                $refund = $transaction->replicate();
                $refund->amount = $transaction->amount * -1;
                $refund->type = Payment::PAYMENT_TYPE_REFUND;
                $refund->status = 1;
                $refund->state = 1;
                $refund->cancel_at = null;
                $refund->save();

                $buyer->settings->personal_account -= $transaction->amount;
                $buyer->settings->save();

                $client = Http::withOptions([
                    'cert' => [storage_path('cert_external/client_1.pfx'),  config('test.external_cert_pass')],
                    'curl' => [CURLOPT_SSLCERTTYPE => 'P12'],
                ]);

                $response = $client->post(config('test.external_service_url').'cancel/transaction', [
                    'userId'             => $refund->user_id,
                    'transactionId'      => $transaction->id,
                    'uuid'               => $refund->uuid,
                    'paymentSystem'      => 'resusPAY',
                    'reason'             => $refund->reason,
                    'updatedAt'          => Carbon::make($refund->updated_at)->format('c'),
                    'cancelAt'           => Carbon::now()->format('c'),
                ]);

                if ($response->failed()) {
                    Log::channel('apelsin_external')->info(__METHOD__, [
                        'code' => $response->status(),
                        'body' => $response->body()
                    ]);
                }
            });
        } else {
            return $this->response('true', '400', 'Insufficient funds');
        }

        Log::channel('apelsin')->info(__METHOD__, [
            'status' => 'true',
            'code' => '200',
            'message' => 'ROK Successful transaction'
        ]);

        return $this->response('true', '200', 'ROK Successful transaction');
    }

    private function response($status, $code, $message)
    {
        return response()->json([
            'status' => $status,
            'code' => $code,
            'message' => $message,
        ]);
    }


}
