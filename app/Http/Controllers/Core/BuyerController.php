<?php

namespace App\Http\Controllers\Core;

use App\Classes\ApiResponses\Katm\Reports\KatmResponseReport;
use App\Classes\CURL\Katm\KatmRequestClaimRegistration;
use App\Classes\CURL\Katm\KatmRequestClientAddress;
use App\Classes\CURL\Katm\KatmRequestCreditReport;
use App\Classes\CURL\Katm\KatmRequestCreditReportStatus;
use App\Classes\CURL\Test\CardRequest;
use App\Classes\CURL\Test\PaymentsRequest;
use App\Classes\Exceptions\TestException;
use App\Classes\Scoring\test\Converter\UniversalResponseConverter;
use App\Classes\Scoring\test\Scoring;
use App\Enums\CategoriesEnum;
use App\Facades\GradeScoring;
use App\Helpers\CardHelper;
use App\Helpers\EncryptHelper;
use App\Helpers\FileHelper;
use App\Helpers\KatmHelper;
use App\Helpers\PaymentHelper;
use App\Helpers\PushHelper;
use App\Helpers\RoyxatHelper;
use App\Helpers\GnkSalaryHelper;
use App\Helpers\SmsHelper;
use App\Helpers\TelegramHelper;
use App\Http\Requests\AddGuarantRequest;
use App\Http\Requests\AddDepositRequest;
use App\Http\Requests\ContractPayRequest;
use App\Http\Requests\ContractPayV2Request;
use App\Http\Requests\BuyerPersonalDataRequest;
use App\Http\Requests\Core\BuyerController\ContractRequest;
use App\Http\Requests\Core\BuyerController\PhonesCountRequest;
use App\Http\Requests\V3\Buyer\CheckSimilarityRequest;
use App\Libs\KatmReportLibs;
use App\Logging\ByUser\LoggerByUser;
use App\Models\Buyer;
use App\Models\Buyer as Model;
use App\Models\BuyerAddress;
use App\Models\BuyerGuarant;
use App\Models\BuyerPersonal;
use App\Models\BuyerSetting;
use App\Models\Card;
use App\Models\CardScoringLog;
use App\Models\CatalogCategory;
use App\Models\CatalogPartners;
use App\Models\Company;
use App\Models\ContractPaymentsSchedule;
use App\Models\Friends;
use App\Models\KatmScoring;
use App\Models\KycHistory;
use App\Models\MyIDJob;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Payment;
use App\Models\PayService;
use App\Models\PaySystem;
use App\Models\RoyxatCredits;
use App\Models\ScoringResultMini;
use App\Models\SellerBonus;
use App\Models\ScoringResult;
use App\Models\SimilarityCheck;
use App\Models\User;
use App\Models\Contract;
use App\Models\UzTax;
use App\Services\API\V3\LoginService;
use App\Services\API\V3\Partners\BuyerService;
use App\Services\testCardService;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request as RequestPhone;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Support\Facades\Redis;

/**
 * Class BuyerController
 * @package App\Http\Controllers\Core
 *
 * Общий контроллер, работает только
 * с верифицированными покупателями
 * в рамках API функционала.
 *
 * Вся доп реализации в специализированных контроллерах - потомках!
 */
class BuyerController extends CoreController
{


    /**
     * BuyerController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->model = app(Model::class);

        //Eager load
        $this->loadWith = ['settings', 'personals', 'personals.passport_selfie'];

        // Relation для данных по ID Карте
        array_push($this->loadWith, 'personals.latest_id_card_or_passport_photo');
    }

    /**
     * @param array $params
     *
     * @return array
     */
    public function filter($params = [])
    {
        $user = Auth::user();
        $request = request()->all();

        if (isset($request['api_token']) && sizeof($params) == 0) {
            unset($request['api_token']);
            $params = $request;
        }

        $query = User::query();
        $query->whereRoleIs('buyer');

        if (!$user->hasRole('employee')) {
            if (isset($params['phone__like']) && mb_strlen($params['phone__like']) < 10) {
                $params['id'] = $user->id;
            }
        }

        if (isset($params['id'])) {
            $params['id'] = is_array($params['id']) ? $params['id'] : [$params['id']];
            $query->whereIn('id', $params['id']);
        }

        // Если задан телефон, находим конкретного
        if (isset($params['phone__like'])) {
            $buyersId = $query->where('phone', $params['phone__like'])->pluck('id')->toArray();// $query->pluck('id')->toArray() ?? [];
            $params['id'] = $buyersId;

            /*}else{
                 $buyersId = $query->pluck('id')->toArray() ?? []; */
        }

        /*if(!array_key_exists('status',$params))
            $params['status'] = 4;*/

        $items = parent::filter($params);


        if ($user->hasRole('partner')) {
            foreach ($items['result'] as &$item) {
                $item->makeHidden(array_merge(array_keys($item->getAttributes()), array_keys($item->getRelations())));
                $item->status_caption = __('user.statuslar_' . $item->status);
                $item->makeVisible(["id", "status", "doc_path", "status_caption", "email", "name", "surname", "patronymic", "phone", "personals", "settings", "debs"]);
            }
        }

        return $items;
    }


    /**
     * @param $id
     * @param array $with
     * @return Builder|\Illuminate\Database\Eloquent\Model|object
     */
    public function single($id, $with = [])
    {
        $single = parent::single($id, array_merge($this->loadWith, $with));
        $single->status_list = Config::get('test.order_status');

        foreach ($single->debts as $debt)
            $single->totalDebt += $debt->total;
        return $single;
    }



    /**
     * @OA\Get(
     *      path="/buyers/detail",
     *      operationId="user-info",
     *      tags={"User info"},
     *      summary="Info of authorized user",
     *      description="Return json",
     *      security={{"api_token_security":{}}},
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
     * @param $id
     *
     * @return array|bool|false|string
     */
    public function detail($id = null)
    {

        if ($id == null) {
            $id = Auth::id();
        }
        $buyer = $this->single($id);
        $user = Auth::user();

        if ($buyer) {
            if ($user->can('detail', $buyer)) {

                $this->result['status'] = 'success';
                $this->result['data'] = $buyer;
            } else {
                //Error: access denied
                $this->result['status'] = 'error';
                $this->result['response']['code'] = 403;
                $this->message('danger', __('app.err_access_denied'));
            }
        } else {
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message('danger', __('app.err_not_found'));
        }

        return $this->result();
    }

    /**
     * проверка статуса клиента
     *
     * @return array|bool|false|string
     */
    public function getBuyer($phone = null)
    {

        if ($phone) $phone = correct_phone($phone);

        if (!$buyer = Buyer::wherePhone($phone)->first()) {
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message('danger', __('panel/buyer.err_buyer_not_found'));
        } else {
            $link = "https://cabinet.test.uz/ru/panel/buyers/" . $buyer->id;
            $this->result['status'] = 'success';
            $this->result['data']['link'] = $link;
        }

        return $this->result;
    }

    /**
     * проверка статуса клиента
     *
     * @return array|bool|false|string
     */
    public function check_status()
    {

        $id = Auth::id();
        $buyer = $this->single($id);

        $user = Auth::user();

        if ($buyer) {
            if ($user->can('detail', $buyer)) {

                $this->result['status'] = 'success';
                $this->result['data'] = ['status' => $buyer->status, 'buyer_id' => $buyer->id, 'passport_type' => $buyer->personals->passport_type];
                if ($buyer->status == 4) {
                    //$balance = $buyer->settings->balance + $buyer->settings->personal_account;
                    $balance = $buyer->settings->balance == 0 ? 0 : $buyer->settings->balance + $buyer->settings->personal_account;
                    $balance = $balance < 0 ? 0 : $balance;
                    $this->result['data']['available_balance'] = number_format($balance, 2, ".", "");
                }
            } else {
                //Error: access denied
                $this->result['status'] = 'error';
                $this->result['response']['code'] = 403;
                $this->message('danger', __('app.err_access_denied'));
            }
        } else {
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message('danger', __('app.err_not_found'));
        }
        return $this->result();
    }

    public function addFriend(Request $request)
    {

        if ($request->friend_phone) {
            $user = Auth::user();
            $phone = correct_phone($request->friend_phone);

            if (!$user_exist = User::where('phone', $phone)->first()) {


                if (!$friend = Friends::where('phone', $phone)->where('user_id', $user->id)->first()) {
                    $friend = new Friends();
                    $friend->phone = $phone;
                    $friend->user_id = $user->id;
                    $friend->status = 0;
                    $friend->save();
                }

                $msg = __('invite_message') . ' https://' . $_SERVER['SERVER_NAME'] . '/login/invite?user_id=' . $user->id . ' ' . __('friend_register');

                [$result, $http_code] = SmsHelper::sendSms($phone, $msg);

                if (($http_code === 200) || ($result === SmsHelper::SMS_SEND_SUCCESS)) {
                    $this->result['status'] = 'success';
                    $this->result['response']['code'] = 200;
                } else {
                    $this->result['status'] = 'error';
                    $this->result['response']['code'] = 404;
                    $this->message('danger', __('app.err_not_found'));
                }
            } else {
                $this->result['status'] = 'error';
                $this->result['response']['code'] = 404;
                $this->message('danger', __('app.user_exist'));
            }

        } else {
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message('danger', __('app.phone_is_not_correct'));

        }


        return $this->result();

    }

    // лимит рассрочки пользователя в кабинете
    public function balance($id = null)
    {

        if ($id == null) {
            $id = Auth::user()->id;
        }


        if ($user = Buyer::find($id)) {

            $this->result['status'] = 'success';
            $this->result['response']['code'] = 200;
            $this->result['data'] = [
                'installment' => $user->settings->balance ?? 0,
                'deposit' => $user->settings->personal_account ?? 0,
                'all' => $user->settings ? $user->settings->balance + $user->settings->personal_account : 0,
            ];

        } else {
            $this->result['status'] = 'error';
            $this->message('danger', __('app.user_not_found'));
            $this->result['response']['code'] = 404;
        }

        return $this->result();

    }

    // платежные системы - click, payme
    public function paySystems()
    {

        $data = [];

        if ($paySystems = PaySystem::where('status', 1)->get()) {

            foreach ($paySystems as $paySystem) {
                $data[] =
                    [
                        "title" => $paySystem->title,
                        "img" => $paySystem->getImage(),
                        "url" => $paySystem->url,
                        "id" => $paySystem->id
                    ];
            }
            $this->result['status'] = 'success';
            $this->result['data'] = $data;
            $this->result['response']['code'] = 200;

        } else {
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message('danger', __('app.pay_systems_not_found'));
        }

        return $this->result();

    }

    public function cards($id = null)
    {

        if ($id == null) {
            $id = Auth::user()->id;
        }

        $data = [];

        // получить все карты пользователя
        if ($cards = Card::where('user_id', $id)->get()) {

            foreach ($cards as $card) {
                if ($card->hidden == 0) continue;  // не отдавать скрытые карты

                if ($card->token_payment) {
                    $balanceResponse = (new testCardService())->getCardBalance($card->token_payment);
                    $balance = isset($balanceResponse['balance']) && $balanceResponse['balance'] > 0 ? $balanceResponse['balance'] / 100 : 0;
                } else {
                    $balance = 0;
                }


                $data[] = [
                    "title" => EncryptHelper::decryptData($card->card_name),
                    "img" => CardHelper::getImage(EncryptHelper::decryptData($card->type)),
                    "pan" => CardHelper::getCardNumberMask(EncryptHelper::decryptData($card->card_number)),
                    "exp" => EncryptHelper::decryptData($card->card_valid_date),
                    "id" => $card->id,
                    "type" => CardHelper::checkTypeCard(EncryptHelper::decryptData($card->card_number))['name'],
                    'balance' => $balance,
                ];
            }

            $this->result['status'] = 'success';
            $this->result['data'] = $data;
            $this->result['response']['code'] = 200;

        } else {
            $this->result['status'] = 'error';
            $this->message('danger', __('app.cards_not_found'));
            $this->result['response']['code'] = 404;
        }

        return $this->result();

    }
    /*public function cardBalance(Request $request){

        if($request->card_id) {

            $user = Auth::user();

            $request->merge([
                'buyer_id' => $user->id
            ]);

            $cardController = new CardController();

            $result = $cardController->balance($request);

            $this->result['status'] = 'success';
            $this->result['data']['balance'] = $result['result']['balance'];
            $this->result['response']['code'] = 200;


        }else{
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message('danger', __('app.card_not_found'));

        }

        return $this->result();

    }*/

    /**
     * Пополнение лицевого счета с карты
     *
     * @param AddDepositRequest $request
     *
     * @return array
     */
    public function addDeposit(AddDepositRequest $request): array
    {
        $request->merge(['user_id' => $request->user->id]);
        $response = (new CardController())->adjunction($request);
        if ($response['status'] === 'success') {
            $this->result['status'] = 'success';
            $this->result['response']['code'] = 200;
            $this->message('success', __('app.payment_success'));
        } else {
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 400;
            $this->message('danger', $response['message']);
        }

        return $this->result();
    }

    // все подтвержденные договора клиента
    public function contracts($id = null)
    {

        if ($id == null) {
            $id = Auth::user()->id;
        }

        //$user = Auth::user();
        if (!$buyer = Buyer::find($id)) {
            $this->result['status'] = 'error';
            $this->result['error'] = __('api.buyer_not_found');

        } else {

            if (!$contracts = $buyer->contracts) {
                $this->result['status'] = 'error';
                $this->result['error'] = __('api.contracts_buyer_not_found');
                return $this->result();
            }

            $data = [];

            $contracts = Contract::where('user_id', $id)
                ->orderByRaw("FIELD(status, 1) desc")
                ->orderBy('created_at', 'desc')
                ->get();

            foreach ($contracts as $contract) {

                if ($contract->order->test == 1) continue; // тестовые не показываем
                $data[] = [
                    'contract_id' => $contract->id ?? null,
                    'order_id' => $contract->order_id ?? null,
                    'period' => $contract->period ?? null,
                    'remainder' => $contract->balance ?? null, // Остаток платежей для досрочного погашения
                    'current_pay' => $contract->nextPayment->balance ?? null,  // Текущий платеж
                    'next_pay' => $contract->nextPayment->payment_date ?? null,
                    'monthly_payment' => $contract->nextPayment->total ?? null,
                    'status' => $contract->status ?? null,
                    'schedule_list' => $contract->schedule ?? [],
                    'created_at' => $contract->created_at,
                    'manager_id' => $contract->company->manager_id,
                ];

            }
            $this->result = [
                "status" => 'success',
                "contracts" => $data,
                'error' => null,
            ];
        }

        return $this->result();
    }

    public function contract(ContractRequest $request)
    {
        $contract = Contract::find($request->contract_id);
        if ($contract->status !== Contract::STATUS_CANCELED) {
            if (isset($contract->uzTaxUrl) && isset($contract->url)) {
                $url = strtotime($contract->uzTaxUrl->created_at) >= strtotime($contract->url->created_at) ? $contract->uzTaxUrl->qr_code_url : $contract->url->url;
            } else {
                $url = $contract->uzTaxUrl->qr_code_url ?? ($contract->url->url ?? null);
            }
        }
        $data = [
            'contract_id' => $contract->id,
            'status' => $contract->status,
            'order_id' => $contract->order_id,
            'remainder' => $contract->balance, // Остаток платежей для досрочного погашения
            'next_pay' => @$contract->nextPayment->payment_date,
            'monthly_payment' => @$contract->nextPayment->total,
            'period' => $contract->period,       // Срок рассрочки
            "current_pay" => @$contract->nextPayment->balance,        // Текущий платеж
            "doc_pdf" => $contract->clientAct ? '/storage/' . $contract->clientAct->path : '', // текущий договор загруженый вендором
            "offer_preview" => '',  //"/storage/offerpreview/" . $contract->offer_preview, // акт который летит на телефон клиенту
            'products' => $contract->order->products,
            'doc_path' => $contract->doc_path,
            'is_allowed_online_signature' => $contract->is_allowed_online_signature,
            'manager_id' => $contract->company->manager_id,
            'url' => $url ?? null,
        ];
        $this->result = [
            "status" => 'success',
            "code" => '200',
            "contracts" => $data,
            "schedule_list" => $contract->schedule,
            'error' => null,
        ];
        return $this->result();
    }


    // досрочная оплата договора с карты или личного счета
    public function contractPay(ContractPayRequest $request)
    {
        $url = '';
        $buyer = Auth::user();

        if (isset($buyer)) {
            $input = [
                'user_id' => $buyer->id,
                'contract_id' => $request->payment['contract_id'],
                'amount' => $request->payment['total']
            ];
            if ($request->payment['type'] == 'account') {
                $url = Config::get('test.test_api_v2_prepay_free_pay');
                $input['type'] = 'ACCOUNT';
            } else if ($request->payment['type'] == 'card') {
                $url = Config::get('test.test_api_v2_prepay_free_pay_card');
                $input['card_id'] = $request->payment['card_id'];
            }
            $httpRequest = Http::withHeaders(['Content-type' => 'application/json'])->post($url, $input);
            $httpRequest->body();
            $result = $httpRequest->json();

            Log::channel('payment')->info($input);
            Log::channel('payment')->info($result);

            if ($result['code'] == -200) {
                preg_match('/"[A-z, \s]+"/', $result['messages'][0], $error);
                $error = str_replace('"', '', $error);
                $error = str_replace(' ', '_', $error);
            }
            $error = $error[0] ?? $result['messages'][0];

            if (isset($result['messages'][0]) && $result['messages'][0] == 'ok') {
                $this->result['status'] = 'success';
                $this->message('success', __('app.successfully_paid'));
            } else if (isset($result['messages'][0])) {
                $this->result['status'] = 'error';
                $this->result['response']['code'] = 400;
                $this->result['error'] = __('app.' . $error);
                $this->message('danger', __('app.' . $error));
            } else if (isset($result['status']) && $result['status'] == 500) {
                $this->result['status'] = 'error';
                $this->result['response']['code'] = 500;
                $this->result['error'] = $result['error'];
                $this->message('danger', $result['error']);
            }
            return $this->result();
        }

        $this->result['error'] = 'error';
        $this->message('danger', __('buyer_not_found'));

        return $this->result();
    }

    public function contractPayV2(ContractPayV2Request $request)
    {
        $buyer = Auth::user();
        $user = Buyer::find($buyer->id);
        $requestPhone = new RequestPhone();
        $requestPhone->merge(['phone' => correct_phone($buyer->phone)]);
        $paymentType = $request->payment_type;
        $type = $request->type;
        if ($type !== Payment::PAYMENT_REQUEST_TYPE_ACCOUNT) {
            if ($request->has('otp')) {
                $requestPhone->merge(['code' => $request->get('otp')]);
                $sms_response = LoginService::checkSmsCode($requestPhone);
                if ($sms_response['code'] === 0) {
                    $this->result['status'] = 'error';
                    $this->message('danger', __('auth.error_code_wrong'));
                    return $this->result;
                }
            } else {
                $str = EncryptHelper::decryptData($user->card->card_number);
                $card_number = '****' . substr($str, -4);
                $msg = "Hurmatli mijoz, sizning " . $card_number . " kartangiz tomonidan muddatidan oldin to'lov qilish uchun tasdiqlash kodi: :code Tel: " . callCenterNumber(2);
                $result = $this->sendSmsCode($requestPhone, true, $msg);
                return $result;
            }
        }

        $input = [
            'user_id' => $buyer->id,
            'contract_id' => $request->contract_id,
            'type' => strtoupper($type),
        ];

        switch ($paymentType) {
            case 'month':
                $url = Config::get('test.test_api_v2_prepay_month');
                break;
            case 'several-month':
                $url = Config::get('test.test_api_v2_prepay_several_month');
                $input['schedule_ids'] = $request->schedule_ids;
                break;
            case 'free-pay':
                $url = Config::get('test.test_api_v2_prepay_free_pay');
                $input['amount'] = $request->amount;
        }

        if ($type == 'card') {
            $input['card_id'] = $request->card_id;

            switch ($paymentType) {
                case 'month':
                    $url = Config::get('test.test_api_v2_prepay_month_card');
                    break;
                case 'several-month':
                    $url = Config::get('test.test_api_v2_prepay_several_month_card');
                    $input['schedule_ids'] = $request->schedule_ids;
                    break;
                case 'free-pay':
                    $url = Config::get('test.test_api_v2_prepay_free_pay_card');
                    $input['amount'] = $request->amount;
            }

            if (isset($request->otp))
                $input['otp'] = $request->otp;
        }

        $httpRequest = Http::withHeaders(['Content-type' => 'application/json'])->post($url, $input);
        $httpRequest->body();
        $response = $httpRequest->json();

        Log::channel('payment')->info($input);
        Log::channel('payment')->info($response);

        if ($httpRequest->ok()) {
            if (isset($request->otp) || $request->type === Payment::PAYMENT_REQUEST_TYPE_ACCOUNT) {
                $this->result['status'] = 'success';
                $this->result['response']['message'][] = __('payment.successfully_paid');
            } else {
                $this->result['status'] = 'success';
                $this->result['response']['message'][] = __('payment.otp_successfully_sent');
            }
        } else {
            $response = $httpRequest->object();
            $this->result['status'] = 'error';
            $this->result['response']['errors'][] = __('test_card_service/code')[$response->code] ?? __('payment.internal_server_error');
        }

        return $this->result();
    }


    // досрочное погашение договора с бонусного счета
    public function contractPayByBonus(Request $request)
    {
        $user = Auth::user();
        $contract_id = (int)$request->payment['contract_id'];
        $total = (int)$request->payment['total'];

        if ($total <= 0) {
            $this->result['status'] = 'error';
            $this->result['error'] = __('app.amount_not_fill');
            return $this->result();
        }

        if (!$buyer = Buyer::find($user->id)) {
            $this->result['status'] = 'error';
            $this->result['error'] = __('api.buyer_not_found');
            return $this->result();
        }

        // если не надо платить за договор
        if (!$contract = Contract::where('id', $contract_id)->whereIn('status', [1, 4, 3])->get()) {  // 1 - активен, 5 - отменен, 9 - оплачен, 0 - не активен, 4,3 - просрочен
            $this->result['status'] = 'error';
            $this->result['error'] = __('api.not_found');
            return $this->result();
        }


        if ($total && $total > 0) {

            if (isset($buyer->settings)) {  // если на БС 0, вернуть ошибку
                if ($buyer->settings->zcoin <= 0) {
                    $errors = true;
                    $this->result['status'] = 'error';
                    $this->message('error', __('order.txt_repayment_error'));
                    return $this->result;

                } elseif ($buyer->settings->zcoin < $total) {
                    $total = $buyer->settings->zcoin;  // спишем сколько есть на БС
                }
                // пополнение на ЛС
                $buyer->settings->personal_account += $total;
                $buyer->settings->zcoin -= $total;
                if ($buyer->settings->zcoin < 0) $buyer->settings->zcoin = 0; // временная коррекция
                $buyer->settings->save();

                $payment = new Payment;
                $payment->type = 'user_auto';  // списание c отрицательной суммой с бонусного счета
                $payment->amount = -1 * $total;
                $payment->user_id = $buyer->id;
                $payment->payment_system = 'Paycoin';
                $payment->save();

                $payment = new Payment;
                $payment->type = 'user';  // пополнение
                $payment->amount = $total;
                $payment->user_id = $buyer->id;
                $payment->payment_system = 'Paycoin';
                $payment->save();

                $amount = 0;
                $sum = 0;  // какая сумма нам понадобилась для закрытия (чтобы вычесть из $contract->balance)
                foreach ($contract->schedule as $schedule) {
                    if ($schedule->status == 1) continue;
                    if ($total > 0) {
                        // если месяц к оплате или в просрочке, резервируем деньги на ЛС, месяц не закрываем, транзакцию не создаем, спишется с крона
                        $payment_date = strtotime($schedule->payment_date);
                        $now = strtotime(Carbon::now()->format('Y-m-d 23:59:59'));
                        if ($payment_date <= $now) continue;

                        if ($total >= $schedule->balance) {
                            $amount = $schedule->balance; // списали за месяц
                            $total -= $schedule->balance;
                            $schedule->balance = 0;   // ост долга
                            $schedule->status = 1;
                            $schedule->paid_at = time();

                            $buyer->settings->balance += $schedule->price;  // вернуть лимит

                        } else {
                            $amount = $total; // списали за месяц
                            $schedule->balance -= $total;  // ост долга
                            $total = 0;
                        }

                        $buyer->settings->balance += $schedule->price;  // вернуть лимит
                        $buyer->settings->save();  // вернем лимит, если не пропустили месяц

                        $sum += $amount;   // какая сумма нам понадобилась для закрытия
                        $schedule->save();

                        if ($amount > 0) {
                            // записать в payment списание c ЛС
                            $transaction = new Payment();
                            $transaction->contract_id = $contract->id;
                            $transaction->order_id = $contract->order->id;
                            $transaction->schedule_id = $schedule->id;
                            $transaction->user_id = $buyer->id;
                            $transaction->amount = $amount;
                            $transaction->type = 'user_auto';
                            $transaction->payment_system = 'ACCOUNT';
                            $transaction->status = 1;
                            $transaction->save();
                        }
                    }
                }

                $contract->balance -= $sum;
                if ($contract->balance < 0) $contract->balance = 0; // временная коррекция
                if ($contract->balance == 0) $contract->status = 9;
                $contract->save();

            } else {
                $errors = true;
                $this->result['status'] = 'error';   // если нет settins
                return $this->result;
            }


        } else {
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message('danger', __('app.amount_not_fill'));
        }

        return $this->result();
    }

    public function paymentsTypes()
    {

        if ($payments = Payment::select('type')->groupBy('type')->get()) {
            return $payments;
        }

        $this->result['status'] = 'error';
        $this->result['error'] = __('api.payments_not_found');
        return $this->result();

    }


    // список всех оплат
    public function payments($id = null)
    {
        if (!$id) $id = Auth::user()->id;

        $data = [];

        if (!$payments = Payment::where('user_id', $id)->whereNotIn('status', [5, 7])->orderBy('created_at', 'desc')->get()) {
            $this->result['status'] = 'error';
            $this->result['error'] = __('api.payments_not_found');
            return $this->result();
        } else {
            foreach ($payments as $payment) {
                $data[] = [
                    'card_id' => $payment->card_id, // карта
                    'payment_id' => $payment->id,
                    'contract_id' => $payment->contract_id == null ? SellerBonus::where(['seller_id' => $id, 'payment_id' => $payment->id])->pluck('contract_id')->first() : $payment->contract_id,
                    'amount' => $payment->amount,
                    'type' => $payment->type,
                    'payment_system' => $payment->payment_system,
                    'date' => date("Y-m-d", strtotime($payment->created_at)),
                    'time' => date("H:i:s", strtotime($payment->created_at)),
                    'status' => $payment->status,
                ];
            }

            $this->result = [
                "status" => 'success',
                "payments" => $data,
                'error' => null,
            ];
        }

        return $this->result();


    }

    // детали одного платежа
    public function paymentDetail(Request $request)
    {
        if (isset($request->buyer_id)) {
            $buyer_id = $request->buyer_id;
        } else {
            $buyer_id = Auth::user()->id;
        }
        //$user = Auth::user();

        if (!$request->has('id')) {
            $this->result['status'] = 'error';
            $this->result['error'] = __('api.empty_payment_id');
            return $this->result();;
        }

        if (!$payment = Payment::where('id', $request->id)->where('user_id', $buyer_id)->first()) {
            $this->result['status'] = 'error';
            $this->result['error'] = __('api.payments_not_found');
        } else {
            $data = [
                'payment_id' => $payment->id,
                'contract_id' => $payment->contract_id,
                'order_id' => $payment->order_id,
                'amount' => $payment->amount,
                'type' => $payment->type,
                'payment_system' => $payment->payment_system,
                'created_at' => $payment->created_at
            ];


            $this->result = [
                "status" => 'success',
                "payment" => $data,
                'error' => null,
            ];

        }

        return $this->result();


    }

    public function catalog()
    {

        $data = [];

        if ($categories = CatalogCategoryController::tree(0, [], true)) { // $catalog = Catalog::where('status',1)->orderBy('pos')->get()

            foreach ($categories as $cat) {
                //s return $cat;

                $data[] = array_merge([
                    'id' => $cat->id,
                    'img' => isset($cat->image->path) ? url('/storage/' . $cat->image->path) : ''  // ссылка на изображение категории
                ], $cat['locales']);

            }

            $this->result['status'] = 'success';
            $this->result['data'] = $data;
            $this->result['response']['code'] = 200;
        } else {
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message('danger', __('app.catalog_not_found'));

        }


        return $this->result();

    }

    public function catalogPartners(Request $request)
    {
        $data = [];

        if ($request->catalog_id) {

            $catalogPartners = CatalogPartners::where('catalog_id', $request->catalog_id)->pluck('partner_id')->toArray();

            if ($companies = Company::with('logo')->where('status', 1)->whereIn('id', $catalogPartners)->orderBy('name')->get()) {

                foreach ($companies as $company) {
                    $data[] = [
                        'title' => $company->name,
                        'img' => isset($company->logo->path) ? url('/storage/' . $company->logo->path) : '', //getImage(),
                        'id' => $company->id
                    ];
                }

                $this->result['status'] = 'success';
                $this->result['data'] = $data;
                $this->result['response']['code'] = 200;

            } else {
                $this->result['status'] = 'error';
                $this->result['response']['code'] = 404;
                $this->message('danger', __('app.catalog_not_found'));
            }

        } else {
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message('danger', __('app.catalog_id_not_fill'));
        }

        return $this->result();
    }

    public function catalogPartner(Request $request)
    {

        $data = [];

        if ($request->partner_id) {
            if ($filials = Company::where('status', 1)->where('parent_id', $request->partner_id)->get()) {

                foreach ($filials as $filial) {
                    $data[] = [
                        'fillial_id' => $filial->id, // Номер филиала партнера в системе test
                        'title' => $filial->title, // Имя партнера
                        'address' => $filial->address, // Адресс партнера
                        'img' => $filial->getImage(), // Лого партнера
                        'phone' => $filial->phone, // номер телефона партнера
                    ];
                }

                $this->result['status'] = 'success';
                $this->result['data'] = $data;
                $this->result['response']['code'] = 200;

            } else {
                $this->result['status'] = 'error';
                $this->result['response']['code'] = 404;
                $this->message('danger', __('app.partner_not_found'));
            }

        } else {
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message('danger', __('app.partner_id_not_fill'));
        }

        return $this->result();
    }

    // сервисы для оплаты с бонусами -
    public function payServices()
    {

        $data = [];

        if ($services = PayService::where('status', 1)->get()) {

            foreach ($services as $service) {

                $data[] = [
                    'id' => $service->id,
                    'title' => $service->name,
                    'type' => $service->type == 1 ? 'mobile' : 'other', // тип
                    'img' => $service->getImgAttribute(), // logo
                ];

            }

            $this->result['status'] = 'success';
            $this->result['data'] = $data;
            $this->result['response']['code'] = 200;

        } else {

            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message('danger', __('app.services_not_found'));

        }

        return $this->result();

    }

    // Upay оплата сервиса
    public function payServicePayment(Request $request)
    {

        $user = Auth::user();

        $errors = [];
        // if(empty($request->service_id)) $errors[] = __('app.service_id_not_fill'); //платежный сервис
        if (empty($request->amount)) $errors[] = __('app.amount_not_fill'); // sum
        if (empty($request->account)) $errors[] = __('app.account_not_fill'); // телефон или логин платежного сервиса
        if ($request->amount < 1000) {   // присылать в сумах
            $errors[] = __('app.amount_limit_danger'); // sum
        }

        if ($buyer = Buyer::find($user->id)) {
            if (!$errors) {

                $request->merge(['user_id' => $user->id]);

                // проверка на черный список
                if ($buyer->black_list) {
                    $this->result['status'] = 'error';
                    $this->result['response']['code'] = 404;
                    $this->message('danger', __('billing/order.err_black_list'));
                    return $this->result;
                }

                if ($buyer->settings->zcoin >= $request->amount) {  // если хватает баллов

                    $upay = new ZpayController();
                    $this->result = $upay->pay($request);
                    //$this->result['status'] = 'success';
                    $this->result['response']['code'] = 200;

                    return $this->result();
                } else {
                    $errors[] = __('app.balls_not_enough'); // не хватает баллов для оплаты
                }

            }

            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            foreach ($errors as $error) {
                $this->message('danger', $error);
            }

        }

        return $this->result();

    }

    // баланс бонусов клиента
    public function bonusBalance()
    {
        $user = Auth::user();

        if ($buyer = Buyer::find($user->id)) {

            $this->result['status'] = 'success';
            $this->result['response']['code'] = 200;
            $this->result['data'] = [
                "bonus" => @$buyer->settings->zcoin,
            ];

            return $this->result();
        }

        $this->result['status'] = 'error';
        $this->result['response']['code'] = 404;
        return $this->result();

    }

    // вывод бонусов на карту
    public function bonusToCard(Request $request)
    {
        $user = Auth::user();

        if ($buyer = Buyer::find($user->id)) {

            //get bonus balance
            $bonus_balance = $buyer->settings->zcoin;

            if ((int)$request->input('bonus_sum_request') < 1000) { //сумма для перевода минимум 1000сум

                $this->result['status'] = 'error';
                $this->result['response']['code'] = 200;
                $this->result['response']['message'] = [
                    __('panel/buyer.bonus_min_1000')
                ];
                return $this->result();
            }

            $bonus_to_debit = (int)$request->input('bonus_sum_request') + (((int)$request->input('bonus_sum_request') * 1) / 100);

            //округляем до сотых в меньшую сторону (напр. если 1000.5996, округляем 1000.59) и сравниваем с балансом
            if ($bonus_balance < floor($bonus_to_debit * 100) / 100) {

                $this->result['status'] = 'error';
                $this->result['response']['code'] = 200;
                $this->result['response']['message'] = [
                    __('panel/buyer.bonus_not_enough_min_comission')
                ];
                return $this->result();
            }

            if ($buyer->cards->isNotEmpty()) {

                foreach ($buyer->cards as $key => $item) { //check all buyer cards

                    if ($request->input('card_id') && ($item->id == (int)$request->input('card_id'))) {

                        //generate SMS code and message
                        $code = SmsHelper::generateCode();

                        // TODO: Определить правильный ли перевод
                        $msg = "Kod: " . $code . ". resusnasiya.uz kartangizga pul o'tkazishga ruxsat so'radi "
                            . CardHelper::getCardNumberMask(EncryptHelper::decryptData($item->card_number))
                            . ". Tel: " . callCenterNumber(2);
//                        $msg = 'Kod: ' . $code . '. resusnasiya.uz zaprosil razresheniye perevoda na kartu ' . $card_number . '. Tel: ' . callCenterNumber(2);
                        [$result, $http_code] = SmsHelper::sendSms(correct_phone($buyer->phone), $msg);

                        if (($http_code === 200) || ($result === SmsHelper::SMS_SEND_SUCCESS)) {

                            //set SMS code in Redis (check timeout Redis)
                            Redis::set($buyer->phone . '_bonus_to_card', $code);

                            $this->result['status'] = 'success';
                            $this->result['response']['code'] = 200;
                            $this->result['response']['message'] = [
                                __('panel/buyer.bonus_sms_sent_success')
                            ];
                        } else {

                            $this->result['status'] = 'error';
                            $this->result['response']['code'] = 404;
                            $this->result['response']['message'] = [
                                __('panel/buyer.bonus_sms_service_unavailable')
                            ];
                        }
                        return $this->result();
                    }
                }

            } else {

                $this->result['status'] = 'error';
                $this->result['response']['code'] = 404;
                $this->result['response']['message'] = [
                    __('panel/buyer.bonus_user_no_card')
                ];
                return $this->result();
            }

        } else {

            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->result['response']['message'] = [
                __('panel/buyer.bonus_user_not_found')
            ];
            return $this->result();
        }
    }

    public function bonusToCardConfirm(Request $request)
    {
        $user = Auth::user();
        $buyer = Buyer::find($user->id);

        if ($buyer->id != $request->input('buyer_id')) {
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->result['response']['message'] = [
                __('panel/buyer.bonus_invalid_buyer')
            ];
            return $this->result();
        }

        $cardArr = [];
        foreach ($buyer->cards as $itemCard) {
            $cardArr[] = $itemCard->id;
        }

        if (!in_array((int)$request->input('card_id'), $cardArr)) {
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->result['response']['message'] = [
                __('panel/buyer.bonus_invalid_card')
            ];
            return $this->result();
        }

        if ($request->input('card_id') && $request->input('buyer_id') && $request->input('amount')) {

            if ($request->input('sms_code') && ($request->input('sms_code') == Redis::get($buyer->phone . '_bonus_to_card'))) {

                $input = "?cardId=" . $request->input('card_id') . "&userId=" . $request->input('buyer_id') . "&amount=" . ((int)$request->input('amount') * 100);
                $curl = curl_init(Config::get('test.bonus_to_card_url') . $input);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");

                $result = curl_exec($curl);
                $result = json_decode($result, JSON_UNESCAPED_UNICODE);

                if ($result['message'] == 'OK') {

                    $this->result['status'] = 'success';
                    $this->result['response']['code'] = 200;
                    $this->result['response']['message'] = [
                        __('panel/buyer.bonus_to_card_success')
                    ];
                    return $this->result();

                } elseif ($result['message'] == 'NOT_ENOUGH_BAL') {

                    $this->result['status'] = 'error';
                    $this->result['response']['code'] = 404;
                    $this->result['response']['message'] = [
                        __('panel/buyer.bonus_not_enough_bal')
                    ];
                    return $this->result();

                } elseif ($result['message'] == 'CONTRACTS_CONFLICT') {

                    $this->result['status'] = 'error';
                    $this->result['response']['code'] = 404;
                    $this->result['response']['message'] = [
                        __('panel/buyer.bonus_contract_conflict')
                    ];
                    return $this->result();

                } elseif ($result['message'] == 'UNEXPECTED_ERROR') {

                    $this->result['status'] = 'error';
                    $this->result['response']['code'] = 404;
                    $this->result['response']['message'] = [
                        __('panel/buyer.bonus_unexpected_error')
                    ];
                    return $this->result();
                }

            } else {

                $this->result['status'] = 'error';
                $this->result['response']['code'] = 404;
                $this->result['response']['message'] = [
                    __('panel/buyer.bonus_sms_not_correct')
                ];
                return $this->result();
            }

        } else {

            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->result['response']['message'] = [
                __('panel/buyer.bonus_lack_params')
            ];
            return $this->result();
        }

    }

    // баллы для оплаты с помощью сервиса UPAY
    public function paycoins()
    {

        $user = Auth::user();

        if ($buyer = Buyer::find($user->id)) {

            $bonus = [];

            $bonus[] = [
                "type" => 'sale',
                "level" => $buyer->settings->paycoin_sale,
            ];
            $bonus[] = [
                "type" => 'month',
                "level" => $buyer->settings->paycoin_month,
            ];
            $bonus[] = [
                "type" => 'limit',
                "level" => $buyer->settings->paycoin_limit // + $buyer->settings->limit,
            ];

            $limit = Config::get('test.paycoin.limit');
            $sale = Config::get('test.paycoin.sale');

            $this->result['status'] = 'success';
            $this->result['response']['code'] = 200;
            $this->result['data'] = [
                "paycoin" => $buyer->settings->paycoin,
                'limit' => (1 + ($buyer->settings->paycoin_limit * $limit) / 100) * $buyer->settings->limit, // общий лимит
                'month' => $buyer->settings->paycoin_month + $buyer->settings->period, // всего месяцев рассрочки
                'sale' => $buyer->settings->paycoin_sale * $sale, // общая скидка
                "items" => $bonus
            ];

            return $this->result();

        }

        $this->result['status'] = 'error';
        $this->result['response']['code'] = 404;

        return $this->result();

    }

    public function paycoinBalance()
    {
        $user = Auth::user();

        if ($buyer = Buyer::find($user->id)) {

            $this->result['status'] = 'success';
            $this->result['response']['code'] = 200;
            $this->result['data'] = [
                'balance' => $buyer->settings->paycoin
            ];

            return $this->result();

        }

        $this->result['status'] = 'error';
        $this->result['response']['code'] = 404;

        return $this->result();

    }

    public function paycoinPay(Request $request)
    {

        $user = Auth::user();

        if ($buyer = Buyer::find($user->id)) {

            if ($buyer->settings->paycoin >= 30) { // если на балансе у клиента хватает баллов

                // поиск нужной категории для покупки
                $this->result['response']['errors'] = '';

                $limit = 0;

                // покупка выбранного уровня
                switch ($request->type) {
                    case 'sale': // скидки
                        if ($buyer->settings->paycoin_sale < 3) {
                            $buyer->settings->paycoin_sale += 1;
                            $buyer->settings->paycoin -= 30;
                            $buyer->settings->save();
                            $level = $buyer->settings->paycoin_sale;
                        } else {
                            $this->result['response']['errors'] = 'Current Level is max!';
                        }

                        break;
                    case 'month': // скидки
                        if ($buyer->settings->paycoin_month < 3) {
                            $buyer->settings->paycoin_month += 1;
                            $buyer->settings->paycoin -= 30;
                            $buyer->settings->save();
                            $level = $buyer->settings->paycoin_month;
                        } else {
                            $this->result['response']['errors'] = 'Current Level is max!';
                        }

                        break;
                    case 'limit': // лимит
                        if ($buyer->settings->paycoin_limit < 3) {

                            $limit = Config::get('test.paycoin.limit') / 100;
                            $limit = $limit * $buyer->settings->limit;

                            $buyer->settings->paycoin_limit += 1;
                            $buyer->settings->paycoin -= 30;

                            // сразу увеличить лимит пользователя
                            //$buyer->settings->limit += $limit; // увеличиваем лимит с учетом коэфф
                            $buyer->settings->balance += $limit; // увеличиваем баланс на сумму коэфф
                            //
                            $buyer->settings->save();
                            $level = $buyer->settings->paycoin_limit;
                        } else {
                            $this->result['response']['errors'] = 'Current Level is max!';
                        }

                        break;

                    default:
                        $this->result['response']['errors'] = 'Type not found!';

                }

                if ($this->result['response']['errors'] == '') { // до 3 го уровня
                    $this->result['status'] = 'success';
                    $this->result['response']['code'] = 200;
                    $this->result['data'] = [
                        'paycoin' => $buyer->settings->paycoin,
                        'type' => $request->type,
                        'level' => $level
                    ];

                    return $this->result();

                }


            } else {

                $this->result['response']['errors'] = 'Not enough Paycoin!';

            }

        }

        $this->result['status'] = 'error';
        $this->result['response']['code'] = 404;

        return $this->result();

    }




    // оплата с помощью бонусов
    /* public function bonusPay(){

        $this->result['status'] = 'success';
        $this->result['response']['code'] = 200;

        return $this->result();

    } */


    public function notify()
    {

        $user = Auth::user();

        if ($user) {
            $this->result['status'] = 'success';
            $this->result['data'] = $user->notifications;
        } else {
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message('danger', __('auth.error_user_not_found'));
        }

        return $this->result();
    }

    public function notifyDetail(Request $request)
    {

        $user = Auth::user();

        if ($user) {

            if ($notify = $user->notifications->where('id', $request->id)->first()) {

                if (!$notify->read_at) {
                    $notify->read_at = time();
                    $notify->save();
                }

                $this->result['status'] = 'success';
                $this->result['data'] = $notify;

            } else {
                $this->result['status'] = 'error';
                $this->result['response']['code'] = 404;
                $this->message('danger', 'Notify not found! ' . $request->id);
            }


        } else {
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message('danger', __('auth.error_user_not_found'));
        }

        return $this->result();
    }


    // подать заявку в KATM
    public function katmScoring(Request $request)
    {
        $user = Buyer::find($request->buyer_id);

        if ($user->status == 4) {
            return [
                'response' => [
                    'data' => [
                        'status' => 'error',
                        'mib' => 'error'
                    ],
                    'errors' => 'Рескоринг не требуется. Пользователь уже активен!'
                ]
            ];
        }

        // сохраняем данные в user
        $result = KatmHelper::registerKatm($user, $request);

        if ($result['status'] == 'error') {
            return [
                'response' => [
                    'data' => [
                        'status' => 'error',
                        'mib' => 'error',
                        'errors' => __('katm.mib_false')

                    ],
                    'errors' => __('katm.mib_false')
                ]
            ];
        }

        /*
         данные из метода getClientAddress
        array:2 [▼
           "status" => "success"
           "data" => array:8 [▼
             "result" => "05000"
             "resultMessage" => "Successful"
             "country" => "ЎЗБЕКИСТОН"
             "region" => "ТОШКЕНТ ШАҲРИ"
             "district" => "ШАЙХОНТОХУР ТУМАНИ"
             "address" => "ИПАКЧИ МФЙ, КИЧИК ҲАЛКА ЙЎЛИ КЎЧАСИ,  uy:60А xonadon:44"
             "regDate" => "2017-09-15T00:00:00"
             "cadastre" => "10:10:07:03:03:5001:0001:044"
           ]
         ]*/

        if ($user->personals) {

            $pnfl = EncryptHelper::encryptData($user->personals->pinfl) ?? null;

            // прописка
            $address = KatmHelper::getClientAddress($pnfl);

            if (!$user_addresses = BuyerAddress::where('user_id', $user->id)->where('type', 'residental')->first()) {  // если нет, создаем
                $user_addresses = new BuyerAddress();
                $user_addresses->user_id = $user->id;
            }
            $user_addresses->address = $address['data']['address'] ?? null;

            // $user_addresses->country  = $address['data']['country'] ?? null; // такого поля в бд нет
            // $user_addresses->region  = $address['data']['region'] ?? null;  // тут цифры должны быть
            // $user_addresses->area  = $address['data']['district'] ?? null;  // тут цифры должны быть

            $user_addresses->save();

            $user->personals->pinfl = EncryptHelper::encryptData($request->pinfl);
            $user->personals->passport = EncryptHelper::encryptData($request->passport);
            $user->personals->passport_number_hash = md5($request->passport); // 09.06.2021

            $user->save();
        }

        $result = KatmHelper::decodeResponse($result);

        $this->result['status'] = $result['status'];
        $this->result['response']['message'] = $result['message'];
        $this->result['response']['errors'] = $result['errors'] ?? null;

        return $this->result();

    }

    // получить ответ от КАТМ
    public function katmReport(Request $request)
    {

        Log::channel('katm')->info('KATM REPORT NEW');

        $user_id = $request->buyer_id;

        // получаем данные из БД
        $katm_info = KatmHelper::getKatmInfo($user_id);

        $this->result['status'] = 'success';
        $this->result['response']['code'] = 200;

        switch ($katm_info['status']) {
            case 0: // не прошел
                $this->result['data']['katm_status'] = 0;
                break;
            case 1: //  прошел
                $this->result['data']['katm_status'] = 1;
                break;
            case 2: // сервер не доступен, либо клиент не найден!
            default:

                $result = KatmHelper::getKatm(KatmHelper::config(), $user_id);
                $result = KatmHelper::decodeResponse($result);
                $this->result['status'] = $result['status'];
                $this->result['response']['message'] = $result['message'] ?? '';
                $this->result['data']['katm_status'] = 2;

                break;

        }

        return $this->result();

    }


    public function katmAddress(Request $request)
    {

        Log::info('KATM - address');
        $user = Buyer::find($request->buyer_id);
        $pinfl = isset($user->personals->pinfl) ? EncryptHelper::decryptData($user->personals->pinfl) : false;

        if (!$pinfl) {
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            return $this->result();
        }

        $result = KatmHelper::getClientAddress($pinfl);

        Log::info($result);

        if ($result['status'] == 'success') {
            $this->result['response']['code'] = 200;
            $this->result['data'] = $result['data'];
        } else {
            $this->result['response']['code'] = 404;
        }
        $this->result['status'] = $result['status'];

        return $this->result();


    }

    // проверка платежеспособности клиента сервис RoyxatCredits
    public function checkCredit(Request $request)
    {

        if ($request->has('passport') && !empty($request->passport)) {

            $result = RoyxatHelper::check($request->passport);
            Log::info('royxat result ' . $request->passport);
            Log::info($result);

            // здесь хранится история запросов по 1 на каждого юзера
            // перезаписывается, остается последний
            if (!$royxat = RoyxatCredits::where('user_id', $request->buyer_id)->first()) {
                $royxat = new RoyxatCredits();
            }
            $royxat->user_id = $request->buyer_id;
            $royxat->data = json_encode($result['data'], JSON_UNESCAPED_UNICODE);

            if ($result['status'] === 'success') {

                Log::info('royxat result success ' . $request->passport . ' status: ' . $result['status']);
                Log::info($result);

                $this->result['response']['code'] = 200;
                $royxat->status = 1;

            } else {

                Log::info('royxat result ERROR ' . $request->passport);
                Log::info($result);

                if ($result['status'] === 500) { // сервер не доступен, пропускаем
                    //$this->result['response']['errors'] = 'Royxat server error!';
                    $result['status'] = 'success';
                    $this->result['response']['code'] = 200;
                    $this->result['response']['errors'] = null;
                    $royxat->status = 2;

                } elseif ($result['status'] === 'trabl') { // не пройден

                    $this->result['response']['errors'] = __('panel/buyer.royxat_problem');
                    $royxat->status = 0;

                }

                $this->result['response']['code'] = 404;
            }
            $this->result['status'] = $result['status'] === 'success' ? 'success' : 'error';

            $royxat->save();

            return $this->result();

        }
        $this->result['status'] = 'error';
        $this->result['response']['code'] = 404;
        $this->result['response']['errors'] = 'Passport incorrect';
        return $this->result();
    }

    // проверка пользователя для royxat запросов
    public function checkPayments(Request $request)
    {
        $buyer_personal = null;

        if ($request->has('passport')) {
            $passport_hash = md5($request->passport);
            $buyer_personal = BuyerPersonal::where('passport_number_hash', $passport_hash)->first();

        } elseif ($request->has('pinfl')) {
            $pinfl_hash = md5($request->pinfl);
            $buyer_personal = BuyerPersonal::where('pinfl_hash', $pinfl_hash)->first();

        }

        if ($buyer_personal) {

            $payments = ContractPaymentsSchedule::where('user_id', $buyer_personal->user_id)->where('status', 4)->get();

            $data = [];

            foreach ($payments as $payment) {

                $data[] = [                                      // ID пользователя на платформе;
                    "credit_id" => $payment->contract_id,                      // Номер кредита;
                    "amount" => $payment->contract->total,                       // Сумма договора;
                    "duration" => $payment->contract->period,                       // Период рассрочки;
                    "contract_date" => $payment->contract->confirmed_at,       // Дата договора;
                    "overdue_days" => intval((time() - strtotime($payment->payment_date)) / 86400),                               // Сколько дней в просрочке;
                    'payment_date' => $payment->payment_date,
                    //'pt1' => time(),
                    //'pt2' => strtotime( $payment->payment_date )
                ];
            }

            if (count($data) > 0) {
                $this->result['status'] = 'success';
                $this->result['data']['user_id'] = $buyer_personal->user_id;
                $this->result['data']['passport'] = EncryptHelper::decryptData($buyer_personal->passport_number); // Серия паспорта;
                $this->result['data']['name'] = $buyer_personal->buyer->firstname . ' ' . $buyer_personal->buyer->secondname;            // ФИО;
                $this->result['data']['items'] = $data;

            } else {
                $this->result = [
                    "status" => 'error',
                    "response" => ['code' => '404'],
                    "message" => 'Client not found',
                ];
            }

        } else {
            $this->result = [
                "status" => 'error',
                "code" => '404',
                "message" => 'Client not found!',
            ];

        }

        return $this->result();

    }

    // 04.06 - модерация KYC оператором
    public function kycModerate(Request $request)
    {

        if ($request->has('buyer_id') && !empty($request->buyer_id)) {

            if ($buyer = Buyer::where('id', $request->buyer_id)/*->where('kyc_status',0)*/ ->first()) {

                // меняем kyc статус покупателя
                $buyer->kyc_status = User::KYC_STATUS_EDIT;
                $buyer->kyc_id = Auth::user()->id;
                $buyer->save();

                $msg = 'Модерация клиента <b>' . $buyer->id . "</b>\n" . 'Редактирует: <b>' . Auth::user()->name . ' ' . Auth::user()->surname . '</b>';
                TelegramHelper::send($msg);

                // добавляем в историю запись KYC оператора
                KycHistory::insertHistory($buyer->id, User::KYC_STATUS_EDIT, User::KYC_STATUS_EDIT);

                $this->result['status'] = 'success';
                $this->result['response']['code'] = 200;
                $this->result['response']['data']['kyc_status'] = User::KYC_STATUS_EDIT;

                return $this->result();
            } else {
                $this->result['status'] = 'error';
                $this->result['response']['code'] = 404;
                $this->result['response']['errors'] = 'Buyer not found!'; //'Buyer state is not NEW!';
                return $this->result();
            }

        }

        $this->result['status'] = 'error';
        $this->result['response']['code'] = 404;
        $this->result['response']['errors'] = 'Buyer not found!';
        return $this->result();
    }

    public function scoringReport($id, $reportID)
    {
        $buyer = Buyer::find($id);
        if (!$buyer) {
            return BuyerController::makeErrorResponse(__('Покупатель не найден'));
        }
        $scoring = $buyer->scoringResult->last();
        $scoringMini = $buyer->scoringResultMini->last();

        if ($scoring && $scoring->is_ml2_scoring === 0) {
            return view('panel.buyer.scoring-report', ['scoringResult' => $scoring, 'report' => GradeScoring::scoringStateReport($scoring)]);
        }

        if (($scoring && $scoring->is_ml2_scoring === 1) || $scoringMini) {
            return view('panel.buyer.scoring-report.main', ['report' => GradeScoring::scoringNewStateReport($scoring, $scoringMini)]);
        }
        return 'Отчёт отсутствует';
    }

    public function report($id, $reportID)
    {
        $buyer = Buyer::find($id);
//        if (!$buyer) {
//            return BuyerController::makeErrorResponse(__('Покупатель не найден'));
//        }
        $katm = $buyer->katmDefault()->find($reportID);
        if ($katm) {
            $reportResponse = new KatmResponseReport($katm->response);
            $report = KatmReportLibs::replaceKeyToTitle($reportResponse->report());

            return view('panel.buyer.katm', ['report' => $report]);
        }
        return 'Отчёт отсутствует';
    }

    static private function makeErrorResponse(string $message): array
    {
        return [
            'status' => 'error',
            'errors' => [
                $message,
            ]
        ];
    }

    public function initScoring(BuyerPersonalDataRequest $request)
    {
        try {
            $buyer = Buyer::find($request->buyer_id);

            if (!$buyer) {
                return BuyerController::makeErrorResponse(__('Покупатель не найден'));
            }

            $scoringResult = $buyer->scoringResult->last();

            if($scoringResult && $scoringResult->total_state == ScoringResult::STATE_AWAIT_RESPONSE){
                if($buyer->settings->mini_limit > 0){
                    return BuyerController::makeErrorResponse(__('Проходит заявка на получение основного лимита'));
                }else{
                    return BuyerController::makeErrorResponse(__('Проходит заявка на получение мини лимита'));
                }
            }

            if (!$scoringResult) {
                $isNeedToMakeNewScoringResult = true;
            } else {
                $isNeedToMakeNewScoringResult = !(Carbon::parse($scoringResult->created_at)->format('Y-m') == Carbon::now()->format('Y-m'));
            }

            if (!$isNeedToMakeNewScoringResult) {
                if ($scoringResult->isSuccess() && $buyer->status == User::KYC_STATUS_VERIFY) {
                    return BuyerController::makeErrorResponse(__('Покупатель прошёл верификацию'));
                }

                /*if ($scoringResult->issetInitiator() && !$scoringResult->isTimeOver()) {
                    return BuyerController::makeErrorResponse(__('Покупатель обрабатывается. Попробуйте через 5 минут.'));
                }*/

            }

            $buyer->region = $request->region_id;
            $buyer->local_region = $request->local_region_id;
            $buyer->name = upFirstLetter($request->first_name);
            $buyer->surname = upFirstLetter($request->last_name);
            $buyer->patronymic = upFirstLetter($request->patronymic);
            $buyer->gender = $request->gender;
            $buyer->birth_date = Carbon::parse($request->birth_date)->format('Y-m-d');
            $buyer->save();

            $personalData = $buyer->personalData;

            if ($request->mrz) {
                $mrz_head = 'P<UZB' . $request->first_name . '<<' . $request->last_name;
                $mrz_head .= str_repeat('<', 44 - strlen($mrz_head));
                $mrz = strtoupper($mrz_head . $request->mrz);
                $personalData->mrz = $mrz;
            }

            $personalData->passport_number = EncryptHelper::encryptData($request->passport);
            $personalData->passport_number_hash = md5($request->passport);
            $personalData->pinfl = EncryptHelper::encryptData($request->pinfl);
            $personalData->pinfl_hash = md5($request->pinfl);
            $personalData->birthday = EncryptHelper::encryptData($request->birth_date);
            $personalData->passport_date_issue = EncryptHelper::encryptData($request->passport_date_issue);
            $personalData->passport_expire_date = EncryptHelper::encryptData($request->passport_expire_date);
            $personalData->passport_type = $request->passport_type;
            $personalData->birthday_open = Carbon::make($request->birth_date) ? Carbon::make($request->birth_date)->format('Y-m-d') : null;
            $personalData->passport_date_issue_open = Carbon::make($request->passport_date_issue) ? Carbon::make($request->passport_date_issue)->format('Y-m-d') : null;
            $personalData->passport_expire_date_open = Carbon::make($request->passport_expire_date) ? Carbon::make($request->passport_expire_date)->format('Y-m-d') : null;
            $personalData->save();

            if ($personalData->passport_type === null) {
                return BuyerController::makeErrorResponse(__('Тип паспорта не обнаружен'));
            }

            if (GradeScoring::findDuplicatedBuyersByPinfl($request->pinfl, $buyer->id) > 0) {
                $buyer->status = User::KYC_STATUS_BLOCKED;
                $buyer->save();

                KycHistory::insertHistory($buyer->id, User::KYC_STATUS_BLOCKED, User::KYC_STATUS_SCORING_PINFL);
                return BuyerController::makeErrorResponse(__('Покупатель с таким ПИНФЛ уже зарегистрирован. Покупатель заблокирован'));
            }

            GradeScoring::mustSendSmsToUser($buyer->id, $request->send_sms);

            $isAuto = false;

            $scoringResultMini = $buyer->scoringResultMini->last();

            if($scoringResultMini && $scoringResultMini->total_state == ScoringResult::STATE_USER_INFO_SUCCESS){
                GradeScoring::initScoring($request->buyer_id, $request->katm_method !== 'manual');
            }else{
                GradeScoring::initMiniScoring($buyer->id, false);
            }
            //GradeScoring::initScoring($request->buyer_id, $isAuto);

        } catch (testException $e) {
            $data = [
                'url' => $e->urlText(),
                'request' => $e->requestArray(),
                'response' => $e->responseArray(),
            ];
            $this->logScoringError(User::find($request->buyer_id), $e->getMessage(), $data);
            return ['status' => 'error'];
        } catch (\Throwable $e) {
            $this->logScoringError(User::find($request->buyer_id), $e->getMessage());
            return ['status' => 'error'];
        }

        return ['status' => 'success'];
    }

    private function logScoringError(User $user, string $message, array $data = []): void
    {
        $log = new LoggerByUser($user, 'scoring', 'full');
        $log->error($message, $data);
    }

    // полная проверка клиента: ПИНФЛ, Royxat, KATM
    public function checkScoring(Request $request)
    {

        $report = GradeScoring::scoringStateReportMessages($request->buyer_id);

        return [
            'status' => 'success',
            'data' => $report,
        ];

    }

    // добавление доверителя при регистрации
    public function addGuarant(AddGuarantRequest $request)
    {
        Log::channel('guarantor')->info(self::class.'->request->'.json_encode($request->all(),JSON_UNESCAPED_UNICODE));

        $buyer = Buyer::find((int)$request->buyer_id);
        $buyer_status = $buyer->status;
        Log::channel('guarantor')->info(self::class.'->start->ID='.$buyer->id.' STATUS='.$buyer_status);

        if ((int)$buyer->status === 4) {
            $errors[] = "Buyer is already verified!";
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 405;    //  	Method Not Allowed
            $this->result['response']['errors'] = $errors;
            return $this->result();
        }

        if (is_array($request->name) && is_array($request->phone) && count($request->name) == count($request->phone)) {
            for ($i = 0; $i < count($request->name); $i++) {
                $buyerGuarant = new BuyerGuarant();
                $buyerGuarant->name = $request->name[$i];
                $buyerGuarant->phone = $request->phone[$i];
                $buyerGuarant->user_id = $request->buyer_id;
                $buyerGuarant->save();
            }
        } else if (!is_array($request->name) && !is_array($request->phone)) {
            $buyerGuarant = new BuyerGuarant();
            $buyerGuarant->name = $request->name;
            $buyerGuarant->phone = $request->phone;
            $buyerGuarant->user_id = $request->buyer_id;
            $buyerGuarant->save();
        } else {
            $this->result['status'] = 'error';
            $this->message('danger', 'name_or_phone_is_array_while_another_is_not');
            return $this->result();
        }

        User::changeStatus($buyer, 2);

        KycHistory::insertHistory($buyer->id, User::KYC_STATUS_UPDATE);
        Log::channel('guarantor')->info(self::class.'->Buyer status changed from '.$buyer_status.' to '. 2 .' ID='.$buyer->id);

        $this->result['status'] = 'success';
        $this->result['response']['code'] = 200;

        return $this->result();
    }

    // сменить язык
    public function changeLang(Request $request)
    {

        Log::info('buyer changeLang ' . Auth::id());
        Log::info($request);

        $errors = [];
        if (!$request->has('lang')) $errors[] = 'lang is not set';

        if (count($errors) == 0) {
            $user = Auth::user();
            $user->lang = $request->lang;
            $user->save();
            Log::info('changeLang to ' . $request->lang);
            $this->result = ['status' => 'success'];
            return $this->result();
        }

        Log::info($errors);

        $this->result['status'] = 'error';
        $this->result['response']['code'] = 404;
        $this->result['response']['errors'] = $errors;

        return $this->result();


    }

    // добавление ссылки на корзину от вендора - магазина
    public function addCartLink(Request $request)
    {

        $errors = [];
        if (!isset($request->url)) $errors[] = 'Url is not set';
        if (!isset($request->phone)) $errors[] = 'Phone is not set';

        if (count($errors) == 0) {

            if ($buyer = Buyer::where('phone', $request->phone)->with('personals')->first()) {

                $buyer->personals->vendor_link = $request->url;
                $buyer->personals->save();

                $this->result['status'] = 'success';
                $this->result['response']['code'] = 200;
                return $this->result();

            }

            $errors[] = 'Buyer not found!';

        }

        $this->result['status'] = 'error';
        $this->result['response']['code'] = 404;
        $this->result['response']['errors'] = $errors;

        return $this->result();


    }


    public function initPush(Request $request)
    {
        $errors = [];
        if (!$request->has('phone')) $errors[] = 'Phone is not set';
        if (!$request->has('lang')) $errors[] = 'Lang is not set';
        if (!$request->has('device')) $errors[] = 'Device is not set';

        if ($request->lang != 'ru' && $request->lang != 'uz') $errors[] = 'Lang is incorrect';
        if ($request->device != 'android' && $request->device != 'ios') $errors[] = 'Device is incorrect';

        if (count($errors) == 0) {

            if ($buyer = Buyer::where('phone', $request->phone)->first()) {
                $buyer->lang = $request->lang;
                $buyer->device_os = $request->device;
                $buyer->save();

                $this->result['status'] = 'success';
                return $this->result();

            }

            $errors[] = 'Buyer not found!';

        }

        $this->result['status'] = 'error';
        $this->result['response']['code'] = 404;
        $this->result['response']['errors'] = $errors;

        return $this->result();

    }

    // кол-во купленных телефонов клиента
    public function phonesCount(PhonesCountRequest $request)
    {
        $count = BuyerService::getPhonesCount($request->buyer_id);
        $this->result['phones_count'] = $count;
        if ($count > 1) {
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->result['response']['errors'] = [__('api.you_have_exceeded_the_maximum_allowable_number_of_contracts_in_the_phones_category')];
        } else {
            $this->result['status'] = 'success';
        }
        return $this->result();
    }


    public function setGender(RequestPhone $request)
    {

        $user = Auth::user();

        if ($request->has('buyer_id')) {

            if ($buyer = Buyer::find($request->buyer_id)) {

                if ($user->can('modify', $buyer)) {

                    $buyer->gender = $request->gender;
                    $buyer->save();

                    $this->result['status'] = 'success';
                    $this->message('success', __('panel/buyer.txt_status_changed'));

                }
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

    public function setBirthdate(RequestPhone $request)
    {

        $user = Auth::user();

        if ($request->has('buyer_id') && $request->has('birthdate')) {

            if ($buyer = Buyer::find($request->buyer_id)) {

                if ($user->can('modify', $buyer)) {

                    $buyer->birth_date = date('Y-m-d', strtotime($request->birthdate));
                    $buyer->save();

                    $this->result['status'] = 'success';
                    $this->message('success', __('panel/buyer.txt_status_changed'));

                }
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
     * проверка статуса клиента - для участия в акции
     *
     * @return array|bool|false|string
     */
    public function checkStatusAction(Request $request)
    {

        $phone = $request->phone;
        $mounth = 0;
        $not_cnt = 0;
        $delays = 0;

        $buyer = Buyer::where('phone', $phone)->first();

        if ($buyer) {
            $this->result['status'] = 'success';
            $this->result['data'] = ['buyer_status' => $buyer->status, 'buyer_id' => $buyer->id];
            if ($buyer->status == 4) {
                if ($conracts = Contract::where('user_id', $buyer->id)->whereIn('status', [1, 3, 4, 9])->get()) {
                    foreach ($conracts as $conract) {

                        // хоть один месяц оплачен и нет просрочки
                        foreach ($conract->schedule as $schedule) {
                            // хоть один месяц оплачен и нет просрочки
                            if (time() - strtotime($schedule->payment_date) > 0) {
                                $overdue_days = intval((time() - strtotime($schedule->payment_date)) / 86400);
                                if ($schedule->status == 0 && $delays == 0) $delays = $overdue_days;

                                if ($schedule->status == 0) {
                                    $not_cnt++;
                                }
                            }

                            //хоть один месяц оплачен
                            if ($schedule->status == 1) {
                                $mounth++;
                            }
                        }

                    }
                    if (!$not_cnt && $mounth) {
                        $action_status = 1;
                    } else {
                        $action_status = 0;
                    }

                    $detail = [
                        'conracts' => $conracts->count(),
                        'overdue_days' => $delays,
                        'mounth_paid' => $mounth,
                        'action_status' => $action_status,
                    ];

                } else {
                    $detail = ['action_status' => 0];
                }

            } else {
                $detail = ['action_status' => 0];
            }
            $this->result['data']['action_detail'] = $detail;

        } else {
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message('danger', __('app.err_not_found'));
        }

        return $this->result();
    }

    /**
     * Сверка имени пользователя в таблице users с именами на картах,
     * добавленных пользователем в таблице cards
     *
     * @param CheckSimilarityRequest $request
     *
     * @return array|mixed
     */
    public function checkSimilarity(CheckSimilarityRequest $request)
    {
        try {
            $minPercent = $request->get('min_percent', 75);
            $user       = User::whereId($request->get('user_id'))
                ->whereHas('cards')
                ->with('cards')
                ->first();
            if ($user) {
                foreach ($user->cards as $card) {
                    if (empty($card->card_name)) {
                        $this->result['data'][$user->id]['possible_error'][$card->id] = ['card_name'         => 'Card name field is empty',
                            'username'          => $user->name . ' ' . $user->surname,
                            'result_%'          => 0,
                            'result_%_reversed' => 0];
                        $entityData = ['user_id'                => $user->id,
                            'user_name'              => $user->name,
                            'user_surname'           => $user->surname,
                            'user_patronymic'        => $user->patronymic,
                            'card_id'                => $card->id,
                            'card_number'            => $card->card_number,
                            'card_valid_date'        => $card->card_valid_date,
                            'min_percent'            => $request->get('min_percent')];
                        $this->createSimilarityCheckEntity($entityData);
                    } else {
                        $dbCardName                       = $this->getClearMetaphoneString($card->card_name, true);
                        $dbUserName                       = $this->getClearMetaphoneString($user->name . ' ' . $user->surname);
                        $dbUserNameRev                    = $this->getClearMetaphoneString($user->surname . ' ' . $user->name);
                        $checkResults                     = $this->getCheckResult($dbCardName, $dbUserName, $dbUserNameRev);
                        $this->result['status']           = 'success';
                        $this->result['response']['code'] = 200;
                        $data                             = ['card_name'         => $card->card_name,
                            'username'          => $user->name . ' ' . $user->surname,
                            'result_%'          => $checkResults['fw_result'],
                            'result_%_reversed' => $checkResults['rev_result'],
                        ];
                        if ($checkResults['fw_result'] >= $minPercent || $checkResults['rev_result'] >= $minPercent) {
                            $this->result['data'][$user->id]['success'][$card->id] = $data;
                        } else {
                            $this->result['data'][$user->id]['possible_error'][$card->id] = $data;
                            $entityData = ['user_id'                => $user->id,
                                'user_name'              => $user->name,
                                'user_surname'           => $user->surname,
                                'user_patronymic'        => $user->patronymic,
                                'card_id'                => $card->id,
                                'card_name'              => $card->card_name,
                                'card_number'            => $card->card_number,
                                'card_valid_date'        => $card->card_valid_date,
                                'similarity_percent_fw'  => $checkResults['fw_result'],
                                'similarity_percent_rev' => $checkResults['rev_result'],
                                'min_percent'            => $request->get('min_percent')];
                            $this->createSimilarityCheckEntity($entityData);
                        }
                    }
                }
            } else {
                $this->result['status']           = 'error';
                $this->result['response']['code'] = 404;
                $this->error('User has no cards');
            }
        } catch (\Exception $e) {
            $this->result['status']           = 'error';
            $this->result['response']['code'] = 500;
            $this->error(['Exception: ' . $e->getMessage() . ', Line: ' . $e->getFile() . '@' . $e->getLine()]);
        }

        return $this->result;
    }

    private function getClearMetaphoneString(string $string, bool $checkPatronymic = false): string
    {
        $string = $this->cleanString($string);
        if ($checkPatronymic) {
            $string = $this->checkForPatronymic($string);
        }

        return metaphone($string);
    }

    private function getCheckResult(string $cardName, string $userStringFw, string $userStringRev): array
    {
        $levenFw   = levenshtein($cardName, $userStringFw);
        $strlenFw  = max(strlen($cardName), strlen($userStringFw));
        $resFw     = round((1 - $levenFw / $strlenFw) * 100, 2);
        $levenRev  = levenshtein($cardName, $userStringRev);
        $strlenRev = max(strlen($cardName), strlen($userStringRev));
        $resRev    = round((1 - $levenRev / $strlenRev) * 100, 2);

        return ['fw_result' => $resFw, 'rev_result' => $resRev];
    }

    private function cleanString(string $string, bool $dontNeedSpace = false): string
    {
        $replaceKeysArray   = ['1',
            '2',
            '3',
            '4',
            '5',
            '6',
            '7',
            '8',
            '9',
            '0',
            '.',
            ',',
            'YATT',
            'OPERU',
            'ZH',
            'KH',
            'X',
            'I`',
            'I‘',
            'I‘',
            'I\'',
            'YI',
            'YE',
            'YA',
            '`',
            '‘',
            '’',
            '\'',
            'O`GLI',
            '  ',
            '   '];
        $replaceValuesArray = ['',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            'J',
            'H',
            'H',
            'Y',
            'Y',
            'Y',
            'Y',
            'I',
            'E',
            'A',
            '',
            '',
            '',
            '',
            '',
            ' ',
            ' '];

        if ($dontNeedSpace) {
            $replaceKeysArray[]   = ' ';
            $replaceValuesArray[] = '';
        }
        $string = strtoupper(trim($string));
        $result = str_replace($replaceKeysArray, $replaceValuesArray, $string);

        return trim($result);
    }

    private function checkForPatronymic(string $string): string
    {
        $divided = explode(' ', $string);
        if (count($divided) > 2) {
            return $divided[0] . ' ' . $divided[1];
        }

        return $string;
    }

    private function createSimilarityCheckEntity(array $data): void
    {
        SimilarityCheck::create(['user_id'                => $data['user_id'],
            'user_name'              => $data['user_name'] ?? null,
            'user_surname'           => $data['user_surname'] ?? null,
            'user_patronymic'        => $data['user_patronymic'] ?? null,
            'card_id'                => $data['card_id'],
            'card_name'              => $data['card_name'] ?? '=empty_field_in_db=',
            'card_number'            => $data['card_number'] ?? null,
            'card_valid_date'        => $data['card_valid_date'] ?? null,
            'similarity_percent_fw'  => $data['similarity_percent_fw'] ?? 0,
            'similarity_percent_rev' => $data['similarity_percent_rev'] ?? 0,
            'min_percent'            => $data['min_percent']]);
    }
}
