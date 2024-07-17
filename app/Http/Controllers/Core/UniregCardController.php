<?php


namespace App\Http\Controllers\Core;

use Carbon\Carbon;
use App\Helpers\CardHelper;
use App\Helpers\EncryptHelper;
use App\Http\Controllers\Controller;
use App\Models\CardScoring;
use App\Models\CardScoringLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Payment;
use App\Models\PaymentLog;

class UniregCardController extends CoreController {
    /**
     * @OA\Post(
     *      path="/buyer/send-sms-code-uz",
     *      operationId="cards-sms-code-uz",
     *      tags={"Cards"},
     *      summary="Send sms code uz card",
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
     * Отправить карту на генарцию токена через шлюз с отправкой СМС
     * @param Request $request
     * @return mixed
     */

    /**
     * @OA\Post(
     *      path="/buyer/check-sms-code-uz",
     *      operationId="cards-check-sms-code-uz",
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
     *          name="hashedCode[data]",
     *          description="Hashed code (return in method send-sms-code-uz)",
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
    public function terminalAdd(Request $request){
        Log::channel('cards')->info('start unireg.terminal.add');
        $split_info_card = CardHelper::getAdditionalCardInfo($request->card, $request->exp);

        $request_id = 'test_' . time();

        $jRequest = [] ; /* json_encode([
            'jsonrpc' => '2.0',
            'id' => $request_id,
            'method' => 'terminal.add',
            'params' => [
                'merchant' => $request->merchant,
                'terminal' => $request->terminal,
                'type'​ => ​$request->type,
                '​purpose'​ => ​$request->purpose, //'Цель'​,
                ​'oint_code' => ​$request->point_code, // '1000000000'​,
                ​'originator'​ => ​$request->orfdinator, // 'Test Trade '​,
                ​'centre_id' => $request->centre_id // ​'Test eportal'
            ],
        ]); */

        $result = CardHelper::requestUZCard($this->buildAuthorizeConfig(), $jRequest);
        Log::channel('cards')->info(print_r($result, 1));

        if(isset($result['result']['pan'])){
            $this->result['status'] = 'success';
            $this->result['data'] = EncryptHelper::encryptData($result['result']['id']);
        }else{
            $this->result['status'] = 'error';
            $this->message('danger', __('card.uzcard_get_token_error'));
        }

        Log::channel('cards')->info(print_r($this->result, 1));
        Log::channel('cards')->info('end uzcard.cards.new.otp');
        return $this->result();
    }
    /**
     * Верификация полученного токена посредством шлюза и полученного СМС кода
     * @param Request $request
     * @return mixed
     */
    public function checkSmsCodeUz(Request $request){
        Log::channel('cards')->info('start uzcard.cards.new.verify');

        $request_id = 'test_' . time();
        $token = EncryptHelper::decryptData($request->hashedCode['data']);
        $jRequest = json_encode([
            'jsonrpc' => '2.0',
            'id' => $request_id,
            'method' => 'cards.new.verify',
            'params' => [
                'card' => [
                    'token' => $token,
                    'code' => $request->code
                ]
            ],
        ]);

        $result = CardHelper::requestUZCard($this->buildAuthorizeConfig(), $jRequest);

        if(isset($result['result'][0]['pan'])){
            $this->result['data'] = EncryptHelper::encryptData(json_encode($result['result'][0]));
            $this->result['status'] = 'success';
        }else{
            $this->result['status'] = 'error';
            $this->message('danger', __('card.uzcard_get_token_error'));
        }

        Log::channel('cards')->info(print_r($result,1));
        Log::channel('cards')->info('end uzcard.cards.new.verify');
        return $this->result();
    }

    /** возврат денежных средств
     * @param Request $request
     * @return array|false|string
     */
    public function refund(Request $request){
        Log::channel('cards')->info('start uzcard.trans.reverse');
        $refund = Payment::find($request->payment_id);
        $requestId = 'test_' . time();
        $tranId = $request->transaction_id; // refnum

        $jRequest = json_encode([
            'jsonrpc' => '2.0',
            'id' => $requestId,
            'method' => 'trans.reverse',
            'params' => [
                'tranId' => $tranId,
            ],
        ]);

        Log::channel('cards')->info(print_r($jRequest,1));
        $result = CardHelper::requestUZCard($this->buildAuthorizeConfig(), $jRequest);
        Log::channel('cards')->info(print_r($result,1));

        $payment = new Payment;

        $payment->schedule_id = $refund->schedule_id;
        $payment->type = 'refund';
        $payment->order_id = $refund->order_id;
        $payment->contract_id = $refund->contract_id;

        $payment->card_id = $refund->card_id;
        $payment->amount = $refund->amount * -1;
        $payment->user_id = $refund->user_id;
        $payment->payment_system = 'UZCARD';

        $paymentLog = new PaymentLog;

        $paymentLog->request = $jRequest;
        $paymentLog->response = json_encode($result);

        if (isset($result['result'][0]['status']) && $result['result'][0]['status'] == 'ROK') {
            $payment->transaction_id = $result['result'][0]['refNum'];
            $payment->status = 1;
            $paymentLog->status = 1;
            $this->result['status'] = 'success';
            $this->result['data'] = $result;
        } else {
            $payment->status = 0;
            $paymentLog->status = 0;
            $this->result['status'] = 'error';
            $this->message('danger', __('card.uz_payment_error'));
        }
        $payment->save();
        $paymentLog->payment_id = $payment->id;
        $paymentLog->save();

        Log::channel('cards')->info('end uzcard.trans.reverse');
        return $this->result();
    }

    /**  баланс на карте
     * @param Request $request
     * @return array|false|string
     */
    public function balance(Request $request){
        Log::channel('cards')->info('start uzcard.cards.get');
        $token = EncryptHelper::decryptData($request->info_card['token']);
        $requestId = 'test_' . uniqid(rand(),1) ;
        $jRequest = json_encode([
            'jsonrpc' => '2.0',
            'id' => $requestId,
            'method' => 'cards.get',
            'params' => [
                'ids' => [
                    $token
                ],
            ],
        ]);
        Log::channel('cards')->info(print_r($jRequest,1));
        $response = CardHelper::requestUZCard($this->buildAuthorizeConfig(), $jRequest);
        Log::channel('cards')->info(print_r($response,1));
        if(isset($response['result'][0]['pan'])){
            $this->result['status'] = 'success';
            $this->result['data'] = ['balance' => $response['result'][0]['balance']];
        }else{
            $this->result['status'] = 'error';
            //$this->message('danger', __('card.uzcard_balance_error'));
            $this->message('danger', $response['error']['message']);
        }
        Log::channel('cards')->info('end uzcard.cards.get');
        return $this->result();
    }

    /**
     * Снятие заданной суммы по токену с карты
     * @param Request $request
     */
    public function payment(Request $request){
        Log::channel('cards')->info('start uzcard.trans.pay');
        $uid = $request->buyer_id;
        $sum = $request->sum;
        $config = $this->buildAuthorizeConfig();
        $token = EncryptHelper::decryptData($request->info_card['token']);
        $requestId = 'test_' . uniqid(rand(),1) . $uid;
        $sum *= 100;
        $balance = $this->balance($request);

        if ($balance['status'] == 'success') {
            if ($balance['data']['balance'] >= $sum) {
                //echo 'безакцептное списание ';
                $jRequest = json_encode([
                    'jsonrpc' => '2.0',
                    'id' => $requestId,
                    'method' => 'trans.pay',
                    'params' => [
                        'tran' => [
                            'cardId' => $token,
                            'amount' => $sum, // сумма в тийнах
                            'merchantId' => $config['merchant_id'], //Yii::$app->user->id, //identity->vendor_id,
                            'ext' => $requestId,
                            'stan' => rand(),//'141220',
                            'refNum' => rand(),
                            'terminalId' => $config['terminal_id'],
                            'port' => $config['port'],
                        ]
                    ],
                ]);

                Log::channel('cards')->info(print_r($jRequest, 1));
                $result = CardHelper::requestUZCard($config, $jRequest);
                Log::channel('cards')->info(print_r($result, 1));
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
                $payment->user_id = $uid;
                $payment->payment_system = 'UZCARD';

                $paymentLog = new PaymentLog;
                $paymentLog->request = $jRequest;
                $paymentLog->response = json_encode($result);
                if ($result['result']['status'] == 'OK') {
                    $payment->transaction_id = $result['result']['refNum'];
                    $payment->status = 1;
                    $paymentLog->status = 1;
                } else {
                    $payment->status = 0;
                    $paymentLog->status = 0;
                }
                $payment->save();
                $paymentLog->payment_id = $payment->id;
                $paymentLog->save();

                if ($result['result']['status'] == 'OK') {
                    $this->result['status'] = 'success';
                    $this->result['data'] = $result;
                } else {
                    $this->result['status'] = 'error';
                    $this->message('danger', __('card.uz_payment_error'));
                }
            } else {
                $this->result['status'] = 'error';
                $this->message('danger', __('card.uz_not_money_payment_error'));
            }
        } else {
            $this->result = $balance;
        }
        Log::channel('cards')->info('end uzcard.trans.pay');
        return $this->result();
    }

    /**
     * Скоринг карты на соответствие заданной суммы
     * @param Request $request
     * @return mixed
     */
    public function scoring(Request $request){
        Log::channel('cards')->info('start uzcard.cards.scoring');
        $sum = $request->sum;
        $requestId = 'test_' . $request->buyer_id;
        $cardToken = EncryptHelper::decryptData($request->info_card['token']);
        if($cardToken) {
            $scoringLog = new CardScoringLog();
            $scoring = new CardScoring();
            $scoring->user_id = $request->buyer_id;
            $scoring->user_card_id = $request->info_card['id'];
            $scoring->period_start = Carbon::parse($request->date_start)->format('Y-m-d H:i:s');
            $scoring->period_end = Carbon::parse($request->date_end)->format('Y-m-d H:i:s');
            $scoring->status = 1;
            $scoring->save();
            $sum *= 100;
            $request = json_encode([
                'jsonrpc' => '2.0',
                'id' => $requestId,
                'method' => 'scoring.create.amount',
                'params' => [
                    'scoring' => [
                        'cardId' => $cardToken, // Токен карты
                        'bdate' => Carbon::parse($request->date_start)->format('dmY'), // '01112019', // Дата начала (DDMMYY)
                        'edate' => Carbon::parse($request->date_end)->format('dmY'), // '01112020', // Дата конца (DDMMYY)
                        'templateId ' => '1', // ID шаблона скоринга
                        'amount' => $sum, // из сум в тийин
                    ]

                ],

            ]);

            $result = CardHelper::requestUZCard($this->buildAuthorizeConfig(), $request);
            Log::channel('cards')->info(print_r($result, 1));
            if(isset($result['result'])) {
                $scoringId = $result['result']['id']; // идентификатор скоринга
                $request = json_encode([
                    'jsonrpc' => '2.0',
                    'id' => $requestId,
                    'method' => 'scoring.get.month',
                    'params' => [
                        'id' => $scoringId,
                    ],
                ]);
                $result = CardHelper::requestUZCard($this->buildAuthorizeConfig(), $request);
                Log::channel('cards')->info(print_r($result, 1));
                if (isset($result['result'])) {
                    $this->result['status'] = 'success';
                    $this->result['data'] = $result['result'];
                    $scoringLog->status = 1;
                }else{
                    $this->result['status'] = 'error';
                    $this->result['response'] = $result;
                    $scoringLog->status = 0;
                }
            }else{
                $this->result['status'] = 'error';
                $this->result['response'] = $result;
                $scoringLog->status = 0;
            }
            $scoringLog->card_scoring_id = $scoring->id;
            //$scoringLog->card_hash = md5($request->info_card['token']); // токен не номер карты
            $scoringLog->request = $request;
            $scoringLog->response = json_encode($result);
            $scoringLog->save();
        }
        Log::channel('cards')->info('end uzcard.cards.scoring');
        return $this->result();
    }

    /**
     * Получить авторизационныя данные для шлюза UniversalBank
     * @return array
     */
    private function buildAuthorizeConfig(): array{
        // У Universal нет тестового режима
        /* if (config('app.debug')) {
            $config = [
                'url' => config('test.unireg_url'),
                'login' => config('test.unireg_login'),
                'password' => config('test.unireg_password'),
                'terminal_id' => config('test.unireg_terminal_id'),
                'merchant_id' => config('test.unireg_merchant_id'),
                'port' => config('test.unireg_port'),
            ];
        } else {
            $config = [
                'url' => config('test.unireg_url'),
                'login' => config('test.unireg_login'),
                'password' => config('test.unireg_password'),
                'terminal_id' => config('test.unireg_terminal_id'),
                'merchant_id' => config('test.unireg_merchant_id'),
                'port' => config('test.uz_port'),
            ];
        } */
        $config = [
            'url' => config('test.unireg_url'),
            'login' => config('test.unireg_login'),
            'password' => config('test.unireg_password'),
            'terminal_id' => config('test.c'),
            'merchant_id' => config('test.unireg_merchant_id'),
            'port' => config('test.unireg_port'),
        ];
        return $config;
    }
}
