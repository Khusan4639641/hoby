<?php


namespace App\Http\Controllers\Core;


use App\Models\Buyer;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Support\Facades\Log;

class PaynetController extends CoreController {

    /*public function __construct()
    {
        parent::__construct();
        $this->model = app(Payment::class);

        //Eager load
        $this->loadWith = [];
    }*/

    // создание транзакции
    public function PerformTransaction(Request $request){
        Log::channel('paynet')->info('paynetController PerformTransaction');
        Log::channel('paynet')->info($request);

        if($paynet = Payment::where('transaction_id',(string)$request->transactionId)->where('payment_system','PAYNET')->first()){
            if($paynet->state==2){ // отменена
                return ['status'=>'error','error'=>'Transaction has been canceled','transactionState'=>2,'code'=>202];
            }else{
                return ['status'=>'error','error'=>'Transaction exist','transactionState'=>1,'code'=>201];
            }
        }

        $phone = correct_phone($request->phone);
        Log::channel('paynet')->info('phone: '.$phone);

        if($buyer = Buyer::with('settings')->where('phone',$phone)->where('status',4)->whereNull('company_id')->first()){
            $amount = $request->amount / 100;
            $paynet = new Payment();
            try {
                DB::transaction(function () use ($request, $buyer, $amount, $paynet){
                    $paynet->transaction_id = $request->transactionId;
                    $paynet->amount = $amount;
                    $paynet->user_id = $buyer->id;
                    $paynet->type = 'user';
                    $paynet->payment_system = 'PAYNET';
                    $paynet->status = 1;
                    $paynet->state = 1;
                    $paynet->create_at = $request->transactionTime; // date('Y-m-d H:i:s',strtotime($request->transactionTime));
                    $paynet->save();

                    $buyer->settings->personal_account += $paynet->amount;
                    $buyer->settings->save();

                    Log::channel('paynet')->info($buyer->id . ' пополнил: ' . $amount );
                });
                $client = Http::withOptions([
                    'cert' => [storage_path('cert_external/client_1.pfx'),  config('test.external_cert_pass')],
                    'curl'     => [CURLOPT_SSLCERTTYPE => 'P12'],
                ]);

                $created_at = Carbon::parse($paynet->created_at);
                $create_at    = Carbon::make($paynet->create_at);
                $perform_time = Carbon::make($paynet->perform_time);
                $performAt    = Carbon::make($paynet->performAt);

                $response = $client->post(config('test.external_service_url').'create/transaction', [
                    'contractId'         => $paynet->contract_id,
                    'orderId'            => $paynet->order_id,
                    'userId'             => $paynet->user_id,
                    'scheduleId'         => $paynet->schedule_id,
                    'cardId'             => $paynet->card_id,
                    'transactionId'      => $paynet->id,
                    'uuid'               => $paynet->uuid,
                    'amount'             => $paynet->amount * 100,
                    'type'               => $paynet->type,
                    'paymentSystem'      => 'PAYNET',
                    'createdAt'          => $created_at->format('c'),
                    'createAt'           => $create_at ? $create_at->format('c') : null,
                    'performTime'        => $perform_time ? $perform_time->format('c') : null,
                    'updatedAt'          => $paynet->updated_at->format('c'),
                    'performAt'          => $performAt ? $performAt->format('c') : null,
                ]);

                if ($response->failed()) {
                    Log::channel('paynet_external')->info(__METHOD__, [
                        'code' => $response->status(),
                        'body' => $response->body()
                    ]);
                }
                return ['status' => 'success', 'providerTrnId' => $paynet->id,'balance'=>(int)($buyer->settings->personal_account*100) ];
            } catch (\Throwable $exception){
                Log::channel('paynet')->info('Transaction not saved');
                return ['status'=>'error','error'=>$exception->getMessage()];
            }

        }
        Log::channel('paynet')->info('Buyer not found phone: ' . $phone);
        return ['status'=>'error','error'=>'Buyer not found','code'=>302];

    }

    // проверка транзакции
    public function CheckTransaction(Request $request){
        Log::channel('paynet')->info('paynetController CheckTransaction');
        Log::channel('paynet')->info($request);

        if($paynet = Payment::with('buyer','buyer.settings')->where('transaction_id',$request->transactionId)->where('payment_system','PAYNET')->first()){

            if( $paynet->state == 2 ){ // отменена
                Log::channel('paynet')->info($paynet->buyer->id . ' Transaction has been canceled ' . $paynet->transactionId . ' на сумму ' . $paynet->amount);
                return ['status'=>'error','error'=>'Transaction has been canceled','transactionState'=>2,'code'=>202];
            }elseif($paynet->state==1 && $paynet->buyer){
                Log::channel('paynet')->info($paynet->buyer->id . ' Подтверждение платежа ' . $paynet->transactionId . ' на сумму ' . $paynet->amount);
                //$paynet->buyer->settings->personal_account+=$paynet->amount;
                $paynet->status = 1;

                if($paynet->save() ){ // && $paynet->buyer->settings->save()) {
                    return ['status' => 'success', 'transactionState' => 1,'providerTrnId'=>$paynet->id];
                }else{
                    Log::channel('paynet')->info($paynet->buyer->id . ' Ошибка при сохраненнии транзакции');
                    return ['status'=>'error','error'=>'Transaction cannot save','transactionState'=>1,'code'=>102];
                }
            }
        }
        Log::channel('paynet')->info($paynet->buyer->id . ' Transaction not found');
        return ['status'=>'error','error'=>'Transaction not found','transactionState'=>0,'code'=>102];
    }

    public function CancelTransaction(Request $request){
        Log::channel('paynet')->info('paynetController CancelTransaction');
        Log::channel('paynet')->info($request);
        if($paynet = Payment::with('buyer','buyer.settings')->where('transaction_id',$request->transactionId)->where('payment_system','PAYNET')->first()){
            if($paynet->state==2){ // отменена
                return ['status'=>'error','error'=>'Transaction has been canceled','transactionState'=>2,'code'=>202];
            }else{
                if($paynet->buyer->settings->personal_account < $paynet->amount){
                    return ['status'=>'error','error'=>'The transaction cannot be canceled','transactionState'=>$paynet->state,'code'=>77];
                }
                $paynet->state=2;
                $paynet->status=-1;
                $paynet->buyer->settings->personal_account -= $paynet->amount;

                if( $paynet->save() && $paynet->buyer->settings->save() ) {

                    $paynetCancel = $paynet->replicate();
                    $paynetCancel->type = 'refund';
                    $paynetCancel->status = 1;
                    $paynetCancel->amount = -1 * $paynetCancel->amount;

                    if($paynetCancel->save()) {
                        $client = Http::withOptions([
                            'cert' => [storage_path('cert_external/client_1.pfx'),  config('test.external_cert_pass')],
                            'curl' => [CURLOPT_SSLCERTTYPE => 'P12'],
                        ]);

                        $response = $client->post(config('test.external_service_url').'cancel/transaction', [
                            'contractId'         => $paynetCancel->contract_id,
                            'userId'             => $paynetCancel->user_id,
                            'transactionId'      => $paynet->id,
                            'amount'             => $paynetCancel->amount,
                            'type'               => $paynetCancel->type,
                            'paymentSystem'      => 'PAYNET',
                            'reason'             => $paynetCancel->reason,
                            'cancelAt'           => Carbon::now()->format('c'),
                        ]);

                        if ($response->failed()) {
                            Log::channel('paynet_external')->info(__METHOD__, [
                                'code' => $response->status(),
                                'body' => $response->body()
                            ]);
                        }

                        Log::channel('paynet')->info($paynet->buyer->id . ' Отмена платежа ' . $paynet->transactionId . ' на сумму ' . $paynet->amount);
                        return ['status' => 'success','transactionState'=>2];
                    }else{
                        return ['status' => 'error', 'error'=>'System error, cannot save cancel transaction','transactionState'=>$paynet->state,'code' => 102];
                    }
                }
                return ['status' => 'error', 'error'=>'System error','transactionState'=>2,'code' => 102];
            }
        }

        return ['status'=>'error','error'=>'Transaction not found','code'=>305,'transactionState'=>2];

    }

    // получение информации о клиенте
    public function GetInformation(Request $request){

        Log::channel('paynet')->info('paynetController GetInformation');
        Log::channel('paynet')->info($request);

        $phone = correct_phone($request->phone);

        if( $buyer = Buyer::where('phone', $phone)->first() ){

            if($buyer->status==4 && ( is_null($buyer->company_id) || (int)$buyer->company_id==0 ) ){
                return ['status' => 'success'];
            }

            if($buyer->status!=4 || (int)$buyer->company_id>0){
                return ['status' => 'error', 'error' => 'Transaction for the subscriber is not allowed','code'=>501];
            }

        }
        Log::channel('paynet')->info('Buyer not found phone: ' . $phone);
        return ['status'=>'error','error'=>'Buyer not found','code'=>302];



    }

    // отправка пополнений за выбранный период
    public function GetStatement(Request $request){
        Log::channel('paynet')->info('paynetController GetStatement');
        Log::channel('paynet')->info($request);

        if($paynet = Payment::where('type','user')->where('payment_system','PAYNET')->where('state',1)->whereBetween('created_at',[$request->dateFrom,$request->dateTo])->get()){

            $statements = [];

            foreach($paynet as $pay){
                $statements[] = [
                    //'statements' =>[
                    'amount' => (int)($pay->amount *100),
                    //'providerTrnId' => $pay->id,
                    'transactionId' => $pay->transaction_id,
                    'transactionTime' => $pay->create_at
                    //]
                ];
            }

            return ['status' => 'success','data'=>$statements];

        }

        Log::channel('paynet')->info('Transactions not found');
        return ['status'=>'error','error'=>'Transactions not found','code'=>304];
    }
}
