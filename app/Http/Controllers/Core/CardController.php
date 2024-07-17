<?php


namespace App\Http\Controllers\Core;

use App\Classes\CURL\Test\GetCardInfo;
use App\Classes\Scoring\LastScoringLog;
use App\Helpers\CardHelper;
use App\Helpers\EncryptHelper;
use App\Helpers\SmsHelper;
use App\Helpers\UniversalHelper;
use App\Http\Requests\CardAddRequest;
use App\Http\Requests\ScoringUniversalRequest;
use App\Http\Resources\GetCardInfoResource;
use App\Models\Buyer;
use App\Models\BuyerSetting;
use App\Models\Card;
use App\Models\Card as Model;
use App\Models\CardPnfl;
use App\Models\CardPnflContract;
use App\Models\CardScoring;
use App\Models\CardScoringLog;
use App\Models\CronPayment;
use App\Models\KycHistory;
use App\Models\Payment;
use App\Models\User;
use App\Services\AddCardInfoHumoUzcard;
use App\Services\API\V3\BaseService;
use App\Services\testCardService;
use App\Services\API\V3\UserPayService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;


/**
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class CardController extends CoreController
{


    private $validatorRules = [
        'card_number' => ['required', 'string', 'max:255'],
        'card_valid_date' => ['required', 'string', 'max:255'],
    ];

    /**
     * Controller constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->model = app(Model::class);
    }

    /**
     * Подтверждение владения картой для ее дальннейшего прикрепления к пользователю
     * @param Request $request
     * @return bool
     */
    public function accept(Request &$request)
    {

        if (!$request->has('verify') || is_null($request->verify)) return true; // ??? 22.04.2021 если поле не задано, например при регистарции пользователя

        $check = false;

        switch (CardHelper::checkTypeCard($request->card_number)['type']) {
            case 1:
                if ($request->has('verify')) {
                    $verifyData = json_decode(EncryptHelper::decryptData($request->verify), true);
                    $request->merge(['card_number' => $verifyData['pan'] ?? '',
                        'token' => $verifyData['id'] ?? '',
                        'phone' => $verifyData['phone'] ?? '',
                        'card_name' => $verifyData['fullName'] ?? ''
                    ]);
                    $check = true;
                }
                break;
            case 2:
                if ($request->has('verify')) {
                    $verifyData = json_decode(EncryptHelper::decryptData($request->verify), true);
                    $request->merge(['phone' => $verifyData['phone'] ?? '',
                        'card_name' => $verifyData['name'] ?? ''
                    ]);
                    $check = true;
                }
                break;
        }
        return $check;
    }


    /**
     * @OA\Post(
     *      path="/buyer/card/add",
     *      operationId="card-add",
     *      tags={"Cards"},
     *      summary="Add card after verification (after method send sms and check sms)",
     *      description="Return json",
     *      security={{"api_token_security":{}}},
     *      @OA\Parameter(
     *          name="card_number",
     *          description="Card number",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="card_valid_date",
     *          description="Expitration date",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="buyer_id",
     *          description="Buyer id",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *
     *      @OA\Parameter(
     *          name="verify",
     *          description="Verify data (return in method check-sms-code-humo or check-sms-code-uz)",
     *          required=true,
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
     * Добавление основной карты к пользователю для скоринга
     * @param Request $request
     * @return mixed
     */
    public function add(Request $request)
    {
        Log::channel('cards')->info('CardController.add buyer.card.add | scoring | KATM');

        Log::channel('cards')->info($request);

        $validator = $this->validator($request->all(), $this->validatorRules);

        if ($validator->fails()) {
            $this->result['status'] = 'error';
            $this->result['response']['errors'] = $validator->errors();
        } else {

            $hashedCardNumber = md5($request->card_number);
            Log::channel('cards')->info('hash: ' . $hashedCardNumber);

            if ($this->accept($request)) {

                $cardNum = str_replace(' ', '', $request->card_number);

                Log::channel('cards')->info('card_num: ' . $cardNum);
                // корректировка карты, удаление пробелов
                $cardInfo = CardHelper::getAdditionalCardInfo($cardNum, $request->card_valid_date);
                Log::channel('cards')->info($cardInfo);

                $hashedCardNumber = md5($cardInfo['card']);

                    Log::channel('cards')->info('add card: ' . $cardInfo['card']);

                    if (Model::where([['guid', '=', $hashedCardNumber], ['user_id', '=', $request->buyer_id ?? Auth::user()->id]])->first())
                    {
                        $this->result['status'] = 'error';
                        $this->result['info'] = 'card_already_added';
                        $this->message('danger', __('auth.card_already_added'));
                        return $this->result();
                    }

                        $request->merge([
                            'card' => $cardInfo['card'],
                            'exp'  => $request->card_valid_date // month/year (превращаем в year/month в самом методе getCardPhone)
                        ]);

                        $cardPhoneRequest = UniversalController::getCardPhone($request);

                        if(isset($cardPhoneRequest['result']) && !isset($cardPhoneRequest['result']['phone']))
                        {
                            $this->result['status'] = 'error';
                            $this->message('danger', __('billing/buyer.card_sms_off'));
                            return $this->result;
                        }
                        if(!isset($cardPhoneRequest['result']) && !isset($cardPhoneRequest['result']['phone']))
                        {
                            $this->result['status'] = 'error';
                            if(isset($cardPhoneRequest['data']) && isset($cardPhoneRequest['data']['message']))
                            {
                                $this->message('danger', __("billing/buyer." . formatToLangVariable($cardPhoneRequest['data']['message'])));
                            }
                            else
                            {
                                $this->message('danger', __('billing/buyer.internal_server_error'));
                            }
                            return $this->result;
                        }

                        $buyerCard = new Model();
                        $buyerCard->user_id         = $request->buyer_id ?? Auth::user()->id; // 04.05 - если не передан buyer_id , значит пользователь сам добавляет карту
                        $buyerCard->card_number     = EncryptHelper::encryptData($cardInfo['card']);
                        $buyerCard->card_valid_date = EncryptHelper::encryptData($cardInfo['exp']);
                        $buyerCard->card_name       = EncryptHelper::encryptData($request->card_name);
                        $buyerCard->phone           = $cardPhoneRequest['result']['phone'];
                        $buyerCard->sms_code        = $request->code;
                        $buyerCard->type            = EncryptHelper::encryptData(CardHelper::checkTypeCard($cardInfo['card'])['name']);
                        $buyerCard->guid            = $hashedCardNumber;
                        $buyerCard->card_number_prefix = substr($cardInfo['card'], 0, 8);

                        if (!$buyerCard->save())
                        {
                            $this->result['status'] = 'error';
                            $this->result['info'] = 'service_unavailable';
                            $this->message('danger', __('auth.service_unavailable'));
                            return $this->result();
                        }


                if ($request->has('token')) {
                    $buyerCard->token = EncryptHelper::encryptData($request->token);
                }

                // здесь нужно получить скоринг по карте и записать его в бд
                // если скоринг не проводился

                Log::channel('cards')->info('buyersetting not found');
                Log::channel('cards')->info($request->buyer_id . '==' . Auth::user()->id);

                if (!is_null($request->buyer_id) && $request->buyer_id != Auth::user()->id) {
                    $user = Buyer::find($request->buyer_id); // если продавец-vendor регистрирует покупателя
                } else {
                    $user = Auth::user(); // если покупатель сам регистрируется
                }

                $day = date('d', time());

                if ($day < 25) {
                    $months = ' -6 month'; // текущий месяц + еще 6 прошедших
                } else {
                    $months = ' -5 month'; // текущий месяц + еще 5 прошедших
                }

                $to = date('Ym25', time());
                $from = date('Ym01', strtotime($to . $months));

                $request->merge([
                    'info_card' => [
                        'card_number' => $cardInfo['card'],
                        'card_valid_date' => $cardInfo['exp']
                    ],
                    'start_date' => $from,
                    'end_date' => $to
                ]);

                // true - пока для всех запросов на uzcard и humo
                $scoring = UniversalHelper::getScoring($request);  //->card_number, $request->card_valid_date, $from, $to, true);
//                    $scoring = UniversalHelper::getScoringV2($request);  // новая обработка скоринга 02.06.2022

                //Log::channel('cards')->info('scoring for ' . $buyerCard->user_id . ' ' . $cardInfo['card']);
                Log::channel('cards')->info('OUT scoring status: ' . $scoring['status']);

                if ($scoring['status'] == 'success') {

                    Log::channel('cards')->info('scoring result ' . $buyerCard->user_id . ' ' . $cardInfo['card']);
                    Log::channel('cards')->info($scoring['scoring']);

                    $scoringResult = $scoring['scoring']['scoring']; // isset($scoring['scoring']) ? $scoring['scoring'] : UniversalHelper::scoringScore($scoring['response']['result']);
                    $scoring_ball = $scoring['scoring']['ball'];

                    Log::channel('cards')->info('scoring for ' . $buyerCard->user_id . ' ' . $cardInfo['card']);
                    Log::channel('cards')->info('scoring: ' . $scoringResult . ' ball: ' . $scoring_ball);

                    // если скоринг не пройден не вносить информацию в БД
                    if ($scoringResult > 0) {

                        // сохраняем скоринг
                        if (!$cardScoring = CardScoring::where('user_id', $user->id)->first()) $cardScoring = new CardScoring();
                        $cardScoring->user_id = $user->id;
                        $cardScoring->user_card_id = $buyerCard->id;
                        $cardScoring->period_start = $from;
                        $cardScoring->period_end = $to;
                        $cardScoring->status = $scoring['response']['status'];

                        if ($cardScoring->save()) {
                            Log::channel('cards')->info($cardScoring);
                            Log::channel('cards')->info('save cardScoring OK');
                        } else {
                            Log::channel('cards')->info('return status error not_save_card_scoring ' . $buyerCard->user_id . ' ' . $cardInfo['card']);
                            $this->result['status'] = 'error';
                            $this->result['info'] = 'error_save_scoring';
                            return $this->result();
                        }

                        // шифруем номер
                        $result = json_decode($scoring['request'], true);
                        $card_encrypt = EncryptHelper::encryptData($result['params']['card_number']);
                        $result['params']['card_number'] = $card_encrypt;

                        if (!$cardScoringLog = CardScoringLog::where('user_id', $user->id)->first()) $cardScoringLog = new CardScoringLog();
                        $cardScoringLog->card_scoring_id = $cardScoring->id;
                        $cardScoringLog->user_id = $user->id;
                        $cardScoringLog->status = $scoringResult > 0 ? 1 : 0; // $cardScoring->status;
                        $cardScoringLog->ball = $scoring_ball;
                        $cardScoringLog->scoring = $scoringResult;
                        $cardScoringLog->scoring_count = 1;
                        $cardScoringLog->card_hash = md5($cardInfo['card']);
                        $cardScoringLog->request = json_encode($result, JSON_UNESCAPED_UNICODE); // $scoring['request'];
                        $cardScoringLog->response = json_encode($scoring['response'], JSON_UNESCAPED_UNICODE);

                        if ($cardScoringLog->save()) {
                            Log::channel('cards')->info('scoring after ---------------------------------');
                            Log::channel('cards')->info('save cardScoringLog OK csl.id:' . $cardScoringLog->id);
                        } else {
                            Log::channel('cards')->info('return status error: error_save_card_scoring_log');
                            $this->result['status'] = 'error';
                            $this->result['info'] = 'error_save_card_scoring';
                            return $this->result();
                        }

                        Log::channel('cards')->info('buyer-settings');

                        //Buyer settings  - информация о лимит рассрочки
                        if (!$settings = BuyerSetting::where('user_id', $buyerCard->user_id)->first()) {
                            $settings = new BuyerSetting();
                        }
                        (new UserPayService)->createClearingAccount($settings->user_id);
                        $settings->user_id = $buyerCard->user_id;
                        $settings->period = Config::get('test.buyer_defaults.period');
                        $settings->zcoin = 0;
                        $settings->personal_account = 0;
                        $settings->rating = 0;
                        //Отменяем начисления лимитов со скоринга клиентов
//                            if($user->vip == 1){  // если вендор сам платит за клиента - лимит из конфига
//                                $limit = Config::get('test.vip_limit');  // 7000000
//                                $settings->limit = $limit;
//                                $settings->balance = $limit;
//                            }else{
//                                $settings->limit = $scoringResult;  // если это обычный вендор
//                                $settings->balance = $scoringResult;
//                            }

                        if (!$settings->save()) {
                            Log::channel('cards')->info('return status error: error_save_settings');
                            $this->result['status'] = 'error';
                            $this->result['info'] = 'error_save_settings';
                            return $this->result();

                        } else {
                            Log::channel('cards')->info('save BuyerSettings OK ' . $settings->user_id);
                        }

                        // статус пройденного скоринга
                        User::changeStatus($user, User::STATUS_CARD_ADD);

                        Log::channel('cards')->info('save buyer-settings buyer_id: ' . $settings->user_id . ' bs_id: ' . $settings->id);
                        Log::channel('cards')->info($settings);

                        // сохраняем карту
                        $buyerCard->save();

                    } else { // scoring = 0
                        Log::channel('cards')->info('scoring 0');
                        Log::channel('cards')->info($scoringResult);
                        Log::channel('cards')->info('return status error: error_card_not_accept');

                        // сохраняем скоринг
                        if (!$cardScoring = CardScoring::where('user_id', $user->buyer_id)->first()) $cardScoring = new CardScoring();
                        $cardScoring->user_id = $user->id;
                        $cardScoring->user_card_id = $buyerCard->id;
                        $cardScoring->period_start = $from;
                        $cardScoring->period_end = $to;
                        $cardScoring->status = 0;
                        $cardScoring->save();

                        if (!$cardScoringLog = CardScoringLog::where('user_id', $user->id)->first()) $cardScoringLog = new CardScoringLog();
                        $cardScoringLog->card_scoring_id = $cardScoring->id;
                        $cardScoringLog->user_id = $user->id;
                        $cardScoringLog->status = 0;
                        $cardScoringLog->scoring = 0;
                        $cardScoringLog->ball = $scoring_ball;
                        $cardScoringLog->scoring_count = 1;

                        $cardScoringLog->card_hash = md5($cardInfo['card']);
                        $cardScoringLog->request = json_encode($scoring['request'], JSON_UNESCAPED_UNICODE); // $scoring['request'];
                        $cardScoringLog->response = json_encode($scoring['response'], JSON_UNESCAPED_UNICODE);
                        $cardScoringLog->save();

                        // если клиент вип, все равно даем ему лимит - из конфига
                        if($user->vip == 1){
                            $limit = Config::get('test.vip_limit');
                            if (!$settings = BuyerSetting::where('user_id', $buyerCard->user_id)->first()) {
                                $settings = new BuyerSetting();
                            }
                            $settings->user_id = $buyerCard->user_id;
                            $settings->period = Config::get('test.buyer_defaults.period');
                            $settings->zcoin = 0;
                            $settings->personal_account = 0;
                            $settings->rating = 0;
                            $settings->limit = $limit;
                            $settings->balance = $limit;
                            $settings->save();
                            (new UserPayService)->createClearingAccount($settings->user_id);
                        }else{
                            $this->result['status'] = 'error';
                            $this->message('danger', __('auth.error_card_not_accept'));
                            return $this->result();
                        }
                    }

                } else {
                    Log::channel('cards')->info('return status error: service_unavailable');
                    $this->result['status'] = 'error';
                    $this->result['info'] = 'service_unavailable ENTER card again';
                    $this->message('danger', __('auth.service_unavailable'));
                    return $this->result();
                }

            } else {
                Log::channel('cards')->info('return status error: error_card_not_accept');
                $this->result['status'] = 'error';
                $this->message('danger', __('auth.error_card_not_accept'));
                return $this->result();
            }
            $this->result['status'] = 'success';
            $this->message('success', __('panel/buyer.txt_card_added'));
        }

        return $this->result();
    }

    /**
     * добавление доп карт клиента
     * @param Request $request
     * @return array|false|string
     */
    public function cardAdd(CardAddRequest $request)
    {

        $errors = [];

        if (!$request->has('card_number')) $errors[] = __('card_number not fill');
        if (!$request->has('card_valid_date')) $errors[] = __('card_valid_date not fill');

        if(isset($request->card_number)) {
            $cardType = CardHelper::checkTypeCard($request->card_number)['type'];
            if($cardType == 0) {
                $this->result['status'] = 'error';
                $this->error(__('api.unknown_type_of_card'));
                return $this->result();
            }
        }

        if ($request->has('buyer_id')) {
            if (!$user = Buyer::find($request->buyer_id)) {
                $errors[] = __('buyer not found');
            }
        } else {
            $user = Auth::user();
        }
        $request->merge(['card' => $request->card_number, 'exp' => $request->card_valid_date]);

        if (count($errors)) {
            $this->error($errors);
            $this->result['status'] = 'error';
            return $this->result();
        }

        $cardInfo = UniversalController::getCardPhone($request);
        // корректировка карты, удаление пробелов
        $cardCorrectionInfo = CardHelper::getAdditionalCardInfo($request->card_number, $request->card_valid_date);

        Log::channel('cards')->info('new CardAdd for ' . $user->id);
        Log::channel('cards')->info($cardInfo);

        if (!isset($cardInfo['result']) || $cardInfo['status'] == false) {
            $this->result['status'] = 'error';
            $this->error(__('card.error_card_number_or_valid_date'));
            return $this->result();
        }

        $cardInfo = $cardInfo['result'];
        $card_phone = $cardInfo['phone'] ?? false; //номер смс информирования

        if ($card_phone) {


            $cardNumber = $cardInfo['card_number'];
            $hashedCardNumber = md5($cardNumber);

            if ($card = Card::where('guid', $hashedCardNumber)->first()) {
                $this->result['status'] = 'error';
                $this->error(__('card_already_exists'));
            } else {

                $code = SmsHelper::generateCode();
                $msg = 'Kod: ' . $code . ". resusnasiya.uz kartani bog'lash uchun ruxsat so'radi " . CardHelper::getCardNumberMask($cardInfo['card_number']) . ' Tel: ' . callCenterNumber(2);

                [$result, $http_code] = SmsHelper::sendSms(correct_phone($card_phone), $msg);

                if (($http_code === 200) || ($result === SmsHelper::SMS_SEND_SUCCESS)) {

                    $hashedCode = Hash::make($code);

                    $card = new Card();
                    $card->user_id = $user->id; // 04.05, 11.08 - если не передан buyer_id , значит пользователь сам добавляет карту

                    /*$card->card_number = env('test_API_BALANCE_SWITCH_BOOLEAN') ? EncryptHelper::encryptData(str_replace(' ', '', $request->card)) : EncryptHelper::encryptData($cardInfo['card_number']);
                    $card->card_valid_date = EncryptHelper::encryptData($cardInfo['expire']);*/

                    $card->card_number = env('test_API_BALANCE_SWITCH_BOOLEAN') ? EncryptHelper::encryptData(str_replace(' ', '', $request->card)) : EncryptHelper::encryptData($cardCorrectionInfo['card']);
                    $card->card_valid_date = EncryptHelper::encryptData($cardInfo['expire']);

                    $card->card_name = $cardInfo['owner'];
                    $card->phone = correct_phone($card_phone); //EncryptHelper::encryptData($card_phone);
                    $card->type = EncryptHelper::encryptData(CardHelper::checkTypeCard($cardInfo['card_number'])['name']);
                    $card->token = md5($hashedCode . uniqid('test'));
                    $card->guid = $hashedCardNumber;// null;  --????
                    $card->status = Card::CARD_INACTIVE; // не активная
                    $card->hidden = 0;  // не показывать карту
                    $card->card_number_prefix = env('test_API_BALANCE_SWITCH_BOOLEAN') ? substr(str_replace(' ', '', $request->card), 0, 8) : substr($cardCorrectionInfo['card'], 0, 8);

                    if ($card->save()) {

                        Redis::set($card->token, $hashedCode);
                        $this->result['status'] = 'success';
                        $this->result['card_token'] = $card->token;

                        return $this->result();

                    } else {
                        $this->result['status'] = 'error';
                        $this->error(__('card save error') . ' ' . $cardInfo['card']);

                    }
                }
            }
        }

        return $this->result();

    }

    // проверка отправленного смс кода при добавлении дополнительной карты клиенту(ом)
    public function cardConfirm(Request $request)
    {

        $errors = [];

        if (!$request->has('card_token')) $errors[] = __('card token not fill');
        if (!$request->has('code')) $errors[] = __('sms code not fill');

        if (count($errors)) {
            $this->error($errors);
            $this->result['status'] = 'error';
            return $this->result();
        }

        $hashedCode = Redis::get($request->card_token);

        if (Hash::check($request->code, $hashedCode)) { // проверка смс кода

            if ($card = Card::where('token', $request->card_token)->first()) { // поиск карты по токену

                $card_number = EncryptHelper::decryptData($card->card_number);
                $card->sms_code = $request->code;
                $card->token = '';
                $card->status = Card::CARD_ACTIVE; // активная
                $card->hidden = 1;  //показывать карту
                $card->save();
                Redis::del($request->card_token);
                $this->result['status'] = 'success';
                $this->message('success', __('panel/buyer.txt_card_added'));
                return $this->result();

            } else {
                $this->result['status'] = 'error';
                $this->error('card not found');
            }
        } else {
            $this->result['status'] = 'error';
            $this->error('sms code not equal');
        }

        return $this->result();

    }

    /**
     * добавление доп карт клиента
     * api v2 (для мобильных пока используется старая версия)
     * @param Request $request
     * @return array|false|string
     */
    public function cardAddV2(CardAddRequest $request)
    {
        $errors = [];

        if (!$request->has('card_number')) $errors[] = __('card_number not fill');
        if (!$request->has('card_valid_date')) $errors[] = __('card_valid_date not fill');

        if(isset($request->card_number)) {
            $cardType = CardHelper::checkTypeCard($request->card_number)['type'];
            if($cardType == 0) {
                $this->result['status'] = 'error';
                $this->error('unknown_type_of_card');
                return $this->result();
            }
        }

        if ($request->has('buyer_id')) {
            if (!$user = Buyer::find($request->buyer_id)) {
                $errors[] = __('buyer not found');
            }
        } else {
            $user = Auth::user();
        }
        $request->merge(['card' => $request->card_number, 'exp' => $request->card_valid_date]);

        if (count($errors)) {
            $this->error($errors);
            $this->result['status'] = 'error';
            return $this->result();
        }

        $cardInfo = UniversalController::getCardPhone($request);

        Log::channel('cards')->info('new CardAdd for ' . $user->id);
        Log::channel('cards')->info($cardInfo);

        if (!isset($cardInfo['result']) || $cardInfo['status'] == false) {
            $this->result['status'] = 'error';
            $this->error(__('card.error_card_number_or_valid_date'));
            return $this->result();
        }

        $cardInfo = $cardInfo['result'];
        $card_phone = $cardInfo['phone'] ?? false; //номер смс информирования

        if ($card_phone)
        {
            $code = SmsHelper::generateCode();
            $msg = 'Kod: ' . $code . ". resusnasiya.uz kartani bog'lash uchun ruxsat so'radi " . CardHelper::getCardNumberMask($request->card_number) . ' Tel: ' . callCenterNumber(2);
            [$result, $http_code] = SmsHelper::sendSms(correct_phone($card_phone), $msg);

            if (($http_code === 200) || ($result === SmsHelper::SMS_SEND_SUCCESS)) {

                $hashedCode = Hash::make($code);
                $token = md5($hashedCode . uniqid('test'));
                /*$card = new Card();
                $card->user_id = $user->id; // 04.05, 11.08 - если не передан buyer_id , значит пользователь сам добавляет карту
                $card->card_number = EncryptHelper::encryptData($cardInfo['card_number']);
                $card->card_valid_date = EncryptHelper::encryptData($cardInfo['expire']);
                $card->card_name = $cardInfo['owner'];
                $card->phone = $card_phone; //EncryptHelper::encryptData($card_phone);
                $card->type = EncryptHelper::encryptData(CardHelper::checkTypeCard($cardInfo['card_number'])['name']);
                $card->token = md5($hashedCode . uniqid('test'));
                $card->guid = $hashedCardNumber;// null;  --????
                $card->status = Card::CARD_INACTIVE; // не активная
                $card->hidden = 0;  // не показывать карту*/

                //if ($card->save()) {

                    Redis::set($token, $hashedCode);
                    $this->result['status'] = 'success';
                    $this->result['card_token'] = $token;

                    return $this->result();

                /*} else {
                    $this->result['status'] = 'error';
                    $this->error(__('card save error') . ' ' . $cardInfo['card']);


                }*/

               //}

            }
        }

        return $this->result();

    }

    // проверка отправленного смс кода при добавлении дополнительной карты клиенту(ом)
    public function cardConfirmV2(Request $request)
    {
        $errors = [];

        if (!$request->has('card_token')) $errors[] = __('card token not fill');
        if (!$request->has('code')) $errors[] = __('sms code not fill');

        if ($request->has('buyer_id')) {
            if (!$user = Buyer::find($request->buyer_id)) {
                $errors[] = __('buyer not found');
            }
        } else {
            $user = Auth::user();
        }

        if (count($errors)) {
            $this->error($errors);
            $this->result['status'] = 'error';
            return $this->result();
        }

        $hashedCode = Redis::get($request->card_token);

        if (Hash::check($request->code, $hashedCode)) { // проверка смс кода

            if(isset($request->card_number)) {
                $cardType = CardHelper::checkTypeCard($request->card_number)['type'];
                if($cardType == 0) {
                    $this->result['status'] = 'error';
                    $this->error('unknown_type_of_card');
                    return $this->result();
                }
            }

            // корректировка карты, удаление пробелов
            $cardCorrectionInfo = CardHelper::getAdditionalCardInfo($request->card_number, $request->card_valid_date);

            $request->merge(['card' => $request->card_number, 'exp' => $request->card_valid_date]);
            $cardInfo = UniversalController::getCardPhone($request);
            $cardInfo = $cardInfo['result'];
            $card_phone = $cardInfo['phone'] ?? null; //номер смс информирования

            $card = new Card();
            $card->user_id = $user->id; // 04.05, 11.08 - если не передан buyer_id , значит пользователь сам добавляет карту
            /*$card->card_number = EncryptHelper::encryptData($cardInfo['card_number']);
            $card->card_valid_date = EncryptHelper::encryptData($cardInfo['expire']);*/

            $card->card_number = EncryptHelper::encryptData($cardCorrectionInfo['card']);
            $card->card_valid_date = EncryptHelper::encryptData($cardInfo['expire']);
            $card->card_name = $cardInfo['owner'];
            $card->phone = $card_phone;
            $card->type = EncryptHelper::encryptData(CardHelper::checkTypeCard($request->card_number)['name']);
            $card->guid = md5($cardCorrectionInfo['card']);
            $card->status = Card::CARD_ACTIVE; // активная
            $card->hidden = 1;  //показывать карту
            $card->sms_code = $request->code;
            $card->card_number_prefix = substr($cardCorrectionInfo['card'], 0, 8);
            $card->save();

            Redis::del($request->card_token);
            $this->result['status'] = 'success';
            $this->message('success', __('panel/buyer.txt_card_added'));
            return $this->result();

            /*if ($card = Card::where('token', $request->card_token)->first()) { // поиск карты по токену

                $card_number = EncryptHelper::decryptData($card->card_number);
                $card->sms_code = $request->code;
                $card->token = '';
                $card->status = Card::CARD_ACTIVE; // активная
                $card->hidden = 1;  //показывать карту
                $card->save();
                Redis::del($request->card_token);
                $this->result['status'] = 'success';
                $this->message('success', __('panel/buyer.txt_card_added'));
                return $this->result();

            } else {
                $this->result['status'] = 'error';
                $this->error('card not found');
            }*/
        } else {
            $this->result['status'] = 'error';
            $this->error('sms code not equal');
        }

        return $this->result();

    }

    // для проверки принадлежности карты клиенту при регистрации
    /* public function checkCard(Request $request){

         $errors = [];

         if(!$request->has('card'))  $errors[] = __('card not fill');
         if(!$request->has('exp'))   $errors[] = __('exp not fill');
         if(!$request->has('phone')) $errors[] = __('phone not fill');

         if(count($errors)){
             $this->error($errors);
             $this->result['status'] = 'error';
             return $this->result();
         }

         $info = UniversalController::getCardPhone($request); // Universal

         Log::channel('cards')->info('Регистрация клиента');
         Log::channel('cards')->info($info);

         // номер на который регистрируется клиент
         $user_phone = correct_phone( $request->phone ); // если продавец создает клиента, берем номер, который вбил продавец

         $card_phone = $info['result']['phone'] ?? false; //номер смс информирования

         if($card_phone) {
             // последние 4 цифры
             if (mb_substr($user_phone, 8, 4) == mb_substr($card_phone, 8, 4)) {

                 $cardNumber = $info['result']['card_number'];
                 $hashedCardNumber = md5($cardNumber);

                 $card = Card::where('guid', $hashedCardNumber)->first();
             }
         }

         if($request->has('scoring')){ // произвести скоринг




         }

     }

     // выполнить скоринг по всем картам клиента при регистрации
     public function cardsScoring(Request $request){

         if($request->has('buyer_id')) {

             if ($cards = Card::where('user_id', $request->buyer_id)->get()) {
                 foreach ($cards as $card) {

                 }

                 $data = [];

                 $result = UniversalHelper::scoringScore($data);

                 if ($result['scoring'] > 0) {
                     $this->result['status'] = 'success';
                 }
             }

         }else{
             $this->error(__('buyer_id not fill'));
             $this->result['status'] = 'error';
         }

         return $this->result();

     }
  */

    /**
     * Списко доступных карт пользователя
     * @param array $params
     * @return mixed
     */
    public function list($params = [])
    {
        $user = Auth::user();
        $request = request()->all();
        if (isset($request['api_token']))
            $params = $request;
        unset($params['api_token']);

        //Filter elements
        $filter = $this->filter($params);
        foreach ($filter['result'] as $index => $item) {

            if ($user->can('detail', $item)) {
                $item->permissions = $this->permissions($item, $user);

                //Получаем роль, кроме роли 'employee'
                $str = EncryptHelper::decryptData($item->card_number);
                $item->card_number = substr($str, 0, 4) . '****' . substr($str, -4);

                $item->card_valid_date = EncryptHelper::decryptData($item->card_valid_date);
                $rq = new Request();
                $rq->merge(['card_id' => $item->id,
                    'buyer_id' => $item->user_id,
                    'info_card' => ['token' => $item->token, 'card_id' => $item->id,
                        'card_number' => $item->card_number,
                        'card_valid_date' => $item->card_valid_date]]);

                $result = $this->balance($rq);

                $availableBalance = isset($result['result']['balance']) ? $result['result']['balance'] / 100 : 0;  // для Universal
                $sms_info = isset($result['result']['state']) ? $result['result']['state'] : -1;  // для Universal

                $item->balance = number_format($availableBalance, 2, '.', ' ') ?? null;
                $item->sms_info = $sms_info ?? null;
            } else
                $filter['result']->forget($index);

        }

        //Collect data
        $this->result['response']['total'] = $filter['total'];
        $this->result['status'] = 'success';

        //Format data
        if (isset($params['list_type']) && $params['list_type'] == 'data_tables') {
            $filter['result'] = $this->formatDataTables($filter['result']);
        }
        //Collect data

        $this->result['data'] = $filter['result'];

        //Return data
        return $this->result();

    }


    /** Скоринг для Uzcard м Humo, api/v1/buyers/scoring
     * @param Request $request
     * @return array|false|mixed|string
     */
    public function scoring(Request $request)
    {
        $buyer = Buyer::find($request->buyer_id);
        $infoCard = $buyer->cards->where('id', $request->card_id)->where('user_id', $buyer->id)->first();
        if ($infoCard != null) {
            $card = EncryptHelper::decryptData($infoCard->card_number);
            $request->merge(['info_card' => $infoCard->toArray()]);
            switch (CardHelper::checkTypeCard($card)['type']) {
                case 1:
                    $uzCard = new UZCardController();
                    $result = $uzCard->scoring($request);
                    break;
                case 2:
                    $humoCard = new HumoCardController();
                    $result = $humoCard->scoring($request);
                    break;
            }
            return $result;
        } else {
            $this->result['status'] = 'error';
            $this->message('danger', __('card.card_not_empty_error'));
            return $this->result();
        }
    }

    /** Скоринг для Uzcard м Humo, api/v1/employee/buyers/scoring-universal
     * @param Request $request
     * @return array|false|mixed|string
     */
    public function scoringUniversal(ScoringUniversalRequest $request)
    {
        Log::channel('cards')->info('CardController->scoringUniversal()');
        Log::channel('cards')->info($request);

        $buyer = Buyer::find($request->buyer_id);
        $infoCard = $buyer->cards->where('id', $request->card_id)->where('user_id', $buyer->id)->first();
        $date_start = Carbon::parse($request->date_start)->format('Ymd');
        $date_end = Carbon::parse($request->date_end)->format('Ymd');
        $card = EncryptHelper::decryptData($infoCard->card_number);

        $request->merge(['info_card' => $infoCard->toArray()]);
        $request->merge(['start_date' => $date_start]);
        $request->merge(['end_date' => $date_end]);

        if ($infoCard != null) {

            $last_scoring_obj = new LastScoringLog($request->buyer_id, $card);
            $last_scoring = $last_scoring_obj->getLastScoring();

            if ($last_scoring_obj->getMonthDifference() < 1 && $request->scoring_from_server){

                $result = json_decode($last_scoring->response, true);

                Log::channel('cards')->info('scoring result LOCAL from DB:');
                Log::channel('cards')->info($result);

                Log::channel('cards')->info('scoring from: ' . __CLASS__ . ' ' . __METHOD__ . ' ' . __LINE__);
                $scoring = UniversalHelper::scoringScore($result['result']);

                Log::channel('cards')->info($scoring);

                return [
                    'status' => 'success',
                    'data' => $result['result'],
                    'result' => $result['result'],
                    'response' => $result,
                    'local' => true,
                    'scoring' => $scoring['scoring']
                ];

            }else{

                switch (CardHelper::checkTypeCard($card)['type']) {
                    case 1:

                        //$uzCard = new UniversalController();
                        $result = UniversalController::getScoring($request); //  $uzCard->scoring($request);
                        break;
                    case 2:
                        // $humoCard = new UniversalController();
                        $request->merge(['url_humo' => true]);
                        $result = UniversalController::getScoring($request, $request->url_humo); // $humoCard->scoring($request);
                        break;
                }

                return $result;

            }

        }else{
            $this->result['status'] = 'error';
            $this->message('danger', __('card.card_not_empty_error'));
            return $this->result();
        }

    }


    /** api
     * @param Request $request
     * @return array|false|mixed|string
     */
    public function balance(Request $request)
    {
        $buyer = Buyer::find($request->buyer_id);
        $card = $buyer->cards->where('id', $request->card_id)->where('user_id', $buyer->id)->first(); // если несколько карт по ID карты

        if ($card != null && $card->token_payment) {

            $cardBalanceResponse = (new testCardService())->getCardBalance($card->token_payment);
            $cardInfoResponse = (new testCardService())->getCardInfo($card->token_payment);

            if ($cardBalanceResponse['status'] == 'success' && $cardInfoResponse['status'] == 'success') {

                $response['result'] = [
                    "card_number" => $request->has('is_cron') ? '' : $card->card_number,
                    "expire" => $request->has('is_cron') ? '' : $card->card_valid_date,
                    "phone" => $cardInfoResponse['card']['phoneNumber'],
                    "balance" => $cardBalanceResponse['balance'],
                    "state" => $card->status,
                    "filial" => '',
                    "is_corporate" => '',
                    "owner" => $cardInfoResponse['card']['holderFullName'],
                    "account" => '',
                    'status' => 'success',
                    'response' => '',
                ];

                return $response;

            } else {

                $this->result['status'] = 'error';
                $this->message('danger', __('card.card_error'));
                return $this->result();
            }

        } else {
            $this->result['status'] = 'error';
            $this->message('danger', __('card.card_not_empty_error'));
            return $this->result();
        }
    }


    /** списание средств
     * @param Request $request
     * @return array|false|string
     */
    public function payment(Request $request)
    {
        $myUzcard_flag = false;
        $buyer = Buyer::find($request->buyer_id);


        // для 1-й карты
        $infoCard = $buyer->cards->where('id', $request->card_id)->where('user_id', $buyer->id)->first(); //

        if ($infoCard != null) {
            $card = EncryptHelper::decryptData($infoCard->card_number);
            $type = CardHelper::checkTypeCard($card)['type'];
            $request->merge(['type' => $type]);
            $request->merge(['info_card' => $infoCard->toArray()]);

            switch ($type) {
                case 1:
                    if ($myUzcard_flag) {     // резервное списание
                        $uzCard = new UZCardController();
                        $result = $uzCard->payment($request);
                    } else {
                        $pay = new UniversalController();
                        $result = $pay->payment($request);  // списываем с Universal
                    }

                    break;
                case 2:
                    if ($myUzcard_flag) {  // резервное списание
                        $humoCard = new HumoCardController();
                        $result = $humoCard->payment($request);
                    } else {
                        $pay = new UniversalController();
                        $request->merge(['url_humo' => true]);
                        $result = $pay->payment($request);  // списываем с Universal
                    }

                    break;
            }
            return $result;
        } else {
            $this->result['status'] = 'error';
            $this->message('danger', __('card.card_not_empty_error'));
            return $this->result();
        }
    }


    /**
     * @param Request $request - ID платежа для отмены данного платежа
     * @return array|false|String
     */
    public function refund(Request $request)
    {
        $myUzcard_flag = false;
        $refunds = [];

        if ($request->has('payment_id'))
            $refunds = Payment::where('id', $request->payment_id)->get();
        //$refunds = Payment::where('transaction_id', $request->payment_id)->get();
        elseif ($request->has('order_id'))
            $refunds = Payment::where('order_id', $request->order_id)->get();

        if (sizeof($refunds) > 0) {
            foreach ($refunds as $refund) {
                $infoCard = $refund->buyer->cards->find($refund->card_id);
                if ($infoCard != null && $refund->transaction_id != null) {
                    $card = EncryptHelper::decryptData($infoCard->card_number);
                    $request->merge(['info_card' => $infoCard->toArray(),
                        'transaction_id' => $refund->transaction_id,
                        'payment_id' => $refund->id,
                        'uuid' => $refund->uuid]);
                    $istestPaymentSystem = preg_match('/^\w{8}-\w{4}-\w{4}-\w{4}-\w{12}$/', $refund->transaction_id); // по transaction_id узнаем, наша это платежка или сторонняя
                    switch (CardHelper::checkTypeCard($card)['type']) {
                        case 1:
                            if ($myUzcard_flag) {     // резервное списание
                                $uzCard = new UZCardController();
                                $result = $uzCard->refund($request);
                            }
                            else if($istestPaymentSystem)
                            {   // Наша платежная система
                                $refundPayment = new PaymentController();
                                if ($refund->type === 'user') {
                                    if ($refund->buyer->settings->personal_account >= $refund->amount) {
                                        $result = $refundPayment->refundPayment($request);
                                        if ($this->result['status'] == 'success'){
                                            $refund->buyer->settings->personal_account -= $refund->amount;
                                            $refund->buyer->settings->save();
                                        }
                                    } else {
                                        return [
                                            'status' => 'error',
                                            'response' => [
                                                'message' => [
                                                    ['text' => 'На лицевом счете не достаточно средств!']
                                                ]
                                            ],
                                        ];
                                    }
                                } else {
                                    $result = $refundPayment->refundPayment($request);
                                }
                            }
                            else
                            {
                                $request->merge(['type' => 1]);
                                $pay = new UniversalController();
                                $result = $pay->reverse($request);  // Universal
                            }
                            break;
                        case 2:
                            if ($myUzcard_flag)
                            {  // резервное списание
                                $humoCard = new HumoCardController();
                                $result = $humoCard->refund($request);
                            }
                            else if($istestPaymentSystem) {   // Наша платежная система
                                $refundPayment = new PaymentController();

                                if ($refund->type === 'user') {
                                    if ($refund->buyer->settings->personal_account >= $refund->amount) {
                                        $result = $refundPayment->refundPayment($request);
                                        if ($this->result['status'] == 'success'){
                                            $refund->buyer->settings->personal_account -= $refund->amount;
                                            $refund->buyer->settings->save();
                                        }
                                    } else {
                                        return [
                                            'status' => 'error',
                                            'response' => [
                                                'message' => [
                                                    ['text' => 'На лицевом счете не достаточно средств!']
                                                ]
                                            ],
                                        ];
                                    }
                                } else {
                                    $result = $refundPayment->refundPayment($request);
                                }
                            }

                            else
                            {
                                $pay = new UniversalController();
                                $request->merge(['url_humo' => 2]);
                                $result = $pay->reverse($request);  // Universal
                            }
                            break;
                    }
                    $this->result = $result;
                    //return $result;
                } elseif ($refund->payment_system == 'ACCOUNT' && $refund->amount > 0) {  //если возврат с лицевого счета ???

                    $refund->buyer->settings->personal_account += $refund->amount;
                    $refund->buyer->settings->save();

                    $payment = new Payment();
                    $payment->schedule_id = $refund->schedule_id;
                    $payment->type = 'refund';
                    $payment->order_id = $refund->order_id;
                    $payment->contract_id = $refund->contract_id;
                    $payment->card_id = $refund->card_id;
                    $payment->amount = $refund->amount * -1;
                    $payment->user_id = $refund->user_id;
                    $payment->status = 1;
                    $payment->payment_system = $refund->payment_system;
                    $payment->save();

                } else {
                    $this->result['status'] = 'error';
                    $this->message('danger', __('card.card_not_empty_error'));
                }
            }
        } else {
            $this->result['status'] = 'error';
            $this->message('danger', __('card.card_not_empty_error'));
        }
        return $this->result();
    }

    /** пополнение средств на ЛС - банковский перевод
     * @param Request $request
     * @return array|false|string
     */
    public function bankAmountAdd(Request $request)
    {
        if (isset($request->password) && $request->password != null) {

            $a = $request->password <=> UniversalController::PASSWORD;

            if ($a != 0) {
                $this->result['status'] = 'error';
                $this->message('danger', __('app.password_error'));
                return $this->result;
            } else {
                if ($request->buyer_id) {
                    $amount = abs($request->amount);
                    $type = $request->type;

                    $payment = new Payment();
                    $payment->user_id = $request->buyer_id;
                    $payment->amount = $amount;
                    $payment->type = 'user';
                    $payment->payment_system = $type;
                    $payment->status = 1;

                    if ($payment->save()) {
                        if ($settings = BuyerSetting::where('user_id', $request->buyer_id)->first()) {
                            $settings->personal_account += $amount;
                            if ($settings->save()) {
                                $client = Http::withOptions([
                                    'cert' => [storage_path('cert_external/client_1.pfx'),  config('test.external_cert_pass')],
                                    'curl'     => [CURLOPT_SSLCERTTYPE => 'P12'],
                                ]);

                                $created_at = Carbon::make($payment->created_at);
                                $create_at = Carbon::make($payment->create_at);
                                $perform_time = Carbon::make($payment->perform_time);
                                $performAt = Carbon::make($payment->performAt);


                                $response = $client->post(config('test.external_service_url').'create/transaction', [
                                    'contractId'         => $payment->contract_id,
                                    'orderId'            => $payment->order_id,
                                    'userId'             => $payment->user_id,
                                    'scheduleId'         => $payment->schedule_id,
                                    'cardId'             => $payment->card_id,
                                    'transactionId'      => $payment->id,
                                    'uuid'               => $payment->uuid,
                                    'amount'             => $payment->amount * 100,
                                    'type'               => $payment->type,
                                    'paymentSystem'      => $type,
                                    'state'              => $payment->state,
                                    'createdAt'          => $created_at->format('c'),
                                    'createAt'           => $create_at ? $create_at->format('c') : null,
                                    'performTime'        => $perform_time ? $perform_time->format('c') : null,
                                    'updatedAt'          => $payment->updated_at->format('c'),
                                    'performAt'          => $performAt ? $performAt->format('c') : null,
                                ]);

                                if ($response->failed()){
                                    Log::channel('bma_external')->info(__METHOD__, [
                                        'code' => $response->status(),
                                        'body' => $response->body()
                                    ]);
                                }

                                $this->result['status'] = 'success';
                                $this->message('success', __('panel/buyer.succes_bank_amount'));
                                return $this->result;
                            }
                        }
                    } else {
                        $this->result['status'] = 'error';
                        $this->message('danger', 'try_again_later');
                        return $this->result;
                    }

                } else {
                    $this->result['status'] = 'error';
                    $this->message('danger', __('app.buyer_not_found'));
                    return $this->result;
                }
            }
        } else {
            $this->result['status'] = 'error';
            $this->message('danger', __('app.password_error'));
            return $this->result;
        }

    }

    /** добавление потерянных платежей в бд
     * @param Request $request
     * @return array|false|string
     */
    public function checkTransactions(Request $request)
    {
        if (isset($request->password) && $request->password != null) {
            $a = $request->password <=> UniversalController::PASSWORD;

            if ($a != 0) {
                $this->result['status'] = 'error';
                $this->message('danger', __('app.password_error'));
                return $this->result;

            } else {

                if ($request->buyer_id) {

                    $transactions = [];
                    $cron_payments_transactions = [];
                    $payment_transactions = Payment::where(['user_id' => $request->buyer_id])->whereIn('payment_system', ['UZCARD', 'HUMO'])->pluck('transaction_id')->toArray(); // только карты
                    $cron_payments = CronPayment::where(['user_id' => $request->buyer_id, 'type' => 1])->select('response')->get();  // pnfl не берем ??

                    foreach ($cron_payments as $cron_payment) {
                        // парсим response
                        $response = json_decode($cron_payment->response);
                        $card_id = $response->card->card_id ?? null;
                        $cron_payments_transactions[] = $response->payment->id;
                    }

                    $payment_transactions = array_merge($cron_payments_transactions, $payment_transactions);

                    $cards = Card::where('user_id', $request->buyer_id)->get();
                    $guids = [];
                    foreach ($cards as $card) {
                        $card_number = EncryptHelper::decryptData($card->card_number);
                        $guid = md5($card_number);
                        if (in_array($guid, $guids)) continue;   // в бд могут быть две одинаковые карты(??? ), не проверяем вторую

                        $type = CardHelper::checkTypeCard($card_number)['type'];
                        $start_date = '';
                        $end_date = '';

                        $request = new Request();
                        $request->merge([
                            'card_number' => $card_number,
                            'start_date' => $start_date,
                            'end_date' => $end_date,
                            'type' => $type
                        ]);

                        $res = new \App\Http\Controllers\Core\UniversalController();
                        $result = $res->getTransactions($request);

                        // все транзакции по картам
                        if ($result['status']) {
                            foreach ($result['result'] as $key => $res) {
                                if (isset($res['user']) && $res['user'] != 'test') continue;

                                if ($res['state'] == 4) {

                                    // проверим есть ли транзакция в бд
                                    if (!in_array($res['payment_id'], $payment_transactions)) {
                                        $res = array_merge(['lost' => 1], $res);  // если нет
                                    }
                                }
                                $res['created_at'] = Carbon::parse($res['created_at'])->format('d.m.Y');
                                $res['card'] = CardHelper::getCardMask($res['card_number']);
                                $res = array_merge(['lost' => 0], $res);
                                $res = array_merge(['card_id' => $card->id], $res);
                                $transactions[] = $res;
                            }

                            $this->result['status'] = 'success';
                            $this->result['data'] = $transactions;
                            $this->message('success', __('panel/buyer.succes_bank_amount'));

                        } else {
                            continue;
                            /*$this->result['status'] = 'error';
                            $this->message('danger', __('app.password_error'));
                            return $this->result;*/
                        }

                        $guids[] = $guid;
                    }
                    //dd($transactions);
                    return $this->result;
                }
            }
        }else {
                $this->result['status'] = 'error';
                $this->message('danger', __('app.password_error'));
                return $this->result;
            }
        }

        /** добавление потерянных платежей в бд
         * @param Request $request
         * @return array|false|string
         */
        public
        function setTransactions(Request $request)
        {
            if ($request->buyer_id) {

                $result = $this->checkTransactions($request);

                if ($result['status']) {
                    foreach ($result['data'] as $res) {

                        if ($res['payment_id'] === $request->transaction_id) {

                            $payment_system = CardHelper::checkTypeCard($res['card_number'])['name'];

                            $payment = new Payment();
                            //$payment->type = $request->type;
                            $payment->type = 'user';  // всегда пополнение - лишние средства
                            $payment->card_id = $res['card_id'];
                            $payment->amount = $res['amount'] / 100;
                            $payment->user_id = $request->buyer_id;
                            $payment->payment_system = $payment_system;
                            $payment->transaction_id = $res['payment_id'];
                            $payment->uuid = $res['uuid'];
                            $payment->created_at = $res['created_at'];
                            $payment->updated_at = $res['updated_at'];
                            $payment->status = 1;


                            if ($payment->save()) {
                                if ($request->type === 'user') {
                                    $buyer_settings = BuyerSetting::where('user_id', $request->buyer_id)->first();
                                    $buyer_settings->personal_account += $payment->amount;
                                    $buyer_settings->save();
                                }

                            } else {
                                $this->result['status'] = 'error';
                                $this->message('danger', __('app.password_error'));
                                return $this->result;
                            }

                        }
                    }

                    $this->result['status'] = 'success';
                    $this->message('success', __('panel/buyer.succes_bank_amount'));
                    return $this->result;

                } else {
                    $this->result['status'] = 'error';
                    $this->message('danger', __('app.password_error'));
                    return $this->result;
                }

            }

        }


        /** пополнение средств на ЛС
         * @param Request $request
         * @return array|false|string
         */
    public function adjunction(Request $request)
    {
        return (new UserPayService())->refillUserAccount($request->sum,
                                                              $request->card_id,
                                                              $request->user_id);
    }

        /**
         * activate/deactivate card by card ID
         *
         * @param Model $card
         *
         * @return array|bool|false|string
         */
        public
        function changeStatus($id)
        {
            $user = Auth::user();
            if ($id) {
                $card = Model::find($id);
                if ($card->status == 0) {
                    $card->status = Card::CARD_ACTIVE;
                } else {
                    $card->status = Card::CARD_INACTIVE;
                }

                $card->save();

                $str = EncryptHelper::decryptData($card->card_number);
                $card_number = substr($str, 0, 4) . '****' . substr($str, -4);

                // добавляем в историю запись
                KycHistory::insertHistory($card->user_id, User::KYC_STATUS_EDIT, User::CARD_ACTIVE, null, $card_number);

                $this->result['status'] = 'success';
                $this->message('success', __('panel/employee.txt_activated'));
            } else {
                $this->result['status'] = 'error';
                $this->result['response']['code'] = 404;
                $this->message('danger', __('app.err_not_found'));
            }

            return $this->result();
        }

        /**
         * activate card
         *
         * @param Model $card
         *
         * @return array|bool|false|string
         */
        public
        function activate($id)
        {
            $user = Auth::user();
            if ($id) {
                $card = Model::find($id);
                $card->status = Card::CARD_ACTIVE;
                $card->save();

                $str = EncryptHelper::decryptData($card->card_number);
                $card_number = substr($str, 0, 4) . '****' . substr($str, -4);

                // добавляем в историю запись
                KycHistory::insertHistory($card->user_id, User::KYC_STATUS_EDIT, User::CARD_ACTIVE, null, $card_number);

                $this->result['status'] = 'success';
                $this->message('success', __('panel/employee.txt_activated'));
            } else {
                $this->result['status'] = 'error';
                $this->result['response']['code'] = 404;
                $this->message('danger', __('app.err_not_found'));
            }

            return $this->result();
        }


        /**
         * deactivate card
         *
         * @param Model $card
         *
         * @return array|bool|false|string
         */
        public
        function deactivate($id)
        {
            $user = Auth::user();
            if ($id) {
                $card = Model::find($id);
                $card->status = Card::CARD_INACTIVE;
                $card->save();

                $str = EncryptHelper::decryptData($card->card_number);
                $card_number = substr($str, 0, 4) . '****' . substr($str, -4);

                // добавляем в историю запись
                KycHistory::insertHistory($card->user_id, User::KYC_STATUS_EDIT, User::CARD_INACTIVE, null, $card_number);

                $this->result['status'] = 'success';
                $this->message('success', __('panel/news.txt_deactivated'));

            } else {
                $this->result['status'] = 'error';
                $this->result['response']['code'] = 404;
                $this->message('danger', __('app.err_not_found'));
            }

            return $this->result();
        }

        /**
         * Delete card
         *
         * @param int $id
         * @return array|bool|false|string
         */
        public
        function delete(int $id)
        {

            $user = Auth::user();

            if ($id) {
                $card = Model::find($id);
                if ($card) {
                    $card->status = Card::CARD_DELETED;
                    $card->save();

                    $str = EncryptHelper::decryptData($card->card_number);
                    $card_number = substr($str, 0, 4) . '****' . substr($str, -4);

                    // добавляем в историю запись
                    KycHistory::insertHistory($card->user_id, User::KYC_STATUS_EDIT, User::CARD_DELETED, null, $card_number);

                    $this->result['status'] = 'success';
                    $this->message('success', __('panel/employee.txt_deleted'));
                } else {
                    $this->result['status'] = 'error';
                    $this->result['response']['code'] = 404;
                    $this->message('danger', __('app.err_not_found'));
                }
            } else {
                $this->result['status'] = 'error';
                $this->result['response']['code'] = 404;
                $this->message('danger', __('app.err_not_found'));
            }

            return $this->result();
        }


        /**
         * add humo cards to user's cards by BUTTON by ID
         *
         * @param Request $request
         * @return mixed
         */
    public function addCardsHumo(Buyer $buyer_id)
    {
        if ($buyer_id->phone)
            $this->result = AddCardInfoHumoUzcard::getHumoCards($buyer_id->phone, $buyer_id->id);

        return $this->result;
    }

        /**
         * add uzcard cards(pnfl) to users cards by BUTTON by user ID
         *
         * @param Request $request
         * @return mixed
         */
        public
        function addCardsUzcard(Request $request)
        {
            $rq = new Request();
            $card_ids = [];

            $buyer = Buyer::where('id', $request->buyer_id)->with('personals')->first();
            // добавляем в историю запись - кто нажал на кнопку
            KycHistory::insertHistory($buyer->id, User::KYC_STATUS_EDIT, User::CARD_ADD);

            // серия и номер паспорта
            if ($buyer->personals) {
                $passport = EncryptHelper::decryptData($buyer->personals->passport_number);
                if (strpos($passport, ' ') > 0) {
                    [$passportSeries, $passportNumber] = explode(' ', $passport);
                } else {
                    $passportSeries = mb_substr($passport, 0, 2);
                    $passportNumber = mb_substr($passport, 2, 7);
                }

                if (isset($buyer->cardsPnfl)) {              // если такая карта уже есть, не будем добавлять
                    foreach ($buyer->cardsPnfl as $card) {
                        $card_ids[] = $card->card_id;
                    }
                }


                $rq->merge([
                    'pnfl' => EncryptHelper::decryptData($buyer->personals->pinfl),
                    'lastName' => $buyer->name != '' ? $buyer->name : 'abc',
                    //'lastName' => $buyer->name,
                    'firstName' => $buyer->surname,
                    'middleName' => $buyer->patronymic,
                    'birthDate' => EncryptHelper::decryptData($buyer->personals->birthday),
                    'passportSeries' => $passportSeries,
                    'passportNumber' => $passportNumber,
                    'passportIssueDate' => EncryptHelper::decryptData($buyer->personals->passport_date_issue),
                    'passportExpDate' => EncryptHelper::decryptData($buyer->personals->passport_expire_date) > 0 ? EncryptHelper::decryptData($buyer->personals->passport_expire_date) : '11',
                ]);

                if (!$pnfl_contract = CardPnflContract::where('user_id', $request->buyer_id)->first()) {

                    $result = UniversalPnflController::getClientId($rq);

                    if ($result['status'] == true) {

                        $pnfl_contract = new CardPnflContract();
                        $pnfl_contract->user_id = $request->buyer_id;
                        $pnfl_contract->clientId = $result['result']['clientId'];
                        $pnfl_contract->save();

                    } else {
                        $this->result['status'] = 'error';
                        $this->result['message'] = $result['error']['message'];
                        return $this->result;
                    }
                }

                // получить все карты клиента
                $rq = new Request();
                $rq->merge([
                    'clientId' => $pnfl_contract->clientId,
                ]);

                $result = UniversalPnflController::getCards($rq);


                if (isset($result['status']) && $result['status'] == true) {

                    if (isset($result['result'])) {
                        $new_cards = $result['result'];

                        foreach ($new_cards as $key => $card) {

                            if (!in_array($card['id'], $card_ids)) {

                                $user_cards = new CardPnfl();
                                $user_cards->user_id = $buyer->id;
                                $user_cards->card_id = $card['id'];
                                $user_cards->status = $card['status'];
                                $user_cards->pan = $card['pan'];
                                $user_cards->card_phone = $card['phone'];
                                $user_cards->fullName = $card['fullName'];
                                $user_cards->sms = $card['sms'];
                                $user_cards->state = 0;

                                if ($user_cards->save()) {
                                    $card_ids[] = $user_cards->id;
                                    $this->result['status'] = 'success';
                                } else {
                                    $this->result['status'] = 'error'; // ??
                                }
                            }
                        }
                    }
                } else {
                    $this->result['status'] = 'error';
                    $this->result['message'] = $result['error']['message'];
                }

                return $this->result;
            }

        }

        /**
         * activate card pnfl
         *
         * при активации карты создается контракт с банком и мы можем списать
         *
         * @param Model $card
         *
         * @return array|bool|false|string
         */
        public
        function activatePnflCard($id)
        {

            $user = Auth::user();
            if ($id) {
                $card = CardPnfl::where('id', $id)->with('pnflContract')->first();

                // создаем контракт на клиента, если еще нет
                if (isset($card->PnflContract)) {
                    if (empty($card->PnflContract->contract_id)) {
                        $buyer = Buyer::where('id', $card->user_id)->with('personals')->first();

                        // серия и номер паспорта
                        if ($buyer->personals) {
                            $passport = EncryptHelper::decryptData($buyer->personals->passport_number);
                            if (strpos($passport, ' ') > 0) {
                                [$passportSeries, $passportNumber] = explode(' ', $passport);
                            } else {
                                $passportSeries = mb_substr($passport, 0, 2);
                                $passportNumber = mb_substr($passport, 2, 7);
                            }
                        }

                        $rq = new Request();
                        $rq->merge([
                            'clientId' => $card->PnflContract->clientId,
                            'pnfl' => EncryptHelper::decryptData($buyer->personals->pinfl),
                            'lastName' => $buyer->name != '' ? $buyer->name : 'abc',
                            //'lastName' => $buyer->name,
                            'firstName' => $buyer->surname,
                            'middleName' => $buyer->patronymic,
                            'birthDate' => EncryptHelper::decryptData($buyer->personals->birthday),
                            'passportSeries' => $passportSeries,
                            'passportNumber' => $passportNumber,
                            'passportIssueDate' => EncryptHelper::decryptData($buyer->personals->passport_date_issue),
                            'passportExpDate' => EncryptHelper::decryptData($buyer->personals->passport_expire_date) > 0 ? EncryptHelper::decryptData($buyer->personals->passport_expire_date) : '11',
                        ]);

                        $result = UniversalPnflController::createContractId($rq);

                        if ($result['status'] == true) {
                            $card->PnflContract->contract_id = $result['contract_id'];
                            $card->PnflContract->save();

                            // добавляем в историю запись
                            KycHistory::insertHistory($card->user_id, User::KYC_STATUS_EDIT, User::CONTRACT_ADD, null, $card->pan);
                        } else {
                            $this->result['status'] = 'error';
                            $this->message = $result['message'];
                            return $this->result;
                        }
                    }
                    $card->state = Card::CARD_ACTIVE;
                    $card->save();
                }


                // добавляем в историю запись
                KycHistory::insertHistory($card->user_id, User::KYC_STATUS_EDIT, User::CARD_ACTIVE, null, $card->pan);

                $this->result['status'] = 'success';
                $this->message('success', __('panel/employee.txt_activated'));
            } else {
                $this->result['status'] = 'error';
                $this->result['response']['code'] = 404;
                $this->message('danger', __('app.err_not_found'));
            }

            return $this->result();
        }


        /**
         * deactivate card pnfl
         * @param Model $card
         *
         * @return array|bool|false|string
         */
        public
        function deactivatePnflCard($id)
        {
            $user = Auth::user();
            if ($id) {
                $card = CardPnfl::find($id);
                $card->state = Card::CARD_INACTIVE;
                $card->save();


                // добавляем в историю запись
                KycHistory::insertHistory($card->user_id, User::KYC_STATUS_EDIT, User::CARD_INACTIVE, null, $card->pan);

                $this->result['status'] = 'success';
                $this->message('success', __('panel/news.txt_deactivated'));

            } else {
                $this->result['status'] = 'error';
                $this->result['response']['code'] = 404;
                $this->message('danger', __('app.err_not_found'));
            }

            return $this->result();
        }

        /**
         * Delete card pnfl
         *
         * @param int $id
         * @return array|bool|false|string
         */
        public
        function deletePnflCard(int $id)
        {

            $user = Auth::user();

            if ($id) {
                $card = CardPnfl::find($id);
                if ($card) {
                    $card->state = Card::CARD_DELETED;
                    $card->save();

                    // добавляем в историю запись
                    KycHistory::insertHistory($card->user_id, User::KYC_STATUS_EDIT, User::CARD_DELETED, null, $card->pan);

                    $this->result['status'] = 'success';
                    $this->message('success', __('panel/employee.txt_deleted'));
                } else {
                    $this->result['status'] = 'error';
                    $this->result['response']['code'] = 404;
                    $this->message('danger', __('app.err_not_found'));
                }
            } else {
                $this->result['status'] = 'error';
                $this->result['response']['code'] = 404;
                $this->message('danger', __('app.err_not_found'));
            }

            return $this->result();
        }

        public function getCardInfo(Request $request)
        {
            $user = Auth::user();
            if($card = Card::where(['id' => $request->card_id, 'user_id' => $user->id])->first())
            {
                $number     = EncryptHelper::decryptData($card->card_number);
                $expiryDate = EncryptHelper::decryptData($card->card_valid_date);
                $cardInfo   = new GetCardInfo();
                $response   = $cardInfo->getCardInfo($number, $expiryDate);
                if($response['status'] === true)
                {
                    BaseService::handleResponse(new GetCardInfoResource($response));
                }
                else
                {
                    if(isset($response['error']['message']))
                        BaseService::handleError([__('card.'.formatToLangVariable(reset($response['error']['message'])))]);
                    else
                        BaseService::handleError([__('card.something_went_wrong')]);
                }
            }
            else
            {
                BaseService::handleError([__('card.card_not_found')]);
            }
        }

    }
