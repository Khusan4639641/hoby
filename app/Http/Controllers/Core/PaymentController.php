<?php


namespace App\Http\Controllers\Core;


use App\Classes\CURL\TEST\RefundPayment;
use App\Helpers\CurlHelper;
use App\Helpers\NotificationHelper;
use App\Helpers\PaymentHelper;
use App\Helpers\TelegramHelper;
use App\Models\Contract;
use App\Models\ContractPaymentsSchedule as CPS;
use App\Models\CronData;
use App\Models\CronPayment;
use App\Models\CronUsersDelays;
use App\Models\Payment;
use App\Models\Payment as Model;
use App\Models\PaymentLog;
use App\Models\PaymentsData;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;


class PaymentController extends CoreController {



    /**
     * Fields validator
     *
     * @param array $data
     *
     * @return Validator
     */
    private $validatorRules = [ ];

    const PASSWORD = 620285;

    /**
     * BuyerPaymentController constructor.
     */
    public function __construct() {
        parent::__construct();

        $this->model = app( Model::class );
        $this->config = Config::get( 'test.preview' );

        $this->loadWith = ['contract'];
    }

    /**
     * Execute autopaymentNew
     * 17.08 - для многопоточной обработки списания
     * @return void
     */
    public function autopaymentNew(){

        return true;

    }


    /**
     * Execute autopaymentNew
     * 17.08 - для многопоточной обработки списания
     * @return void
     */
    public function autopaymentMany(){

        if( ! $cronData = CronData::where('status',0)->first() ){
            // Log::channel('autopayment')->info('STOP NO FREE CronData');
            Log::channel('autopayment')->info('STOP! CRON ALREADY RUNNING OR NO FREE RANGE!');

            exit;
        }
        $cronData->status = 1;
        $cronData->save();


        $cp = 0;

        Log::channel('autopayment')->info('* * * START AUTOMATIC.PAYMENT.START >>>');
        Log::channel('cronpayment')->info('* * * START AUTOMATIC.PAYMENT.START >>>');



        $starttime = microtime(true);

        $cron_data = CronData::select(DB::raw('MAX(end) as end'))->orderBy('end','DESC')->first();
        $max_id = $this->getMaxID();  // последняя запись в schedule

        $_min = 0;
        if( isset($cron_data->end) && $cron_data->end < $max_id ) {
            $count_data = ceil(($max_id-$cron_data->end)/100 ) ;
            $_min = $cron_data->end ;
            $add_range = true;
        }elseif(!isset($cron_data->end)){
            $count_data = ceil($max_id/100);
            $add_range = true;
        }else{
            $add_range = false;
        }

        if($add_range){
            for($r = 0; $r<$count_data; $r++){
                $cron_data = new CronData();
                $cron_data->start = $r*100 + $_min +1 ;
                $cron_data->end =  $cron_data->start + 99;
                $cron_data->status = 0;
                $cron_data->cnt = 0;
                $cron_data->save();
            }
        }



        if( $cronData->cnt > 0 ){
            $len = ceil($cron_data->end / 100);
            if( $cronData->cnt < $len - 5 ) {
                $cronData->cnt++;
                $cronData->save();
                exit; // 5 - колво кронов
            }else{
                $cronData->cnt = 0;
            }

        }

        $cronData->status = 1;
        $cronData->save();

        if(!$payment_data = PaymentsData::find(1)){
            $payment_data = new PaymentsData();
            $payment_data->id = 1;
            $payment_data->quantity = 200;
            $payment_data->last_id = $this->getMinID();
        }

        /* if($payment_data->is_worked==1){
            Log::channel('autopayment')->info('STOP CRON already running!');
            Log::channel('cronpayment')->info('STOP CRON already running');
            exit;
        } */

       // $payment_data->is_worked = 1; // блокируем cron
       // $payment_data->save();
        Log::channel('autopayment')->info('CRON status 1!');

        $params = [
            //'last_id' => $payment_data->last_id,
            //'quantity' => $payment_data->quantity,

            'start' => $cron_data->start,
            'end' => $cron_data->end,
        ];



        Log::channel('autopayment')->info('range: '.$params['start'] . ' to ' . $params['end']);

        $scheduleList = PaymentHelper::getScheduleList($params);

        $count = count($scheduleList);
        if($count==0 ){
            Log::channel('autopayment')->info('no contracts: max_id ' . $max_id );

            // 29.07 пропуск диапазонов, в которых нет данных для списания
            if( $max_id >= $params['end']) {
                $skip = 0;
                while (count($scheduleList) == 0 && $max_id >= $params['last_id']) {
                    $params['start'] += $payment_data->quantity;
                    $params['last_id'] += $payment_data->quantity;
                    $scheduleList = PaymentHelper::getScheduleList($params);
                    $skip++;
                    Log::channel('autopayment')->info('INNER skip ' . $skip  . ' last_id: ' .$params['last_id']);
                }
            }

            // начать сначала
            if( count($scheduleList)==0 && $max_id < $params['last_id'] ){
                $params['last_id'] = 1;
                $skip = 0;
                while(count($scheduleList)==0 && $max_id >= $params['last_id'] ){
                    $scheduleList = PaymentHelper::getScheduleList($params);
                    $skip++;
                    $params['last_id'] += $payment_data->quantity;

                    Log::channel('autopayment')->info('START skip ' . $skip  . ' last_id: ' .$params['last_id']);
                }
            }
        }

        if(count($scheduleList)>0){

            Log::channel('autopayment')->info('contracts count: ' . count($scheduleList));

            $current_day = time();
            $current_month = time() - 30*86400;

            foreach ($scheduleList as $payment) {

                if ( ! isset($payment->contract) ) continue;
                if ( $payment->contract->status == Contract::STATUS_CANCELED ) continue;
                if ( $payment->contract->status == Contract::STATUS_AWAIT_SMS ) continue;

                list($date, $time) = explode(' ', $payment->payment_date);

                Log::channel('autopayment')->info('---');

                if (Carbon::parse($date . ' 23:59:59')->timestamp < $current_day && $payment->paid_at == '') {
                    Log::channel('autopayment')->info('contracts change status: from ' . $payment->contract->status . ' to 4');
                    $payment->contract->status = Contract::STATUS_OVERDUE_30_DAYS; // просрочка более дня
                    $payment->contract->save();
                }

                if (Carbon::parse($date . ' 23:59:59')->timestamp < $current_month && $payment->paid_at == '') {
                    Log::channel('autopayment')->info('contracts change status: from ' . $payment->contract->status . ' to 3');
                    $payment->contract->status = Contract::STATUS_OVERDUE_60_DAYS; // просрочка больше месяца
                    $payment->contract->save();
                    Log::channel('autopayment')->info('contract status 3 : ' . $payment->contract_id . ' sc: ' . $payment->id);
                }

                $result = rand(0,1);

                /* try {
                    $result = PaymentHelper::actionPayment($payment);
                }catch (\Exception $e){
                    Log::channel('cronpayment')->info('ERROR exception');
                    Log::channel('cronpayment')->info($e);

                } */

                if($result) $cp++;

                $data = $payment->id . '.' . $payment->contract_id . '.' . $payment->contract->order_id . '.' . $payment->id . '.' . $payment->user_id . '.' . $payment->contract->status . '.' . $payment->balance;
                $notifyData = [
                    'contract' => $payment->contract_id,
                    'order' => $payment->contract->order,
                    'order_id' => $payment->contract->order_id,
                    'buyer_id' => $payment->user_id,
                    'status' => __('contract.status_' . $payment->contract->status),
                    'hash' => md5( $data ),
                ];
               //  NotificationHelper::orderDelay($notifyData);

            }

            Log::channel('autopayment')->info("---\n" . 'contracts всего: ' . count($scheduleList) . ' обработано: ' . $cp);

            Log::channel('autopayment')->info('autopayment: from ' . $params['last_id'] . ' to: ' . $payment->id);

            // $payment_data->last_id = $payment->id + 1;
            // $payment_data->save();

        }

        $endtime = microtime(true);
        $dt = $endtime - $starttime;

        Log::channel('autopayment')->info('time: ' . number_format($dt,2,'.',' ') . ' sec');
        Log::channel('cronpayment')->info('time: ' . number_format($dt,2,'.',' ') . ' sec');

        //$payment_data->is_worked = 0; // освобождаем cron
        //$payment_data->save();

        $cronData->status = 0;
        $cronData->save();

        Log::channel('autopayment')->info('END AUTOMATIC.PAYMENT <<<');
        Log::channel('cronpayment')->info('END AUTOMATIC.PAYMENT <<<');
    }

    public function getMinID(){
        if($res = CPS::select(DB::raw('MIN(id) as id'))->from('contract_payments_schedule')->where('status',0)->first()){
            return $res->id;
        }
        return 1;
    }
    public function getMaxID(){
        if($res = CPS::select(DB::raw('MAX(id) as id'))->from('contract_payments_schedule')->where('status',0)->first()){
            return $res->id;
        }
        return 1;
    }
    public function getUsersMaxID(){
        if($res = CronUsersDelays::select(DB::raw('MAX(id) as id'))->from('cron_users_delays')->where('status',0)->first()){
            return $res->id;
        }
        return 1;
    }


    /**
     * Get items ids list
     *
     * @param array $params
     * @return array|bool|false|string
     */
    public function list( array $params = []) {
        $user = Auth::user();
        //Get data from REQUEST if api_token is set
        $request = request()->all();
        if ( isset( $request['api_token'] ))
            $params = $request;

        //Filter elements
        $filter = $this->filter($params);

        //Render items
        /*foreach ($filter['result'] as $index => $item){
            $item->permissions = $this->permissions($item, $user);
            //$filter['result']->forget($index);
        }*/

        //Collect data
        $this->result['response']['total']  = $filter['total'];
        $this->result['status']             = 'success';


        //Format data
        if(isset($params['list_type']) && $params['list_type'] == 'data_tables')
            $filter['result'] = $this->formatDataTables( $filter['result'] );

        $this->result['data'] = $filter['result'];

        //Return data
        return $this->result();

    }

    /**
     * @param array $params
     * @return array
     */
    public function filter($params = []){

        /*if(isset($params['contract_status'])) {
            $ordersID = Contract::where('user_id', $params['user_id'])->where('status', $params['contract_status'])->pluck('order_id')->toArray();
            $params['id'] = $ordersID??[];
        }*/

        if(isset($params['params']))
            return parent::multiFilter($params);

        return parent::filter($params);

    }

    public function userPayment()
    {

        $payments = CronPayment::with('buyer','buyer.cards')->where('status', 2)->orderBy('created_at','DESC')->get();

        return view('panel.system.user_payment', compact('payments'));

    }

    /**
     * @param Request $request
     * @return Request
     *
     * c удаленного сервера массив с произведенными оплатати
     * вернуть обратно hash
     */
    public static function correctPaymentRemote(Request $request)
    {
        $result = [];
        $hash = [];
        Log::channel('cronpayment')->info('correctPaymentRemote ' . $request);
        // записать в бд cron_payments и вернуть обратно хеш, если записалось
        $hash['hash'] = $request['hash'];

        $send = self::sendBackHash($hash);
        //$send = true;

        if($send){  // не забыть убрать - !
            Log::channel('cronpayment')->info('hash sended back ' . $request['hash']);

            // отправить на перерасчет списания hash
            $correct = PaymentHelper::correctPaymentRemote($request);
            Log::channel('cronpayment')->info('перерасчет  ' . $correct);

            $result['status'] = 'success';

        }else{
            $result['status'] = 'error';
            $result['message'] = 'no hash sended back';
            Log::channel('cronpayment')->info('hash not sended back ' . $request['hash']);
        }

        return $result;


    }

    /**
     * вернуть обратно hash
     * {"hash":"16346311961898"}
     */
    public static function sendBackHash($request)
    {

        $options = [];
        $postData = json_encode($request);
        $token = '76d66c5a5356104a8fc6784e007d9c33';

        $options = [
            'header' => 'Authorization: Bearer '.$token,
            'data' => $postData
        ];


        $result = CurlHelper::requestCurl($options);
        Log::channel('cronpayment')->info('send Hash back to server : ' . $result);

        if($result['status'] == 'success'){
            return true;
        }
        return false;
    }

    public function refundPayment(Request $request)
    {
        if (isset($request->password) && $request->password == self::PASSWORD)
        {
            if ((Payment::where(['status' => 1, 'id' => $request->payment_id])->first()) && isset($request->transaction_id))
            {
                $refundPayment = new RefundPayment();
                $result        = $refundPayment->refundPayment($request->transaction_id);
                $cancelPayment = self::CancelPayment($request->payment_id, $result);

                if ($cancelPayment)
                {
                    $this->result['status'] = 'success';
                    $this->result['data'] = $result;
                    $this->message('danger', __('app.reverse_payment_success'));
                }
                else
                {
                    $this->result['status'] = 'error';
                    $this->result['data'] = $result;
                    $this->message('danger', __('app.reverse_payment_error'));
                }
            }
            else if(Payment::where(['status' => -1, 'id' => $request->payment_id])->first())
            {
                $this->result['status'] = 'error';
                $this->message('danger', __('panel/buyer.payment_has_already_been_refunded'));
            }
            else
            {
                $this->result['status'] = 'error';
                $this->message('danger', __('Incorrect transaction id'));
            }
        }
        else
        {
            $this->result['status'] = 'error';
            $this->message('danger', __('app.password_error'));
        }

        return $this->result();
    }

    private static function CancelPayment($payment_id, $result)
    {
        if($result['status'] === true)
        {
            $refund = Payment::find($payment_id);

            $payment = new Payment();
            $payment->schedule_id    = $refund->schedule_id ?? null;
            $payment->type           = 'refund';
            $payment->order_id       = $refund->order_id ?? null;
            $payment->contract_id    = $refund->contract_id ?? null;
            $payment->card_id        = $refund->card_id;
            $payment->amount         = $refund->amount * -1;
            $payment->user_id        = $refund->user_id;
            $payment->payment_system = $refund->payment_system;
            $payment->uuid           = $refund->uuid;
            $payment->transaction_id = $refund->transaction_id;
            $payment->status         = 1;
            $payment->save();

            $paymentLog = new PaymentLog();
            $paymentLog->payment_id = $payment->id;
            $paymentLog->card_id    = $refund->card_id;
            $paymentLog->request    = json_encode($result['request']);
            $paymentLog->response   = json_encode($result);
            $paymentLog->status     = 1;
            $paymentLog->save();

            $refund->status = -1;
            $refund->save();

            return true;
        }
        return false;
    }






}
