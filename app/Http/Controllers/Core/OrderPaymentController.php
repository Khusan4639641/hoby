<?php

namespace App\Http\Controllers\Core;

use App\Helpers\PaycoinHelper;
use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\ContractPaymentsSchedule;
use App\Models\Order;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderPaymentController extends OrderController{
    //
    /**
     * @param Request $request
     * @return array|false|mixed|string
     * @throws \Exception
     */
    public function payment(Request $request){
        $buyer = Buyer::find(Auth::id());
        $params = $request->all();
        $params['user_id'] = $buyer->id;
        $result = ['status' => 'error'];
        if($request->type == 'credit'){
            $zcoin = config('test.zcoin');
            $result = $this->add($params);
            if($result['status'] == 'success'){
                //TODO: Раскоментить если вдруг будут начислятся бонусы с покупки в рассрочку
                //$buyer->settings->zcoin += (float)$zcoin['bonus'];
                //$buyer->settings->save();
            }
        }elseif($request->type == 'direct') {
            $result = $this->paymentCardOrAccount($params);
        }

        if($result['status'] == 'success'){
            $cartRequest = request();
            $cart = new CartController();
            $cartRequest->merge(['cart_id'=> $params['cart'][0]['cart_id']]);
            $cart->clear($cartRequest);
        }

        return $result;
    }


    /**  // старый, не используется??
     * @param Request $request
     * @return array|false|string
     */
    public function repayment(Request $request){
        if($request->has('payment')){
            $order = Order::find($request->payment['order_id']);
            $schedules = ContractPaymentsSchedule::where('contract_id', $order->contract->id)->where('status', 0)->orderBy('payment_date', 'asc')->get();
            if(sizeof($schedules)>0 && isset($request->payment['total'])) {
                $total = $request->payment['total'];

                    if($total > $schedules[0]->contract->balance)
                        $total = $schedules[0]->contract->balance;

                        $sum = $schedules[0]->buyer->settings->personal_account;
                    if ($request->payment['type'] == 'account') {
                        $this->result = $this->repaymentAction($schedules, $total, $sum, 'ACCOUNT');

                    } elseif ($request->payment['type'] == 'card') {
                        $cardController = new CardController();
                        $pay = new Request();
                        $pay->merge(['buyer_id' => $schedules[0]->buyer->id,
                            'order_id' => $schedules[0]->contract->order_id,
                            'contract_id' => $schedules[0]->contract->id,
                            'payment_type' => 'auto',
                            'card_id' => $request->payment['card_id'],
                            'sum' => $total]);
                        $response = $cardController->payment($pay);  // тут мы с карты сняли total(задано клиентом)
                        // расчеты
                        if ($response['status'] == 'success') {
                            $this->result = $this->repaymentAction($schedules, $total, $sum,$system = 'CARD');  // sum?
                        }else{
                            $this->result['status'] = 'error';
                        }

                    }
            }else{
                $this->result['status'] = 'error';
            }
        }
        return $this->result();
    }



    /** досрочное погашение лицевой счет??
     * @param $schedules
     * @param $total
     * @param $sum
     * @param string $system
     * @return array|false|string
     */
    private function repaymentAction($schedules, $total, $sum, $system = 'ACCOUNT'){
        $account = $personalAccount = $sum;  //лицевой счет
        $fixedTotal = $total;  // сумма которую сняли с карты
        $fixedTotalAccount = 0;  // сумма которую сняли c ЛС
        if($personalAccount >= $total || $system == 'CARD') {  // если на лс хватает денег, или оплата с карты

            foreach ($schedules as $schedule) {

                if($total > 0) {
                    $currentPayment = $balance = $schedule->balance;
                    $fullPayment = false;
                    if ($total >= $balance) {
                        $fullPayment = true;
                        $amount = $balance; // списали
                        $account -= $balance;
                        $total -= $balance;
                        $balance = 0;
                    } else {
                        $amount = $total; // списали
                        $account -= $total;
                        $balance -= $total;
                        $currentPayment = $total;
                        $total = 0;
                    }

                    // если оплата с карты, и месяц к оплате или в просрочке, просто кладем деньги на ЛС, месяц не закрываем, спишется с крона
                    $payment_date = strtotime($schedule->payment_date);
                    $now = strtotime(Carbon::now()->format('Y-m-d 23:59:59'));
                    if($payment_date <= $now ){
                        if($system == 'CARD'){
                            $schedule->buyer->settings->personal_account += $amount;
                            $schedule->buyer->settings->save();
                        }
                        continue;
                    }
                    $fixedTotalAccount += $amount;

                    $schedule->balance = $balance;

                    $currentBalance = $schedule->contract->balance - $currentPayment;
                    $schedule->contract->balance = $currentBalance;
                    if($currentBalance == 0)
                        $schedule->contract->status = 9;

                    $schedule->contract->save();

                    if ($fullPayment) {
                        $schedule->buyer->settings->balance += $schedule->price;
                        $schedule->buyer->settings->save();
                        $schedule->status = 1;
                        $schedule->paid_at = time();
                    }

                    $schedule->save();
                    if($system == 'ACCOUNT'){
                        $schedule->buyer->settings->personal_account -= $amount;
                        $schedule->buyer->settings->save();
                        $payment = new Payment;
                        $payment->type = 'auto';
                        $payment->order_id = $schedules[0]->contract->order_id;
                        $payment->contract_id = $schedules[0]->contract->id;
                        $payment->amount = $amount; // списали
                        $payment->user_id = $schedules[0]->buyer->id;
                        $payment->payment_system = $system;
                        $payment->status = 1;
                        $payment->save();

                        PaycoinHelper::addBall($schedule);
                    }
                }
            }

            $sum = $system == 'CARD' ? $fixedTotal : $fixedTotalAccount;
            $this->result['status'] = 'success';
            $this->message('success', __('order.txt_repayment_success', ['summ' => $sum, 'currency' => __('app.currency')]));
        }else{
            $this->result['status'] = 'error';
            $this->message('error', __('order.txt_repayment_error'));
        }

        return $this->result();
    }

    public function delaypayment(Request $request){
        if($request->has('payment')){
            $schedules = Buyer::find(Auth::id())->debts;
            if(sizeof($schedules)>0 && isset($request->payment['total'])) {
                $total = $request->payment['total'];
                $debt = 0;
                foreach($schedules as $item)
                    $debt += $item->balance;
                if($total > $debt)
                    $total = $debt;
                $sum = $schedules[0]->buyer->settings->personal_account;
                if ($request->payment['type'] == 'account') {
                    $this->result = $this->repaymentAction($schedules, $total, $sum, 'ACCOUNT');
                } elseif ($request->payment['type'] == 'card') {
                    $cardController = new CardController();
                    $pay = new Request();
                    $pay->merge(['buyer_id' => $schedules[0]->buyer->id,
                        'order_id' => '',
                        'contract_id' => '',
                        'type' => 'user',
                        'card_id' => $request->payment['card_id'],
                        'sum' => $total]);
                    $response = $cardController->payment($pay);
                    if ($response['status'] == 'success') {
                        $this->result = $this->repaymentAction($schedules, $total, $sum, 'CARD');
                    }
                }
            }else{
                $this->result['status'] = 'error';
            }
        }
        return $this->result();
    }

    /**
     * @param array $params
     * @return array|false|mixed|string
     * @throws \Exception
     */
    private function paymentCardOrAccount($params = []){
        $recalculate = $this->calculate($params);
        $result = ['status' => 'error'];
        if($recalculate['status'] == 'success') {
            if ($params['payment']['type'] == 'card')
                $result = $this->paymentCard($recalculate, $params);
            elseif ($params['payment']['type'] == 'account')
                $result = $this->paymentAccount($recalculate, $params);
        }else $result = $recalculate;
        return $result;
    }

    /**
     * @param $calc
     * @param $params
     * @return array|false|mixed|string
     * @throws \Exception
     */
    private function paymentCard($calc, $params){
        $request = new Request();
        $total = $calc['data']['price']['total'];
        $request->merge(['card_id'=>$params['payment']['card_id'], 'buyer_id' => Auth::id()]);
        $cardController = new CardController();
        $result = $cardController->balance($request);
        if($result['status'] == 'success') {
            $availableBalance = $result['data']['balance'] / 100;
            if ($availableBalance >= $total) {
                $orders = $this->add($params);
                foreach($orders['data']['orders'] as $vendorId => $order) {
                    $request = new Request();
                    $request->merge(['card_id' => $params['payment']['card_id'], 'buyer_id' => Auth::id(),
                                    'sum' => $order['price']['total'], 'type' => 'user', 'order_id' => $order['id']]);
                    $cardController = new CardController();
                    $result = $cardController->payment($request);
                    if($result['status'] == 'error')
                        $this->delete($order['id']);
                }
                $this->result['status'] = 'success';
                $this->message('success',__('frontend/order.congratulation'));
            }else{
                $this->result['status'] = 'error';
                $this->message('danger',__('frontend/order.payment_few_card'));
            }
        }
        return $result;
    }

    /**
     * @param $calc
     * @param $params
     * @return array|false|string
     */
    private function paymentAccount($calc, $params){
        $buyer = Buyer::find(Auth::id());
        $total = $calc['data']['price']['total'];
        if($buyer->settings->personal_account >= $total){
            $orders = $this->add($params);

            foreach($orders['data']['orders'] as $vendorId => $order) {
                $buyer->settings->personal_account -= $order['price']['total'];
                $buyer->settings->save();

                $payment = new Payment;
                $payment->schedule_id = null;
                $payment->type = 'user';
                $payment->order_id = $order['id'];
                $payment->contract_id = null;

                $payment->amount = $order['price']['total'];
                $payment->user_id = $buyer->id;
                $payment->payment_system = 'ACCOUNT';
                $payment->status = 1;
                $payment->save();
            }
            $this->result['status'] = 'success';
            $this->message('success',__('frontend/order.congratulation'));
        }else{
            $this->result['status'] = 'error';
            $this->message('danger',__('frontend/order.payment_few_personal_account'));
        }
        return $this->result();
    }
}
