<?php

namespace App\Services\API\V3;

use App\Helpers\CardHelper;
use App\Helpers\EncryptHelper;
use App\Helpers\FileHelper;
use App\Helpers\SmsHelper;
use App\Http\Controllers\Core\CardController;
use App\Http\Controllers\Core\CatalogCategoryController;
use App\Http\Controllers\Core\ZpayController;
use App\Http\Requests\AddDepositRequest;
use App\Http\Requests\V3\Buyer\UploadAddressRequest;
use App\Http\Requests\V3\Buyer\UploadPassportAndIDRequest;
use App\Models\AutopayDebitHistory;
use App\Models\Buyer;
use App\Models\BuyerGuarant;
use App\Models\BuyerPersonal;
use App\Models\Card;
use App\Models\CatalogPartners;
use App\Models\Company;
use App\Models\Contract;
use App\Models\ContractNotification;
use App\Models\GeneralCompany;
use App\Models\KycHistory;
use App\Models\MyIDJob;
use App\Models\Payment;
use App\Models\PayService;
use App\Models\Role;
use App\Models\User;
use App\Rules\CheckByPhonePrefixCode;
use App\Rules\CheckGuarantPhone;
use App\Services\Mobile\OtpService;
use App\Services\testCardService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis;

class BuyerService extends BaseService
{

    public static function validateAddGuarant(Request $request)
    {
        Log::channel('guarantor')->info(self::class.'->request->'.json_encode($request->all(),JSON_UNESCAPED_UNICODE).'ID='.Auth::id());
        $validator = Validator::make($request->all(), [
            'data' => 'required|array|size:2',
            'data.*.name' => 'required|string',
            'data.*.phone' => ['required','numeric','regex:/(998)[0-9]{9}/', new CheckGuarantPhone, new CheckByPhonePrefixCode],
            // 'data.*.buyer_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            Log::channel('guarantor')->info(self::class.'->validation->'.json_encode($validator->errors()->getMessages(),JSON_UNESCAPED_UNICODE).'ID='.Auth::id());
            return self::handleError($validator->errors()->getMessages());
        }
        return $validator->validated();
    }

    public static function validateContractId(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contract_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return self::handleError($validator->errors()->getMessages());
        }
        return $validator->validated();
    }

    public static function validatePayServicePay(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
            'amount' => 'required|numeric',
            'account' => 'required|numeric|regex:/(998)[0-9]{9}/',
        ]);

        if ($validator->fails()) {
            return self::handleError($validator->errors()->getMessages());
        }
        return $validator->validated();
    }

    public static function validateAddDeposit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'card_id' => 'required|numeric',
            'sum' => 'required|numeric|gt:0',
        ]);

        if ($validator->fails()) {
            return self::handleError($validator->errors()->getMessages());
        }
        return $validator->validated();
    }

    public static function validateBonusToCard(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bonus_sum_request' => 'required|integer|min:1000',
            'card_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return self::handleError($validator->errors()->getMessages());
        }
        return $validator->validated();
    }

    public static function validateBonusToCardConfirm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'card_id' => 'required|integer',
            'amount' => 'required|integer',
            'sms_code' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return self::handleError($validator->errors()->getMessages());
        }
        return $validator->validated();
    }

    public static function changeLang(Request $request)
    {
        $user = Auth::user();
        Log::info('buyer changeLang ' . $user->id);
        Log::info($request);

        if (!$request->has('lang')) {
            $errors = [__('api.lang_not_set')];
            Log::info($errors);
            self::handleError($errors);
        }

        $user->lang = $request->lang;
        $user->save();
        Log::info('changeLang to ' . $request->lang);
        return self::handleResponse();
    }

    public static function catalog(Request $request)
    {
        $data = [];
        // $catalog = Catalog::where('status',1)->orderBy('pos')->get()
        if ($categories = CatalogCategoryController::tree(0, [], true)) {
            foreach ($categories as $cat) {
                $data[] = array_merge([
                    'id' => $cat->id,
                    'img' => isset($cat->image->path) ? url('/storage/' . $cat->image->path) : ''  // ссылка на изображение категории
                ], $cat['locales']);
            }
        }
        return self::handleResponse($data);
    }

    public static function addGuarant(Request $request)
    {
        $user = Auth::user();
        $buyer = Buyer::find($user->id);
        $buyer_status = $buyer->status;
        Log::channel('guarantor')->info(self::class.'->start->ID='.$buyer_status.' STATUS='.$buyer->status);
        $inputs = self::validateAddGuarant($request);
        if (!$buyer) {
            return self::handleError([__('auth.error_user_not_found')]);
        }
        if ($buyer->status == User::KYC_STATUS_VERIFY) {
            return self::handleError([__('api.user_verified')]);
        }
        $errors = [];
        foreach ($inputs['data'] as $key => $input) {
            if ($buyer->phone == $input['phone']) {
                $errors[] = __('api.user_phone_equals_to_buyers');
            }
            if (($key + 1) == count($inputs['data'])) {
                if ($input['phone'] == $inputs['data'][$key - 1]['phone']) {
                    $errors[] = __('api.duplicate_phone_number');
                }
            }
        }
        if (count($errors) > 0) {
            Log::channel('guarantor')->info(self::class.'->validation->'.json_encode($errors,JSON_UNESCAPED_UNICODE).' ID='.$buyer->id);
            return self::handleError($errors);
        }
        foreach ($inputs['data'] as $input) {
            $buyerGuarant = new BuyerGuarant();
            $buyerGuarant->name = $input['name'];
            $buyerGuarant->phone = $input['phone'];
            $buyerGuarant->user_id = $user->id;
            $buyerGuarant->save();
        }
        //Update user status
        self::changeUserStatus($buyer, 2);
        KycHistory::insertHistory($buyer->id, User::KYC_STATUS_UPDATE);
        Log::channel('guarantor')->info(self::class.'->Buyer status changed from '.$buyer_status.' to '. 2 .' ID='.$buyer->id);
        return self::handleResponse();
    }

    public static function check_status()
    {

        $user = Auth::user();
        $buyer = Buyer::with('settings', 'personals', 'addressRegistration', 'scoringResultMini')->find($user->id);
        if ($buyer) {
            if ($user->can('detail', $buyer)) {
                $data = [
                    'status' => $buyer->status,
                    'buyer_id' => $buyer->id,
                    'passport_type' => $buyer->personals->passport_type ?? null,
                    'address_is_received' => $buyer->addressRegistration && $buyer->addressRegistration->address !== null ? 1 : 0,
                ];
                if ($buyer->status == User::KYC_STATUS_VERIFY) {
                    $balance = $buyer->settings->balance == 0 ? 0 : $buyer->settings->balance;
                    $personal_account = $buyer->settings->personal_account == 0 ? 0 : $buyer->settings->personal_account;
                    $totalBalance = $balance == 0 ? 0 : $balance + $personal_account;
                    $balance = $balance < 0 ? 0 : $balance;
                    $personal_account = $personal_account < 0 ? 0 : $personal_account;
                    $totalBalance = $totalBalance < 0 ? 0 : $totalBalance;
                    $data['balance'] = number_format($balance, 2, ".", "");
                    $data['personal_account'] = number_format($personal_account, 2, ".", "");
                    $data['available_balance'] = number_format($totalBalance, 2, ".", "");
                }
                if (in_array($buyer->status, [User::KYC_STATUS_RESCORING, User::KYC_STATUS_GUARANT])) {
                    // If buyer registered with MYID - change status to 5
                    $myid = MyIDJob::where('user_id', $buyer->id)->orderBy('id', 'DESC')->first();
                    if ($myid) {
                        $buyer->status = User::KYC_STATUS_SCORING;
                        $buyer->save();
                        $data['status'] = $buyer->status;
                    }
                }

                $miniScoringResult = $buyer->scoringResultMini->last();
                if ($miniScoringResult) {
                    $data['scoring_result_mini'] = $miniScoringResult->total_state;
                    $data['attempts_limit_reached'] = $miniScoringResult->attempts_limit_reached;
                    if ($buyer->settings) {
                        $mini_balance = $buyer->settings->mini_balance == 0 ? 0 : $buyer->settings->mini_balance;
                        $mini_limit = $buyer->settings->mini_limit == 0 ? 0 : $buyer->settings->mini_limit;
                        $data['mini_balance'] = number_format($mini_balance, 2, ".", "");
                        $data['mini_limit'] = number_format($mini_limit, 2, ".", "");
                    }
                }

                return self::handleResponse($data);
            } else {
                return self::handleError([__('app.err_access_denied')]);
            }
        }
        return self::handleError([__('app.err_not_found')]);
    }

    public static function balance()
    {
        $user = Auth::user();
        $buyer = Buyer::with('settings')->find($user->id);
        if (!$buyer) {
            return self::handleError([__('auth.error_user_not_found')]);
        }
        $data = [
            'installment' => $buyer->settings ? floatval($buyer->settings->balance) : 0,
            'deposit' => $buyer->settings ? floatval($buyer->settings->personal_account) : 0,
            'all' => $buyer->settings ? floatval($buyer->settings->balance) + floatval($buyer->settings->personal_account) : 0,
        ];
        return self::handleResponse($data);
    }

    public static function cards()
    {
        $user = Auth::user();
        $data = [];
        $cards = Card::where('user_id', $user->id)->get();
        if (count($cards) > 0) {
            // $cardConroller = new CardController();
            foreach ($cards as $card) {
                // leave hidden cards as it is
                if ($card->hidden == 0) continue;

                if ($card->token_payment) {
                    $balanceResponse = (new testCardService())->getCardBalance($card->token_payment);
                    $balance = isset($balanceResponse['balance']) && $balanceResponse['balance'] > 0 ? $balanceResponse['balance'] / 100 : 0;
                } else {
                    $balance = 0;
                }


                $data[] = [
                    "title" => $card->card_name,
                    "img" => CardHelper::getImage(EncryptHelper::decryptData($card->type)),
                    "pan" => CardHelper::getCardNumberMask(EncryptHelper::decryptData($card->card_number)),
                    "exp" => EncryptHelper::decryptData($card->card_valid_date),
                    "id" => $card->id,
                    "type" => CardHelper::checkTypeCard(EncryptHelper::decryptData($card->card_number))['name'],
                    'balance' => $balance,
                ];
            }
        }
        return self::handleResponse($data);
    }

    public static function payments(Request $request)
    {
        $limit = $request->has('limit') ? (int)$request->get('limit') : 20;
        $user = Auth::user();
        $payments = Payment::with('order')
            ->select('cards.card_number', 'payments.id', 'payments.card_id', 'payments.id AS payment_id', 'payments.contract_id', 'payments.amount', 'payments.type', 'payments.payment_system', 'payments.created_at', DB::raw('DATE_FORMAT(payments.created_at,"%Y-%m-%d") AS date'), DB::raw('DATE_FORMAT(payments.created_at,"%H:%i:%s") AS time'))
            ->leftJoin('cards', 'cards.id', '=', 'payments.card_id')
            ->where('payments.user_id', $user->id)
            ->whereNotIn('payments.status', [5, 7])
            ->orderBy('payments.created_at', 'DESC')
            ->paginate($limit);
        $result = self::formatPayments($payments);
        $data = $payments->toArray();
        $data['data'] = $result;
        return self::handleResponse($data);
    }

    public static function notify()
    {
        $user = Auth::user();
        $data = $user->notifications;
        return self::handleResponse($data);
    }

    public static function contracts()
    {
        $user = Auth::user();
        $buyer = Buyer::with('contracts.order','contracts.schedule','contracts.collcost','contracts.autopay_history')->find($user->id);
        $data = [1 => [], 2 => [], 3 => [], 4 => [], 5 => [], 6 => []];
        if (!$buyer) {
            return self::handleError([__('auth.error_user_not_found')]);
        }
        $contracts = $buyer->contracts()->orderBy('created_at', 'DESC')->get();
        foreach ($contracts as $contract) {
            //Do not show test contracts
            if ($contract->order->test == 1) continue;
            $item = [
                'contract_id' => $contract->id ?? null,
                'order_id' => $contract->order_id ?? null,
                'online' => $contract->order ? $contract->order->online : null,
                'period' => $contract->period ?? null,
                'remainder' => $contract->balance ?? null,
                'current_pay' => $contract->nextPayment->balance ?? null,
                'next_pay' => $contract->nextPayment->payment_date ?? null,
                'monthly_payment' => $contract->nextPayment->total ?? null,
                'status' => $contract->status ?? null,
                'schedule_list' => $contract->schedule ?? [],
                'created_at' => $contract->created_at,
                'manager_id' => $contract->company->manager_id,
                'partner_contract_confirm' => $contract->partner->settings->contract_confirm ?? 0,
                'recovery_costs' =>  round($contract->collcost()->where('status', 0)->sum('balance') + $contract->autopay_history()->where('status', 0)->sum('balance'),2),

            ];
            //Просроченные
            if (in_array($contract->status, [3, 4])) {
                $data[1][] = $item;
            }
            //На модерации
            if (in_array($contract->status, [2])) {
                $data[2][] = $item;
            }
            //Активные
            if (in_array($contract->status, [1])) {
                $data[3][] = $item;
            }
            //Неактивные
            if (in_array($contract->status, [0])) {
                $data[4][] = $item;
            }
            //Отмененные
            if (in_array($contract->status, [5])) {
                $data[5][] = $item;
            }
            //Закрытые
            if (in_array($contract->status, [9])) {
                $data[6][] = $item;
            }
        }
        $result = [];
        foreach ($data as $d) {
            if (count($data)) {
                foreach ($d as $c) {
                    $result[] = $c;
                }
            }
        }
        return self::handleResponse($result);
    }

    public static function contract(Request $request)
    {
        $input = self::validateContractId($request);
        $user = Auth::user();
        $buyer = Buyer::find($user->id);
        if (!$buyer) {
            return self::handleError([__('auth.error_user_not_found')]);
        }
        $contract = Contract::with('order','collcost','autopay_history')->find($input['contract_id']);
        if (!$contract) {
            return self::handleError([__('api.contract_not_found')]);
        }
        if ($contract->status !== Contract::STATUS_CANCELED) {
            if (isset($contract->uzTaxUrl) && isset($contract->url)) {
                $url = strtotime($contract->uzTaxUrl->created_at) >= strtotime($contract->url->created_at) ? $contract->uzTaxUrl->qr_code_url : $contract->url->url;
            } else {
                $url = $contract->uzTaxUrl->qr_code_url ?? ($contract->url->url ?? null);
            }
        }
        $products = $contract->order->products;
        if (count($products) > 0) {
            foreach ($products as $product) {
                $product->name = !empty($product->original_name) ? $product->original_name : $product->name;
                $product->imei = !empty($product->original_imei) ? $product->original_imei : $product->imei;
                $product->price = floatval($product->price);
                $product->price_discount = floatval($product->price_discount);
            }
        }

        $doc_path = $contract->clientAct;

        $webview_path = null;
        $contract->webview_path = null;
        if($contract->general_company_id === GeneralCompany::MFO_COMPANY_ID) {
            $webview_path = Config::get('test.webview_link') . '?contractId=' . $contract->id;
        }

        $data = [
            'contract_id' => $contract->id,
            'status' => $contract->status,
            'order_id' => $contract->order_id,
            'online' => $contract->order ? $contract->order->online : null,
            'remainder' => $contract->balance,
            'next_pay' => @$contract->nextPayment->payment_date,
            'monthly_payment' => @$contract->nextPayment->total,
            'period' => $contract->period,
            "current_pay" => @$contract->nextPayment->balance,
            "doc_pdf" => isset($doc_path) ? "/storage/" . $doc_path->path : null,
            "offer_preview" => '',
            'products' => $products,
            'doc_path' => $contract->doc_path,
            'general_company_id' => $contract->general_company_id,
            'is_allowed_online_signature' => $contract->is_allowed_online_signature,
            'manager_id' => $contract->company->manager_id,
            'url' => $url ?? null,
            'partner_contract_confirm' => $contract->partner->settings->contract_confirm ?? 0,
            'webview_path' => $webview_path,
            'recovery_costs' =>  round($contract->collcost()->where('status', 0)->sum('balance') + $contract->autopay_history()->where('status', 0)->sum('balance'),2),
        ];
        $result = [
            "contracts" => $data,
            "schedule_list" => $contract->schedule
        ];
        return self::handleResponse($result);
    }

    public static function bonusBalance()
    {
        $user = Auth::user();
        $buyer = Buyer::find($user->id);
        if (!$buyer) return self::handleError([__('auth.error_user_not_found')]);
        $data = ["bonus" => $buyer->settings ? floatval($buyer->settings->zcoin) : 0];
        return self::handleResponse($data);
    }

    public static function payServices()
    {
        $data = [
            [
                'title' => 'Other',
                'items' => [],
                'type' => 1
            ],
            [
                'title' => 'Mobile',
                'items' => [],
                'type' => 0
            ]
        ];

        $services = PayService::where('status', 1)->get();
        if (count($services) > 0) {
            foreach ($services as $service) {
                $data[$service->type]['items'][] = [
                    'id' => $service->id,
                    'title' => $service->name,
                    'type' => $service->type == 1 ? 'mobile' : 'other',
                    'img' => $service->getImgAttribute(),
                ];
            }
        }
        return self::handleResponse($data);
    }

    public static function payServicePayment(Request $request)
    {
        $user = Auth::user();
        $inputs = self::validatePayServicePay($request);
        $errors = [];
        $buyer = Buyer::find($user->id);
        if (!$buyer) {
            return self::handleError([__('auth.error_user_not_found')]);
        }
        $request->merge(['user_id' => $user->id]);
        // check for black list
        if ($buyer->black_list) {
            return self::handleError([__('billing/order.err_black_list')]);
        }
        if ($buyer->settings->zcoin < $request->amount) {
            return self::handleError([__('app.balls_not_enough')]);
        }
        $upay = new ZpayController();
        $result = $upay->pay($request);
        if (isset($result['status']) && $result['status'] == 'error') return self::handleError([__('api.internal_error')]);
        if (isset($result['status']) && $result['status'] == 'success') return self::handleResponse();
    }

    public static function addDeposit(Request $request)
    {
        $inputs = self::validateAddDeposit($request);
        $buyer = auth()->user();
        $card = Card::find($inputs['card_id']);
        if (!$card) {
            return self::handleError([__('card.card_not_found')]);
        }
        if ($inputs['sum'] < 1) {
            return self::handleError([__('validation.gt.numeric', ['attribute' => 'sum', 'value' => 1])]);
        }
        // списание с карты на баланс клиента без смс информирования
        $cardController = new CardController();
        $request->merge([
            'card_id' => $card->id,
            'user_id' => $buyer->id
        ]);
        $response = $cardController->adjunction($request); // пополнение средств на ЛС
        if ($response['status'] === 'success') {

            return self::handleResponse();
        }
        return self::handleError([$response['message']]);
    }

    public static function bonusToCard(Request $request)
    {
        $inputs = self::validateBonusToCard($request);
        $user = Auth::user();
        $buyer = Buyer::with('cards')->find($user->id);
        if (!$buyer) {
            return self::handleError([__('auth.error_user_not_found')]);
        }
        if ($buyer->cards->count() < 1) {
            return self::handleError([__('panel/buyer.bonus_user_no_card')]);
        }
        $card = $buyer->cards->where('id', $inputs['card_id'])->first();
        if (!$card) {
            return self::handleError([__('card.card_not_found')]);
        }
        //get bonus balance
        $bonus_balance = $buyer->settings->zcoin;
        $bonus_to_debit = (int)$inputs['bonus_sum_request'] + (((int)$inputs['bonus_sum_request'] * 1) / 100);
        //округляем до сотых в меньшую сторону (напр. если 1000.5996, округляем 1000.59) и сравниваем с балансом
        if ($bonus_balance < floor($bonus_to_debit * 100) / 100) {
            return self::handleError([__('panel/buyer.bonus_not_enough_min_comission')]);
        }
        foreach ($buyer->cards as $key => $item) {
            if ((int)$inputs['card_id'] == $item->id) {
                //generate SMS code and message
                $phone = correct_phone($buyer->phone);
                $code = SmsHelper::generateCode();
                $msg = "Kod: " . $code . ". resusnasiya.uz kartangizga pul o'tkazishga ruxsat so'radi " . CardHelper::getCardNumberMask(EncryptHelper::decryptData($item->card_number)) . ". Tel: " . callCenterNumber(2);

                [$result, $http_code] = SmsHelper::sendSms(correct_phone($buyer->phone), $msg);
                Log::info($result);

                if (($http_code === 200) || ($result === SmsHelper::SMS_SEND_SUCCESS)) {
                    //set SMS code in Redis (check timeout Redis)

                    //Заносим в базу для проверки ввода смс кода
                    $otpService = new OtpService($phone, $code);
                    $otpService->save_record();

                    Redis::set($buyer->phone . '_bonus_to_card', $code);
                    return self::handleResponse();
                } else {
                    return self::handleError([__('panel/buyer.bonus_sms_service_unavailable')]);
                }
            }
        }
    }

    public static function bonusToCardConfirm(Request $request)
    {
        $inputs = self::validateBonusToCardConfirm($request);
        $user = Auth::user();
        $buyer = Buyer::with('cards')->find($user->id);
        $card = $buyer->cards->where('id', $inputs['card_id'])->first();
        if (!$card) {
            return self::handleError([__('panel/buyer.bonus_invalid_card')]);
        }
        if ($inputs['sms_code'] == Redis::get($buyer->phone . '_bonus_to_card')) {
            $input = "?cardId=" . $inputs['card_id'] . "&userId=" . $buyer->id . "&amount=" . ((int)$inputs['amount'] * 100);
            $curl = curl_init(Config::get('test.bonus_to_card_url') . $input);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
            $result = curl_exec($curl);
            $result = json_decode($result, JSON_UNESCAPED_UNICODE);
            $messages = [
                'OK' => [__('panel/buyer.bonus_to_card_success')],
                'NOT_ENOUGH_BAL' => [__('panel/buyer.bonus_not_enough_bal')],
                'CONTRACTS_CONFLICT' => [__('panel/buyer.bonus_contract_conflict')],
                'UNEXPECTED_ERROR' => [__('panel/buyer.bonus_unexpected_error')],
            ];
            if (isset($result['message']) && $result['message'] != '') {
                if ($result['message'] == 'OK') {
                    return self::handleResponse($messages['OK']);
                } else {
                    return self::handleError($messages[$result['message']] ?? [__('app.payment_error')]);
                }
            } else {
                return self::handleError([__('api.internal_error')], 0, 500);
            }
        }
        return self::handleError([__('panel/buyer.bonus_sms_not_correct')]);
    }

    public static function catalogPartners(Request $request)
    {
        $data = [];
        if (!$request->catalog_id) {
            return self::handleError([__('api.bad_request')]);
        }
        $catalogPartners = CatalogPartners::where('catalog_id', $request->catalog_id)->pluck('partner_id')->toArray();
        $companies = Company::with('logo')->where('status', 1)->whereIn('id', $catalogPartners)->orderBy('name')->get();
        if (count($companies) > 0) {
            foreach ($companies as $company) {
                $data[] = [
                    'title' => $company->name,
                    'img' => isset($company->logo->path) ? url('/storage/' . $company->logo->path) : '',
                    'id' => $company->id
                ];
            }
        }
        return self::handleResponse($data);
    }

    public static function catalogPartner(Request $request)
    {
        $data = [];
        if (!$request->partner_id) {
            return self::handleError([__('api.bad_request')]);
        }
        $filials = Company::with('logo')->where('status', 1)->where('parent_id', $request->partner_id)->get();
        if (count($filials) > 0) {
            foreach ($filials as $filial) {
                $data[] = [
                    'fillial_id' => $filial->id,
                    'title' => $filial->title,
                    'address' => $filial->address,
                    'img' => isset($filial->logo->path) ? url('/storage/' . $filial->logo->path) : '',
                    'phone' => $filial->phone,
                ];
            }
            return self::handleResponse($data);
        }
        return self::handleError([], 'error', 404);
    }

    public static function formatPayments($payments)
    {
        $result = [];
        if (count($payments) > 0) {
            foreach ($payments as $payment) {
                $item = new \stdClass();
                $category = '';
                $category_info = '';
                $is_credit = false;
                switch ($payment->type) {
                    case 'user':
                        $category = __('api.payment_category_user');
                        break;
                    case 'user_auto':
                        $category = __('api.payment_category_user_auto');
                        $category_info = __('api.payment_category_info_contract') . $payment->contract_id;
                        $category_info .= $payment->payment_system == 'ACCOUNT' ? ' ' . __('api.payment_category_info_contract_account') : __('api.payment_category_info_contract_card');;
                        $is_credit = true;
                        break;
                    case 'auto':
                        $category = __('api.payment_category_auto');
                        $category_info = $category_info = __('api.payment_category_info_contract') . $payment->contract_id;
                        $is_credit = true;
                        break;
                    case 'refund':
                        $category = __('api.payment_category_refund');
                        $category_info = __('api.payment_category_info_to_card') . CardHelper::getCardNumberMask(EncryptHelper::decryptData($payment->card_number));
                        break;
                    case 'fill':
                        $category = __('api.payment_category_fill');
                        break;
                    case 'A2C':
                        $category = __('api.payment_category_a2c');
                        $category_info = __('api.payment_category_info_to_card') . CardHelper::getCardNumberMask(EncryptHelper::decryptData($payment->card_number));
                        $is_credit = true;
                        break;
                    case 'upay':
                        $category = __('api.payment_category_upay');
                        break;
                    case 'reimbursable':
                        $category = __('app.payment_category_reimbursable');
                        $category_info = __('api.payment_category_info_contract') . $payment->contract_id;
                        $is_credit = true;
                        break;
                    case 'reimbursable_autopay':
                        $category = __('app.payment_category_reimbursable_autopay');
                        $category_info = __('api.payment_category_info_contract') . $payment->contract_id;
                        $is_credit = true;
                        break;
                }
                $item->payment_id = $payment->payment_id;
                $item->contract_id = $payment->contract_id;
                $item->amount = $payment->amount;
                $item->type = $payment->type;
                $item->payment_system = $payment->payment_system;
                $item->date = $payment->date;
                $item->time = $payment->time;
                $item->receipt_type = $payment->receipt_type;
                $item->category = $category;
                $item->category_info = $category_info;
                $item->created_at = $payment->created_at;
                $item->is_credit = $is_credit;
                $result[] = $item;
            }
        }
        return $result;
    }

    public function getBuyerLimits(): array
    {
        $user = Auth::user();

        $buyer = Buyer::find($user->id);

        if ($buyer->role_id !== Role::CLIENT_ROLE_ID) {
            self::handleError([__('app.err_access_denied_role')]);
        }

        $phone = correct_phone($buyer->phone);

        $main_limit = $buyer->settings ? $buyer->settings->limit : 0;
        $mini_limit = $buyer->settings ? $buyer->settings->mini_limit : 0;

        $result = [];

        $website_link = Config::get('app.url');
        $webview_link = Config::get('test.webview_link') . '?phone=' . $phone;

        $limit_statuses = $this->getBuyerLimitStatus($buyer);
        $block_max = (object)[
            'title' => 'Limit Max',
            'period' => '12',
            'limit' => $main_limit > 0 ? (int)$buyer->settings->balance : 15000000,
            'background_img' => $website_link . '/images/limits/main_limit.png',
            'is_active' => $main_limit > 0,
            'webview_link' => $webview_link,
            'description' => __('mobile/mobile.main_limit_banner_desc'),
            'priority' => 1,
            'limit_status' => $limit_statuses['max_limit_status']
        ];

        $block_min = (object)[
            'title' => 'Limit Start',
            'period' => '3',
            'limit' => $mini_limit > 0 ? (int)$buyer->settings->mini_balance : \config('test.scoring.limit.min'),
            'background_img' => $website_link . '/images/limits/mini_limit.png',
            'is_active' => $mini_limit > 0,
            'webview_link' => $webview_link,
            'description' => __('mobile/mobile.mini_limit_banner_desc'),
            'priority' => 2,
            'limit_status' => $limit_statuses['min_limit_status']
        ];

        $result[] = $block_max;
        $result[] = $block_min;

        return $result;
    }

    public static function expiredContractsAutopay()
    {
        $user = Auth::user();
        $result = [];
        $contracts = AutopayDebitHistory::select('contract_id')
            ->where('days', '>', 30)
            ->where('user_id', $user->id)
            ->groupBy('contract_id')
            ->pluck('contract_id');
        if (count($contracts) >= 1) {
            $result['total'] = count($contracts);
            $result['contracts'] = $contracts;
            $result['message'] = str_replace('{contracts}', implode(',', $contracts->toArray()), __('api.expired_contracts_forwarded_to_autopay'));
        }
        return self::handleResponse($result);
    }

    public static function contractsNotifications()
    {
        $user = Auth::user();

        return ContractNotification::where('user_id', $user->id)->get();
    }

    public static function uploadAddress(UploadAddressRequest $request): HttpResponseException
    {
        $user = Auth::user();
        if ($request->has('buyer_id') && self::is_vendor($user->role_id)) {
            $buyer = Buyer::find($request->get('buyer_id'));
        } else {
            $buyer = Buyer::find($user->id);
        }
        if (!$buyer) {
            self::handleError([__('auth.error_user_not_found')]);
        }

        if(isset($request->hash) && Redis::get('myid_registration:' . $buyer->id) !== $request->hash) {
            return self::handleError([__('app.err_upload')]);
        }

        $buyerPersonals = $buyer->personals ?? new BuyerPersonal();
        $buyerPersonals->user_id = $buyer->id;
        $buyerPersonals->passport_type = $request->get('passport_type');
        $buyerPersonals->save();
        $params = [
            'files' => $request->file(),
            'element_id' => $buyerPersonals->id,
            'model' => 'buyer-personal',
            'user_id' => $buyer->id,
        ];
        FileHelper::uploadNew($params);

        $cardExists = Card::where('user_id', $buyer->id)->first();
        if ($cardExists && in_array($buyer->status,[1,5])) {
            User::changeStatus($buyer, 12);
        }
        //if user try manual myid registration
        if(isset($request->hash) && $buyer->status === 5) {
            User::changeStatus($buyer, 1);
        }

        self::handleResponse();
    }

    public static function uploadPassportAndID(UploadPassportAndIDRequest $request, Buyer $buyer, int $kyc_id): bool
    {
        try {
            $params = [
                'files' => $request->file(),
                'element_id' => $kyc_id,
                'model' => Buyer::CONTRACT_KYC_FILE_TYPE,
                'user_id' => $buyer->id,
            ];
            FileHelper::uploadNew($params);
            return true;
        } catch (\Exception $exception) {
            Log::info('uploadPassportAndID failed: ' . $exception->getMessage());
            return false;
        }
    }

    public static function is_vendor(int $role_id): bool
    {
        $role_ids = Role::whereIn('name', Role::VENDOR_ROLE_NAMES)->pluck('id')->toArray();
        return in_array($role_id, $role_ids);
    }

    public function getBuyerLimitStatus(Buyer $buyer) : array
    {
        $status = $buyer->status;
        $settings = $buyer->settings;
        $max_limit_status = 5;
        $min_limit_status = 0;
        if(!$settings || $status == 8){
            return [
                'max_limit_status' => 0,
                'min_limit_status' => 0,
            ];
        }
        if($status == 4){
            $max_limit_status = $settings->limit > 0 ? 1 : 0;
            $min_limit_status = $settings->mini_limit > 0 ? 1 : 0;
        }
        if($status == 5){
            $min_limit_status = $settings->mini_limit > 0 ? 1 : 4;
            $max_limit_status = 4;
        }
        if($status == 1){
            $min_limit_status = $settings->mini_limit > 0 ? 1 : 3;
            $max_limit_status = 3;
        }
        if($status == 12){
            $min_limit_status = $settings->mini_limit > 0 ? 1 : 2;
            $max_limit_status = 2;
        }
        if($status == 2){
            $min_limit_status = $settings->mini_limit > 0 ? 1 : 6;
            $max_limit_status = $settings->limit > 0 ? 1 : 6;
        }
        return [
            'max_limit_status' => $max_limit_status,
            'min_limit_status' => $min_limit_status,
        ];
    }
}
