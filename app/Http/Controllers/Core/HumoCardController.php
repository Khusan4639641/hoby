<?php


namespace App\Http\Controllers\Core;

use App\Helpers\UniversalHelper;
use App\Models\CardScoring;
use App\Models\CardScoringLog;
use Carbon\Carbon;
use App\Helpers\EncryptHelper;
use App\Http\Controllers\Controller;
use App\Helpers\CardHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Payment;
use App\Models\PaymentLog;
use function Couchbase\defaultDecoder;

class HumoCardController extends CoreController {

    private $client;

    /**
     * @OA\Post(
     *      path="/buyer/send-sms-code-humo",
     *      operationId="cards-sms-code-humo",
     *      tags={"Cards"},
     *      summary="Send sms code humo card",
     *      description="Return json",
     *      security={{"api_token_security":{}}},
     *      @OA\Parameter(
     *          name="phone",
     *          description="Phone",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="card",
     *          description="Number card",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="exp",
     *          description="Expiration card",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="12/25"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       ),
     *      @OA\Response(
     *       response=201,
     *       description="Success",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     *     )
     */
    /**
     * @param Request $request
     * @return array
     */
    public function sendSmsCodeHumo(Request $request){
        Log::channel('cards')->info('start humo.verify sms');
        $config = $this->buildAuthorizeConfig();
        $splitCardInfo = CardHelper::getAdditionalCardInfo($request->card, $request->exp);
        $this->client = CardHelper::connectedHumoCard($config);
        $resultCard = CardHelper::requestHumoCard($this->client, $splitCardInfo);
        Log::channel('cards')->info(print_r($resultCard,1));
        if($resultCard['status'] == 'success') {
            $this->client = CardHelper::connectedHumoCard($config, 'phone', false);
            $resultMobile = CardHelper::requestHumoMobile($this->client, $resultCard['data']);
            $request->merge(['phone' => $resultMobile['data']['phone']]);
            $answer = ['hash' => $this->sendSmsCode($request),
                'phone'=>$resultMobile['data']['phone'],
                'name'=>$resultMobile['data']['name']];
        }else{
            $answer['status'] = 'error';
        }
        Log::channel('cards')->info('end humo.verify');
        return $answer;
    }

    /**
     * @OA\Post(
     *      path="/buyer/check-sms-code-humo",
     *      operationId="cards-check-sms-code-humo",
     *      tags={"Cards"},
     *      summary="Send sms code humo card",
     *      description="Return json",
     *      security={{"api_token_security":{}}},
     *      @OA\Parameter(
     *          name="code",
     *          description="Code sms",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="hashedCode",
     *          description="Hashed code (return in method send-sms-code-humo)",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="name",
     *          description="Name (return in method send-sms-code-humo)",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="phone",
     *          description="Phone (return in method send-sms-code-humo)",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       ),
     *      @OA\Response(
     *       response=201,
     *       description="Success",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     *     )
     */
    /**
     * Проверка смс код для привязки карты к пользвателю
     * @param Request $request
     * @return false|string
     */
    public function checkSmsCodeHumo(Request $request){
        $encSms = $sms = $this->checkSmsCode($request);
        if($encSms['status'] == 'success'){
            $this->result['status'] = 'success';
            $this->result['data'] = EncryptHelper::encryptData(json_encode($request->all()));
        }else{
            $this->result['status'] = 'error';
        }
        return $this->result();
    }

    /**
     * @param Request $request
     */
    public function refund(Request $request){
        $refund = Payment::find($request->payment_id);
        $config = $this->buildAuthorizeConfig();
        $transactionId = $request->transaction_id;
        $this->client = CardHelper::connectedHumoCard($config, 'discard', false);
        $splitCardInfo = CardHelper::getAdditionalCardInfo(EncryptHelper::decryptData($request->info_card['card_number']), EncryptHelper::decryptData($request->info_card['card_valid_date']));
        $response = CardHelper::requestHumoRefund($config, $this->client, $splitCardInfo, $transactionId);

        $payment = new Payment;
        $payment->schedule_id = $refund->schedule_id;
        $payment->type = 'refund';
        $payment->order_id = $refund->order_id;
        $payment->contract_id = $refund->contract_id;

        $payment->card_id = $refund->card_id;
        $payment->amount = $refund->amount * -1;
        $payment->user_id = $refund->buyer_id;
        $payment->payment_system = 'HUMO';

        $paymentLog = new PaymentLog;
        $paymentLog->request = $response['response']['request'];
        $paymentLog->response = json_encode($response);
        if (isset($response['response']['data']['details']->item)) {
            $payment->transaction_id = $response['response']['data']['paymentID'];
            $payment->status = 1;
            $paymentLog->status = 1;
        } else {
            $payment->status = 0;
            $paymentLog->status = 0;
        }
        $payment->save();
        $paymentLog->payment_id = $payment->id;
        $paymentLog->save();

        if (isset($response['response']['data']['details']->item)) {
            $this->result['status'] = 'success';
            $this->result['data'] = $response['response']['data'];
        } else {
            $this->result['status'] = 'error';
            $this->message('danger', __('card.humo_payment_error'));
        }

    }
    /**
     * @param Request $request
     * @return mixed
     */
    public function balance(Request $request){
        $config = $this->buildAuthorizeConfig();
        $this->client = CardHelper::connectedHumoCard($config, 'balance', false);
        $splitCardInfo = CardHelper::getAdditionalCardInfo(EncryptHelper::decryptData($request->info_card['card_number']), EncryptHelper::decryptData($request->info_card['card_valid_date']));
        $response = CardHelper::requestHumoBalance($this->client, $splitCardInfo);
        if(isset($response->availableAmount)){
            $this->result['status'] = 'success';
            $this->result['data'] = ['balance' => $response->availableAmount];
        }else{
            $this->result['status'] = 'error';
            $this->message('danger', __('card.humo_balance_error'));
        }
        return $this->result();
    }



    /**
     * @param Request $request
     */
    public function payment(Request $request){
        $sum = $request->sum;
        $sum *= 100;
        $balance = $this->balance($request);
        if($balance >= $sum) {
            $config = $this->buildAuthorizeConfig();
            $this->client = CardHelper::connectedHumoCard($config, 'discard', false);
            $splitCardInfo = CardHelper::getAdditionalCardInfo(EncryptHelper::decryptData($request->info_card['card_number']), EncryptHelper::decryptData($request->info_card['card_valid_date']));
            $response = CardHelper::requestHumoPayment($config, $this->client, $splitCardInfo, $sum);

            $payment = new Payment;
            if ($request->has('schedule_id'))
                $payment->schedule_id = $request->schedule_id;
            if ($request->has('type'))
                $payment->type = $request->type;
            if ($request->has('order_id'))
                $payment->order_id = $request->order_id;
            if ($request->has('contract_id'))
                $payment->contract_id = $request->contract_id;

            $payment->card_id = $request->card_id;
            $payment->amount = $sum / 100;
            $payment->user_id = $request->buyer_id;
            $payment->payment_system = 'HUMO';

            $paymentLog = new PaymentLog;
            $paymentLog->request = $response['response']['request'];
            $paymentLog->response = json_encode($response);
            if (isset($response['response']['data']['details']->item)) {
                $payment->transaction_id = $response['response']['data']['paymentID'];
                $payment->status = 1;
                $paymentLog->status = 1;
            } else {
                $payment->status = 0;
                $paymentLog->status = 0;
            }
            $payment->save();
            $paymentLog->payment_id = $payment->id;
            $paymentLog->save();

            if (isset($response['response']['data']['details']->item)) {
                $this->result['status'] = 'success';
                $this->result['data'] = $response['response']['data'];
            } else {
                $this->result['status'] = 'error';
                $this->message('danger', __('card.humo_payment_error'));
            }
        }else{
            $this->result['status'] = 'error';
            $this->message('danger', __('card.humo_not_money_payment_error'));
        }
        return $this->result();
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function scoring(Request $request){

        Log::info('HumoCardController -> scoring');

        $sum = $request->sum; $sum *= 100;
        $scoringLog = new CardScoringLog();
        $scoring = new CardScoring();
        $scoring->user_id = $request->buyer_id;
        $scoring->user_card_id = $request->info_card['id'];
        $scoring->period_start = Carbon::parse($request->date_start)->format('Y-m-d H:i:s');
        $scoring->period_end = Carbon::parse($request->date_end)->format('Y-m-d H:i:s');
        $scoring->status = 1;
        $scoring->save();

        $aScoringInfo = [
            'date_start' => Carbon::parse($request->date_start)->format('Y-m-d\TH:i:s'),
            'date_end' => Carbon::parse($request->date_end)->format('Y-m-d\TH:i:s'),
            'sum' => $sum
        ];
        $config = $this->buildAuthorizeConfig();
        $splitCardInfo = CardHelper::getAdditionalCardInfo(EncryptHelper::decryptData($request->info_card['card_number']), EncryptHelper::decryptData($request->info_card['card_valid_date']));
        $this->client = CardHelper::connectedHumoCard($config);
        $resultScoring = CardHelper::requestHumoScoring($this->client, $splitCardInfo, $aScoringInfo);

        if(sizeof($resultScoring['data'])>0){
            $this->result['status'] = 'success';
            $this->result['data'] = $resultScoring['data'];
            $scoringLog->status = 1;
        }else{
            $scoringLog->status = 0;
            $this->result['status'] = 'error';
            $this->message('danger', __('card.scoring_error'));
        }
        /* $scoring = UniversalHelper::scoringScore($result['result']); // $resultScoring['response']['response']['result']
        $scoringLog->status = isset($scoring['ball']) && $scoring['ball']>0 ? 1: 0; // $cardScoring->status;
        $scoringLog->scoring = isset($scoring['ball']) ? $scoring['ball'] : 0;
        $scoringLog->scoring_count = 1;
        */

        $scoringLog->card_scoring_id = $scoring->id;
        $scoringLog->card_hash = md5($request->info_card['card_number']);
        $scoringLog->request = $resultScoring['response']['request'];
        $scoringLog->response = $resultScoring['response']['response'];
        $scoringLog->save();
        return $this->result();
    }

    /**
     * Получить авторизационныя данные для шлюза
     * @return array
     */
    private function buildAuthorizeConfig(): array{
        if (config('app.debug')) {
            $config = [

            ];
        } else {
            $config = [

            ];
        }
        return $config;
    }
}
