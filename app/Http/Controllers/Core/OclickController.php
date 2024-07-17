<?php

namespace App\Http\Controllers\Core;

use App\Models\Buyer;
use App\Models\BuyerSetting;
use App\Models\Payment;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use click\models\Payments as OPayments;
use click\applications\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Helpers\PaymentHelper;

class OclickController extends CoreController{
    //

    /**
        пример запроса
    Array
    (
    "click_trans_id" => 14566027
    "service_id" => 14699
    "merchant_trans_id" => 1617975028
    "merchant_prepare_id" =>
    "amount" => 500
    "action" => 0
    "error" => 0
    "error_note" => Ok
    "sign_time" => 2021-04-09 17:31:29
    "sign_string" => a11f50d3999a852a9e0269e09d5824eb
    "click_paydoc_id" => 16853761
    )
     *
     *
     * #	Наименование параметра	Тип данных	Описание
    click_trans_id	int	Номер транзакции (итерации) в системе CLICK, т.е. попытки провести платеж.
    service_id	int	ID сервиса
    click_paydoc_id	int	Номер платежа в системе CLICK. Отображается в СМС у клиента при оплате.
    merchant_trans_id	varchar	ID договора(для Интернет магазинов)/лицевого счета/логина в биллинге поставщика
    amount	float	Сумма оплаты (в сумах)
    action	int	Выполняемое действие. Для Prepare — 0
      error	int	Код статуса завершения платежа. 0 – успешно. В случае ошибки возвращается код ошибки.
      error_note	varchar	Описание кода завершения платежа.
    sign_time	varchar	Дата платежа. Формат «YYYY-MM-DD HH:mm:ss»
    sign_string	varchar	Проверочная строка, подтверждающая подлинность отправляемого запроса. ХЭШ MD5 из следующих параметров:
    md5( click_trans_id + service_id + SECRET_KEY* + merchant_trans_id + amount + action + sign_time)

    SECRET_KEY – уникальная строка, выдаваемая Поставщику при подключении
     *
     */

    public function init(Request $request){

        Log::channel('click')->info('INIT request from click');
        Log::channel('click')->info(print_r($request->all(),1));


        //return ['status'=>'success','data'=>'data'];

        $buyer = null;

        $transaction = NULL;
        if ($request->has('error') && $request->error == -5017) {
            $c_transaction = Payment::where([
                ['transaction_id', $request->merchant_prepare_id],
                ['payment_system', Payment::PAYMENT_SYSTEM_CLICK],
                ['created_at', '>=', date('Y-m-01 00:00:00')],
                ['status', 1]
            ])->first();
            if ($c_transaction) {
                $reverse = Payment::where([
                    ['transaction_id', $request->merchant_prepare_id],
                    ['type', Payment::PAYMENT_TYPE_REFUND]
                ])->first();
                if (!$reverse) {
                    Log::channel('click')->info(__METHOD__, ['reverse']);

                    $buyer = Buyer::find($c_transaction->user_id)->load('settings');

                    if ($buyer->settings->personal_account >= $c_transaction->amount) {
                        $refund = $c_transaction->replicate();
                        $refund->status = 1;
                        $refund->type = Payment::PAYMENT_TYPE_REFUND;
                        $refund->amount = (-1 * $c_transaction->amount);
                        $refund->cancel_at = null;

                        $buyer->settings->personal_account -= $c_transaction->amount;

                        $client = Http::withOptions([
                            'cert' => [storage_path('cert_external/client_1.pfx'),  config('test.external_cert_pass')],
                            'curl'     => [CURLOPT_SSLCERTTYPE => 'P12'],
                        ]);

                        DB::transaction(function () use ($c_transaction, $buyer,  $refund) {
                            $c_transaction->update([
                                'status' => -1,
                                'cancel_at' => now()
                            ]);

                            $refund->save();

                            $buyer->settings->save();
                        });

                        $created_at = Carbon::make($refund->created_at);
                        $create_at = Carbon::make($refund->create_at);
                        $perform_time = Carbon::make($refund->perform_time);
                        $performAt = Carbon::make($refund->performAt);

                        try {
                            $response = $client->post(config('test.external_service_url').'cancel/transaction', [
                                'contractId'         => $refund->contract_id,
                                'orderId'            => $refund->order_id,
                                'userId'             => $refund->user_id,
                                'scheduleId'         => $refund->schedule_id,
                                'cardId'             => $refund->card_id,
                                'transactionId'      => $c_transaction->id,
                                'clickTransactionId' => $refund->transaction_id,
                                'uuid'               => $refund->uuid,
                                'amount'             => $refund->amount * 100,
                                'type'               => $refund->type,
                                'paymentSystem'      => 'CLICK',
                                'reason'             => $refund->reason,
                                'createdAt'          => $created_at->format('c'),
                                'createAt'           => $create_at ? $create_at->format('c') : null,
                                'performTime'        => $perform_time ? $perform_time->format('c') : null,
                                'updatedAt'          => $refund->updated_at->format('c'),
                                'cancelAt'           => Carbon::now()->format('c'),
                                'performAt'          => $performAt ? $performAt->format('c') : null,
                            ]);

                            if ($response->failed()){
                                Log::channel('click_external')->info(__METHOD__, [
                                    'code' => $response->status(),
                                    'body' => $response->body()
                                ]);
                            }
                        } catch (\Throwable $throwable){
                            Log::channel('click_external')->info(__METHOD__, [
                                'message' => $throwable->getMessage(),
                                'line' => $throwable->getLine()
                            ]);
                        }

                        Log::channel('click')->info(__METHOD__, ['buyer reverse amount: ' . $c_transaction->amount]);

                        return response()->json([
                            'error' => -9,
                            'error_note' => 'Transaction cancelled'
                        ]);
                    }

                    // не хватка средств для снятия с клиента
                    return response()->json([
                        'error' => -9,
                        'error_note' => 'Transaction cancelled'
                    ]);
                }
            }
        }



        if($request->has('merchant_trans_id')){
            //$buyer = Buyer::find($request->merchant_trans_id); // поиск по user_id

            Log::channel('click')->info('phone: ' . $request->merchant_trans_id);

            $buyer = Buyer::where('phone',$request->merchant_trans_id)->whereIn('status',[1, 2, 4, 12])->first(); // поиск по телефону

            if($buyer != NULL){
                $transaction = Payment::find($request->merchant_prepare_id); // payment->id - получаем платеж
                if($transaction == NULL) { // если нет, создаем платеж

                    $payment = new Request();
                    $payment->merge([
                        'amount' => $request->amount,
                        'phone' => correct_phone($buyer->phone),
                        'user_id' => $buyer->id
                        //'merchant_trans_id' => $request->merchant_trans_id // не было
                    ]);
                    $result = $this->createPayment($payment);
                    if($result['status'] == 'success'){
                        // получаем созданный платеж
                        $transaction = Payment::where('payment_system', 'OCLICK')
                            ->where('transaction_id', $result['data']['transaction_id'])
                            //->where('transaction_id', correct_phone($buyer->phone))
                            //->where('status',0)
                            //->orderBy('id','DESC')
                            ->first(); // было buyer->id
                    }

                    Log::channel('click')->info('buyer '.$buyer->id.' pay amount: ' . $request->amount);
                }

            }
        }

        if(!$buyer){
            Log::channel('click')->info('Buyer not found!');

            // return response array-like
            return [
                'error' => -5,
                'error_note' => 'User does not exist'
            ];
        }

        $payments = new OPayments([
            'db' => [
                'dsn' => 'mysql:host='.config('database.connections.mysql.host').';dbname='.config('database.connections.mysql.database'),
                'username' => config('database.connections.mysql.username'),
                'password' => config('database.connections.mysql.password')
            ]
        ]);

        //Log::channel('click')->info($payments);

        $application = new Application([
            'type' => 'json',
            'model' => $payments,
        ]);

        // Log::channel('click')->info('before APP run');
        $result = $application->run();
        $response = json_decode($result, true);
        // Log::channel('click')->info('after APP run');
        switch ($response['error']){
            case -1:
                Log::channel('click')->info('Transaction has error!');
                if($transaction != NULL && $request->merchant_prepare_id == null){
                    $transaction->delete();
                }
                break;
        }

        // Log::channel('click')->info('response from CLICK:');
        // Log::channel('click')->info($response);

        if(isset($response['error']) && $response['error'] == 0){
            $accept = new Request();
            $accept->merge(['transaction_id' => $request->click_paydoc_id, 'phone' => correct_phone($buyer->phone) /*,'buyer_id' =>  $buyer->id Auth::id()*/ ]);
            $this->acceptPayment($accept);
            $transaction->save();

        }
        echo $result;
        //return $result;
        exit;
    }

    /**
     * @param Request $request
     * @return int
     */
    public function createPayment(Request $request): array{
        Log::channel('click')->info('buyer create payment');
        $transID = 0;
        if($request->has('amount') && $request->amount > 0) {
            $transID = time() + rand(100, 999);
            $payment = new Payment();
            $payment->user_id = $request->has('user_id') ? $request->user_id : Auth::id();
            // $payment->transaction_id = $request->has('user_id') ? $request->user_id : $transID;  // $request->merchant_trans_id,
            //$payment->transaction_id = $request->has('merchant_trans_id') ? $request->merchant_trans_id : $transID;  // $request->merchant_trans_id, // не было
            $payment->transaction_id = $transID; //$request->has('phone') ? $request->phone : $transID;
            $payment->amount = $request->amount;
            $payment->type = 'user';
            $payment->payment_system = 'OCLICK';
            $payment->status = 0;
            $payment->state = 0;
            $payment->reason = null;
            $payment->save();
            $this->result['status'] = 'success';
            $this->result['data']['transaction_id'] = $transID;
            Log::channel('click')->info('payment created success ' . $payment->id . ' ' . $payment->transaction_id);
        }else{
            $this->result['status'] = 'error';
            $this->message('danger', 'Error create transaction id');
            $this->result['data']['transaction_id'] = $transID;
            Log::channel('click')->info('payment NOT created, amount ' . $request->amount);
        }
        return $this->result();
    }

    /**
     * @param Request $request
     * @return array
     */
    public function deletePayment(Request $request): array{
        if($request->has('transaction_id') && $request->transaction_id > 0) {
            $payment = Payment::where('transaction_id', $request->transaction_id)->where('status', 0)->first();
            if($payment) {
                $payment->delete();
                $this->result['status'] = 'success';
                $this->result['data']['transaction_id'] = $request->transaction_id;
            }
        }else{
            $this->result['status'] = 'error';
            $this->message('danger', 'Incorrect transaction id');
        }
        return $this->result();
    }

    /**
     * @param Request $request
     * @return array|false|string
     */
    public function acceptPayment(Request $request): array {

        Log::channel('click')->info('buyer accept payment phone - merchant_trans_id ? ');

        Log::channel('click')->info($request);

        if($request->has('transaction_id') && $request->transaction_id > 0) {
            if($payment = Payment::where('transaction_id', $request->transaction_id)->where('status', 1)->first()) {

                /* if($request->has('phone') && !is_null($request->phone) ){
                    $buyer = Buyer::where('phone',$request->phone)->first();
                }else{
                    // $request->buyer_id ===== Auth::id()
                    $buyer = Buyer::where('id',Auth::id())->first();
                } */

                //$buyer = Buyer::where('id',$payment->user_id)->first();

                $buyerSettings = BuyerSetting::where('user_id',$payment->user_id)->first() ;

                //$buyer->settings->personal_account += $payment->amount;
                //$buyer->settings->save();

                $buyerSettings->personal_account += $payment->amount;
                if(!$buyerSettings->save()){
                    Log::channel('click')->info('error save ' . $payment->user_id . ' ' . $payment->amount);
                }

                $client = Http::withOptions([
                    'cert' => [storage_path('cert_external/client_1.pfx'),  config('test.external_cert_pass')],
                    'curl'     => [CURLOPT_SSLCERTTYPE => 'P12'],
                ]);

                $created_at = Carbon::make($payment->created_at);
                $create_at = Carbon::make($payment->create_at);
                $perform_time = Carbon::make($payment->perform_time);
                $cancel_at = Carbon::make($payment->cancel_at);
                $performAt = Carbon::make($payment->performAt);


                $response = $client->post(config('test.external_service_url').'create/transaction', [
                    'contractId'         => $payment->contract_id,
                    'orderId'            => $payment->order_id,
                    'userId'             => $payment->user_id,
                    'scheduleId'         => $payment->schedule_id,
                    'cardId'             => $payment->card_id,
                    'transactionId'      => $payment->id,
                    'clickTransactionId' => $payment->transaction_id,
                    'uuid'               => $payment->uuid,
                    'amount'             => $payment->amount * 100,
                    'type'               => $payment->type,
                    'paymentSystem'      => 'CLICK',
                    'state'              => $payment->state,
                    'reason'             => $payment->reason,
                    'createdAt'          => $created_at->format('c'),
                    'createAt'           => $create_at ? $create_at->format('c') : null,
                    'performTime'        => $perform_time ? $perform_time->format('c') : null,
                    'updatedAt'          => $payment->updated_at->format('c'),
                    'performAt'          => $performAt ? $performAt->format('c') : null,
                ]);

                if ($response->failed()){
                    Log::channel('click_external')->info(__METHOD__, [
                        'code' => $response->status(),
                        'body' => $response->body()
                    ]);
                }

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                /*// уведомить о пополнении сервер автосписания
                  $data = [
                      'user_id' => $payment->user_id,
                      'amount' => $payment->amount,
                  ];
                  PaymentHelper::fillAccount($data);*/
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

                $this->result['status'] = 'success';
                $this->result['data']['transaction_id'] = $request->transaction_id;
            } else {
                $this->result['status'] = 'error';
                $this->message('danger', 'Incorrect transaction id');
            }
        }else{
            $this->result['status'] = 'error';
            $this->message('danger', 'Incorrect transaction id');
        }
        Log::channel('click')->info('result:');

        Log::channel('click')->info($this->result());

        return $this->result();
    }
}
