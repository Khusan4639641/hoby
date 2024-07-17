<?php

namespace App\Services\MFO;

use App\Facades\KATM\RepKatm;
use App\Helpers\EncryptHelper;
use App\Helpers\FileHelper;
use App\Helpers\QRCodeHelper;
use App\Helpers\SellerBonusesHelper;
use App\Helpers\SmsHelper;
use App\Helpers\V3\OrderCreateHelper;
use App\Helpers\V3\OTPAttemptsHelper;
use App\Http\Controllers\Core\CatalogProductController;
use App\Http\Controllers\Core\ContractController;
use App\Http\Requests\V3\MFO\CreateOrderV3MFORequest;
use App\Http\Requests\V3\MFO\OrderCalculateV3MFORequest;
use App\Models\AvailablePeriod;
use App\Models\Buyer;
use App\Models\BuyerPersonal;
use App\Models\Company;
use App\Models\Contract;
use App\Models\ContractPaymentsSchedule;
use App\Models\File;
use App\Models\GeneralCompany;
use App\Models\ContractStatus;
use App\Models\MyIDJob;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\Role;
use App\Models\StaffPersonal;
use App\Models\V3\CompanyAvailablePeriods;
use App\Services\API\V3\BaseService;
use App\Services\API\V3\ContractVerifyService;
use App\Services\API\V3\MyIDService;
use App\Services\API\V3\BuyerService;
use App\Services\API\V3\Partners\OrderService;
use App\Services\Mobile\OtpService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class MFOOrderService extends BaseService
{
    public function calculate(OrderCalculateV3MFORequest $request): array
    {
        $user = Auth::user();
        $partner = Partner::find($user->id);
        $buyer = Buyer::find($request->user_id);
        $company = $partner->company;
        $tariffs = $partner->company->tariffs;
        $response = [];
        foreach ($tariffs as $tariff) {
            $response[] = $this->calculateByPeriod($company, $tariff, $request->products, false, $buyer ?? null);
        }

        return $response;
    }

    public function createOrder(CreateOrderV3MFORequest $request)
    {
        $user = Auth::user();
        $params = $request->all();
        $partner = Partner::find($user->id);
        $company = $partner->company;
        if (!$company) {
            return self::handleError([__('company.company_not_found')]);
        }
        if ($company->status == 0) {
            return self::handleError([__('app.user_is_blocked')]);
        }
        if ($company->general_company_id !== GeneralCompany::MFO_COMPANY_ID) {
            return self::handleError([__('app.incorrect_vendor_type_for_creating_order')]);
        }
        $buyer = Buyer::where(['id' => $params['user_id']])->first();
        if (!$buyer) {
            return self::handleError([__('billing/order.err_buyer_not_found')]);
        }
        if ($buyer->black_list) {
            return self::handleError([__('billing/order.err_black_list')]);
        }
        if (isset($request->ext_order_id) && $this->isActiveContractWithSameRequestId($request->ext_order_id ?? 0,$company->id)) {
            return self::handleError([__('api.contract_is_active_with_same_request_id')]);
        }
        $this->checkUserOverdueContracts($buyer);
        Log::info('add order');
        Log::info($params);
        if (OrderCreateHelper::is_exists_mobile_categories($params['products'])) {
            $phones_count = \App\Services\API\V3\Partners\BuyerService::getPhonesCount($params['user_id']);
            if (!OrderCreateHelper::is_available_buying_smartphones($params['products'], $phones_count)) {
                return self::handleError([__('billing/order.txt_phones_count')]);
            }
        }

        $availablePeriod = AvailablePeriod::where('period', $request->period)->first();
        if (!isset($availablePeriod)) {
            return self::handleError(['Период не найден']);
        }

        if (!CompanyAvailablePeriods::where('company_id', $company->id)->where('period_id', $availablePeriod->id)->exists()) {
            return self::handleError(['Данный период не доступен для этой компании']);
        }
        $calculation = $this->calculateByPeriod($company, $availablePeriod, $request['products'], true,$buyer);
        $origin = $calculation['origin'];  // чистая цена

        if ($availablePeriod->is_mini_loan && !MyIDService::checkBuyerStatusToActivateContract($buyer)) {
            return self::handleError(['Клиент не прошел мини-скоринг']);
        } elseif (!$availablePeriod->is_mini_loan && $buyer->status !== 4) {
            return self::handleError(['Клиент не прошел скоринг']);
        }


        if ($availablePeriod->is_mini_loan) {
            $creditLimit = $buyer->settings->mini_balance;
        } else {
            $creditLimit = $buyer->settings->balance;
        }

        $period = $availablePeriod->period_months;

        if ($calculation['origin'] > $creditLimit) {
            return self::handleError([__('billing/order.err_limit')]);
        }
        $config = Config::get('test.paycoin');
        // если у покупателя приобретена скидка
        if ($buyer->settings->paycoin_sale > 0) {
            $sale = $buyer->settings->paycoin_sale * $config['sale'];
        } else {
            $sale = 0;
        }
        //Сохраняем все договоры
        // если ввели телефон продавца, начислить ему бонусы для оплаты UPAY сервисов
        if (isset($params['seller_phone'])) {
            $seller_phone = correct_phone($params['seller_phone']);
            $seller = Buyer::where('phone', $seller_phone)->first();
        }
        //Create & save order
        $order = new Order();
        $order->partner_id = $user->id;
        $order->company_id = $company->id;
        $order->user_id = $params['user_id'];
        $order->total = $calculation['total'];
        $order->partner_total = $calculation['partner'];
        $order->credit = $order->partner_total;
        $order->debit = 0;
        $order->status = 0;
        $order->online = isset($params['online']) ? 1 : 0;  // все договоры от маркетплейса

        $order->save();
        $result['data']['orders'][$order->company_id]['id'] = $order->id;

        $rvs = Config::get('test.rvs');
        $company_settings = $company->settings;
        $is_trustworthy = $company_settings->is_trustworthy ?? 0;

        //Create & save order products
        foreach ($calculation['products'] as $productItem) {
            $imei = !empty($productItem['imei']) ? ' IMEI: ' . $productItem['imei'] : '';

            $product = new OrderProduct();
            $product->order_id = $order->id;
            $product->name = str_replace(';', ',', $productItem['name'] . $imei);
            $product->label = $productItem['label'] ?? null;
            $product->category_id = $productItem['category'] ?? null;
            $product->imei = $productItem['imei'] ?? null;

            //если с настройках компании есть флаг $is_trustworthy пытаемся записать, которые должны были отправиться с фронта  original_name и original_imei
            if ($is_trustworthy) {
                $product->original_imei = $productItem['original_imei'] ?? '';
                if (isset($productItem['original_name']) && isset($productItem['original_imei'])) {
                    $product->original_name = $productItem['original_name'] . " IMEI: " . $productItem['original_imei'];
                } else {
                    $product->original_name = $productItem['original_name'] ?? '';
                }
            }

            $product->product_id = $productItem['product_id'] ?? null;
            $product->price_discount = $productItem['origin'];
            $product->price = $company->reverse_calc == 1 ? $productItem['price'] / $rvs : $productItem['price'];
            $product->original_price = $productItem['origin'];
            $product->original_price_client = $company->reverse_calc == 1 ? $productItem['price'] / $rvs : $productItem['price'];
            $product->amount = $productItem['amount'];
            $product->weight = $productItem['weight'] ?? 0;
            $product->vendor_code = $productItem['vendor_code'] ?? '';
            $product->unit_id = $productItem['unit_id'];
            $product->save();

        }

        $contractController = new ContractController();

        //Create contract
        $paramsContract = [
            'user_id' => $order->user_id,
            'total' => $order->total,
            'period' => $period,
            'deposit' => (isset($calculation['deposit'])) ? $calculation['deposit'] : 0,
            'partner_id' => $order->partner_id,
            'company_id' => $order->company_id,
            'order_id' => $order->id,
            'price_plan_id' => $availablePeriod->id ?? 0,
            'ext_order_id' => $params['ext_order_id'] ?? null,
            'confirmation_code' => $params['sms_code'] ?? null,
            'offer_preview' => $params['offer_preview'] ?? null,
            'payments' => $calculation['contract']['payments'],
            'status' => (isset($params['cart']) ? 0 : 1),
            'd_graf' => 1,
            'ox_system' => (isset($params['ox_system']) ? $params['ox_system'] : 0)  // все договоры от ox system **
        ];
        //TODO: Совместимость АПИ
        if (isset($params['created_at']))
            $paramsContract['created_at'] = $params['created_at'];

        $contract = $contractController->add($paramsContract, true);

        // если есть продавец магазина, регистрируем ему бонусы с продажи
        if (isset($seller)) {
            $originalBonusAmount = SellerBonusesHelper::calculateBonus($seller->id, $origin);
            $sellerBonusAmount = $originalBonusAmount * $seller->seller_bonus_percent / 100;
            SellerBonusesHelper::registerBonus($seller->id, $contract['data']['id'], $sellerBonusAmount);

            if (count($seller->bonusSharers) > 0) {
                foreach ($seller->bonusSharers as $bonusSharer) {
                    if ($bonusSharer->sharer_id && $bonusSharer->percent) {
                        $sharerBonusAmount = $originalBonusAmount * $bonusSharer->percent / 100;
                        SellerBonusesHelper::registerBonus($bonusSharer->sharer_id, $contract['data']['id'], $sharerBonusAmount);
                    }
                }
            }
        }

        // prefix_act - порядковый номер счет фактуры вендора
        $ct = Contract::where('id', $contract['data']['id'])->first();
        $ct->prefix_act = Contract::where('partner_id', $order->partner_id)->where('id', '<=', $contract['data']['id'])->count();
        $ct->save();

        if ($contract['status'] == 'success') {
            //Calculate buyer balance
            $result['contract']['id'] = $contract['data']['id'];
            $buyer->settings->save();

        } else {
            Log::channel('contracts')->info('Ошибка при создании конктракта, не удалось создать контракт');
            Log::channel('contracts')->info($company->name . " - " . $contract['response']['errors']->all());
            return self::handleError([__('billing/order.err_cant_create_contract') . ' ' . $company->name]);
        }

        Log::channel('contracts')->info("Контракт " . $contract['data']['id'] . " создан  ");
        $result['data']['order_id'] = $order->id;
        $result['data']['contract_id'] = $contract['data']['id'];
        $result['message'] = __('billing/order.txt_created');

        //SMS notification to vendor and manager (if $order->online == 1)
        if ($order->online == 1) {
            $msg = "resusNasiya / Hurmatli sotuvchi, siz buyurtma oldingiz. Iltimos, 5-12 soat ichida qayta ishlang. Hurmat bilan resusNasiya. Tel: " . callCenterNumber(2);

            if ($company->phone) {
                SmsHelper::sendSms($company->phone, $msg);
            } else {
                Log::info("SMS not sent, phone number not found");
            }

            if ($company->manager_phone) {
                SmsHelper::sendSms($company->manager_phone, $msg);
            } else {
                Log::info("SMS not sent, manager phone number not found");
            }
        }

        // 15.07 - тут будем создавать pdf
        $result = OrderService::detail($order->id);
        $result['status_list'] = Config::get('test.order_status');

        $contract_status = new ContractStatus();
        $contract_status->contract_id = $result['order']->contract->id;
        $contract_status->status = ContractStatus::STATUS_ORDER_CREATED_NOT_VERIFIED;
        $contract_status->type = ContractStatus::CONTRACT_TYPE_MFO;
        $contract_status->save();

        $folderContact = 'contract/';
        $path_hash = md5($result['order']->contract->id . time());
        $folder = $folderContact . $path_hash;
        $namePdf = 'buyer_account_' . $path_hash . '.pdf';
        $link = $folder . '/' . $namePdf;
        Log::channel('mfo_order')->info('ACT CREATE');
        if (!FileHelper::exists($link)) {
            $generalCompany = $order->contract->generalCompany;
            //If tariff is 003 get buyer-avatar from my_id_job
            if($availablePeriod->period == '003'){
                $my_id_history = MyIDJob::with('photo')
                                        ->where('user_id','=',$buyer->id)
                                        ->where('result_code','=',1)
                                        ->where('type','=','registration')
                                        ->first();
                if($my_id_history){
                    $buyerAvatar = $my_id_history->photo ? $my_id_history->photo->path : null;
                }
            }
            if(!isset($buyerAvatar)){
                $buyerAvatar =  File::where('element_id', $buyer->personalData->id)->where('type','passport_selfie')->latest()->first();
            }
            $fileInfo = pathinfo($link);
            $actParams = [
                'contract' => $order->contract,
                'generalCompany' => $generalCompany,
                'buyer' => $buyer,
                'passport' => EncryptHelper::decryptData($buyer->personalData->passport_number),
                'buyerAvatar' => $buyerAvatar->path ?? null,
                'buyerSign' => false,
                'isSigned' => false
            ];
            FileHelper::generateAndUploadPDF($link, 'order.mfo.act-mfo', $actParams); //новый формат акта
            Log::channel('mfo_order')->info('buyer_account_pdf create ' . $link);
            $file = new File;
            $file->element_id = $contract['data']['id'];
            $file->model = 'contract';
            $file->type = File::TYPE_CONTRACT_PDF;
            $file->name = $fileInfo['basename'];
            $file->path = $link;
            $file->language_code = $paramsContract['language_code'] ?? null;
            $file->user_id = $paramsContract['partner_id'];
            $file->doc_path = 1;
            $file->save();
        }
        $result['account_pdf'] = 'storage/contract/' . $path_hash . '/' . $namePdf;
        $buyerInfo = Buyer::getInfo($buyer->id);
        Log::channel('contracts')->info('buyerInfo');
        Log::channel('contracts')->info($buyerInfo);

        $full_path_account_pdf = Config::get('test.sftp_file_server_domain') . $result['account_pdf'];

        $webview_url = Config::get('test.webview_link');
        if($partner->id === (int)Config::get('test.resus_partner_id')) {
            $webview_url = Config::get('test.resus_webview_link');
        }

        $webview_url = $webview_url . '?contractId=' . $order->contract->id;
        if (isset($request->callback)) {
            $webview_url = $webview_url . '&callback=' . $request->callback;
        }

        $response = [
            "test_client" => [
                "fio" => $buyer->fio,
                "phone" => correct_phone($buyer->phone),
                "order" => $order->id,
                "contract_id" => $order->contract->id,
                "created_at" => $order->created_at,
                "price_month" => number_format($order->contract->schedule[0]->total, 2, ".", ""),
                "total" => number_format($order->contract->total, 2, ".", ""),
                "available_balance" => number_format($buyer->settings->balance, 2, ".", ""),
                "mini_balance" => number_format($buyer->settings->mini_balance, 2, ".", "")
            ],
            "cart" => $calculation['products'],
            "client_act_pdf" => $full_path_account_pdf,
            "webview_path" => $webview_url
        ];
        return self::handleResponse($response);

    }

    public function checkContractStatus(int $contract_id): ContractStatus
    {
        $user = Auth::user();
        if (!BuyerService::is_vendor($user->role_id) && $user->role_id !== Role::CLIENT_ROLE_ID) {
            self::handleError([__('app.err_access_denied_role')]);
        }
        if (BuyerService::is_vendor($user->role_id)) {
            $contract = Contract::where('partner_id', $user->id)->where('general_company_id', GeneralCompany::MFO_COMPANY_ID)->where('id', $contract_id)->first();
        } else {
            $contract = Contract::where('user_id', $user->id)->where('general_company_id', GeneralCompany::MFO_COMPANY_ID)->where('id', $contract_id)->first();
        }

        if (!isset($contract)) {
            return self::handleError([__('api.contract_not_found')]);
        }
        $mfoStatuses = ContractStatus::where('contract_id', $contract_id)->first();
        if (!isset($mfoStatuses)) {
            return self::handleError([__('api.contract_statuses_not_found')]);
        }

        $mfoStatuses->contract_status = $contract->status;

        return $mfoStatuses;

    }

    public function myIdVerify($file, $contract_id): array
    {
        if($this->getRedisKey($contract_id)){
            return self::handleError([__('api.contract_activation_in_progress')]);
        }
        $user = Auth::user();

        if (!BuyerService::is_vendor($user->role_id) && $user->role_id !== Role::CLIENT_ROLE_ID) {
            self::handleError([__('app.err_access_denied_role')]);
        }
        if (BuyerService::is_vendor($user->role_id)) {
            $contract = Contract::where('id', $contract_id)->where('partner_id', $user->id)->first();
        } else {
            $contract = Contract::where('id', $contract_id)->where('user_id', $user->id)->first();
        }

        if (!isset($contract)) {
            return self::handleError([__('api.contract_not_found')]);
        }

        if ($contract->status === Contract::VERIFIED) {
            return self::handleError([__('api.contract_is_already_activated')]);
        }
        if ($contract->status === Contract::STATUS_AWAIT_VENDOR) {
            return self::handleError([__('contract.contract_in_moderation')]);
        }

        $buyer = Buyer::where('id', $contract->user_id)->first();

        if (!isset($buyer)) {
            return self::handleError([__('api.buyer_not_found')]);
        }

        if ($this->isExpiredContract($contract)) {
            return self::handleError([__('api.contract_out_of_date')]);
        }

        if ($this->isNotEnoughBalance($contract)) {
            return self::handleError([__('billing/order.err_limit')]);
        }

        if (!empty($contract->ext_order_id) && $this->isActiveContractWithSameRequestId($contract->ext_order_id,$contract->company_id)) {
            return self::handleError([__('api.contract_is_active_with_same_request_id')]);
        }

        $contract_status = ContractStatus::where('contract_id', $contract_id)->first();

        if (!isset($contract_status)) {
            return self::handleError([__('api.contract_not_found')]);
        }

        $this->setRedisKey($contract_id);
        //If tariff is 003 skip MY ID
        $attempts =  $contract_status->myid_attempts;
        if($contract->price_plan->period !== '003'){
            if (Config::get('test.myid_order_limit_active') && $contract_status->myid_attempts >= 3) {
                $this->deleteRedisKey($contract_id);
                return self::handleError([__('api.myid_contract_attempts limit')], 'error', 400, ['attempts' => $contract_status->myid_attempts]);
            }

            $myIdService = new MyIDService;

            $response = $myIdService->checkBuyerToActivateContract($file, $buyer, $contract_id);

            $attempts += 1;

            if ($response['code'] === 0) {
                $contract_status->myid_attempts = $attempts;
                $contract_status->save();
                $this->deleteRedisKey($contract_id);
                return self::handleError($response['message'], 'error', 400, ['attempts' => $attempts]);
            }
        }else{
            $params = [
                'files' => [Buyer::PASSPORT_SELFIE_FOR_CONTRACT => $file],
                'element_id' => $buyer->personals->id,
                'model' => 'buyer-personal',
                'user_id' => $buyer->id,
            ];
            FileHelper::uploadNew($params, true);
        }


        $response = $this->activateContract($contract);
        $contract_status->myid_attempts = $attempts;
        $contract_status->status = ContractStatus::STATUS_ORDER_VERIFIED;
        $contract_status->save();

        $this->deleteRedisKey($contract_id);

        return $response;

    }

    public function checkSmsCode($code, $phone, $hashedCode = null): array
    {
        if (!$phone) {
            $user = Auth::user();
            $phone = correct_phone($user->phone);
        }

        $hashedCode = $hashedCode ?? Redis::get($phone);

        $result = Hash::check($phone . $code, $hashedCode);

        if ($result) {
            $data['code'] = 1;
            Redis::del($phone);
        } else {
            $data['code'] = 0;
            $data['error'] = [__('auth.error_code_wrong')];
        }

        //Проверка на лимит ввода и ограничение на ввод сообщений
        $otpCodeResponse = OTPAttemptsHelper::checkOtpCode($phone, boolval($data['code']));

        if ($otpCodeResponse['error']) {
            $data['code'] = 0;
            $data['error'] = $otpCodeResponse['message'];
            $data['data']['errorCode'] = $otpCodeResponse['errorCode'];
        }

        return $data;
    }

    public function sendSmsCode($phone, string $msg = null): array
    {
        if (!OTPAttemptsHelper::isAvailableToSendOTP($phone)) {
            return self::handleError([__('mobile/v3/otp.attempts_timeout')]);
        }

        $code = rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9);
        if (!$msg) {
            $msg = "resusNasiya / :{$code}. Shartnomani tasdiqlash kodi. Xaridingiz uchun rahmat!";
        } else {
            $msg = str_replace(':code', $code, $msg);
        }
        $msg = '<#> ' . $msg . PHP_EOL . Config::get('test.firebase_sms_code_key_hash_prod'); // adding firebase hash for autofill otp and replace code to next row

        $hashedCode = Hash::make($phone . $code);
        [$result, $http_code] = SmsHelper::sendSms($phone, $msg);
        Log::info($result);
        if ($http_code === 200) {
            $otpService = new OtpService($phone, $code);
            $otpService->save_record();
            try {
                Redis::set($phone, $hashedCode);
            } catch (\Exception $e) {
                dd($e);
            }
            return ['code' => 1, 'data' => $hashedCode];
        }
        return ['code' => 0, 'data' => $hashedCode];
    }

    public function signContract(int $contract_id, UploadedFile $sign, $langCode = 'ru'): string
    {
        $user = Auth::user();

        if (!BuyerService::is_vendor($user->role_id) && $user->role_id !== Role::CLIENT_ROLE_ID) {
            self::handleError([__('app.err_access_denied_role')]);
        }
        if (BuyerService::is_vendor($user->role_id)) {
            $contract = Contract::where('id', $contract_id)->where('partner_id', $user->id)->first();
        } else {
            $contract = Contract::where('id', $contract_id)->where('user_id', $user->id)->first();
        }


        if (!isset($contract)) {
            return self::handleError([__('api.contract_not_found')]);
        }


        if (!$contract->is_allowed_online_signature) {
            return self::handleError([__('api.bad_request')]);
        }

        $contract_status = ContractStatus::where('contract_id', $contract_id)->first();

        if (!isset($contract_status)) {
            return self::handleError([__('api.contract_not_found')]);
        }

        $contract_status->status = ContractStatus::STATUS_ORDER_CLIENT_SIGN;
        $contract_status->save();

        try {
            $signParams = [
                'files' => [File::TYPE_SIGNATURE => $sign],
                'element_id' => $contract->id,
                'model' => 'contract'
            ];
            FileHelper::upload($signParams, [], true);
            $path = 'contract/' . $contract->id . '/';

            $buyerAvatar = File::where('element_id', $contract->buyer->personalData->id)->where('type','passport_selfie')->latest()->first();

            $actParams = [
                'contract' => $contract,
                'generalCompany' => $contract->generalCompany,
                'buyer' => $contract->buyer,
                'passport' => EncryptHelper::decryptData($contract->buyer->personalData->passport_number),
                'buyerAvatar' => $buyerAvatar->path ?? null,
                'buyerSign' => $contract->signature->path,
                'isSigned' => true
            ];

            FileHelper::generateAndUploadHtml($contract->id,
                'contract',
                File::TYPE_SIGNED_CONTRACT,
                $langCode,
                $path,
                'order.mfo.act-mfo-html',
                $actParams);

        } catch (\Exception $e) {
            return self::handleError([$e->getMessage()]);
        }
        return FileHelper::url($contract->signedContract->path);
    }

    private function activateContract(Contract $contract): array
    {
        if ($this->isExpiredContract($contract)) {
            return self::handleError([__('api.contract_out_of_date')]);
        }
        $is_mini_loan = $contract->price_plan->is_mini_loan;
        if ($is_mini_loan) {
            $contract->buyer->settings->mini_balance -= $contract->order->credit;
            if ($contract->buyer->settings->mini_balance < 0) {
                return self::handleError([__('billing/order.err_limit')]);
            }
        } else {
            $contract->buyer->settings->balance -= $contract->order->credit; // снять после подтверждения смс кода
            if ($contract->buyer->settings->balance < 0) {
                return self::handleError([__('billing/order.err_limit')]);
            }
        }
        $contract_status = 1;
        $partner = Partner::with('settings')->find($contract->partner_id);
        if($partner && $partner->settings && $partner->settings->contract_confirm == 1){
            $contract_status = 2;
        }

        $contract->confirmation_code = null;
        $contract->status = $contract_status;
        $contract->save();
        $contract->order->status = 9;
        $contract->order->save();

        $contract->buyer->settings->save();
        // IF DEPOSIT
        if ($contract->deposit > 0) {
            // записать как транзакцию в payments
            $payment = new Payment();
            $payment->schedule_id = $contract->schedule[0]->id;
            $payment->type = 'auto';
            $payment->order_id = $contract->order->id;
            $payment->contract_id = $contract->id;
            $payment->amount = $contract->deposit;
            $payment->user_id = $contract->buyer->id;
            $payment->payment_system = 'DEPOSIT';
            $payment->status = 1;
            $payment->save();
        }
        $data['contract_id'] = $contract->id;
        $data['message'] = __('api.check_sms_contract_success');

        if($contract_status == 1){
            $service = new MFOPaymentService();
            $service->init($contract);
        }

        ContractVerifyService::instantVerification($contract);

        return $data;
    }

    public function checkUserOverdueContracts(Buyer $buyer):void
    {
        $user_id = $buyer->id;

        if (Contract::where('user_id', $user_id)->whereIn('status', [3, 4])->count() >  0) {
            self::handleError([__('billing/order.user_overdue_contract')]);
        }

        $debts_value = ContractPaymentsSchedule::leftJoin('contracts','contract_payments_schedule.contract_id','=','contracts.id')
            ->where('contracts.user_id',$user_id)
            ->where('contract_payments_schedule.status',ContractPaymentsSchedule::STATUS_UNPAID)
            ->where('contract_payments_schedule.payment_date','<=',Carbon::now()->format('Y-m-d 23:59:59'))
            ->whereIn('contracts.status',[Contract::STATUS_ACTIVE,Contract::RECOVERY_TYPE_LETTER_WAIT,Contract::RECOVERY_TYPE_NOTARIUS])
            ->sum('contract_payments_schedule.balance');

        if($debts_value > 0) {
            self::handleError([__('billing/order.user_overdue_contract')]);

        }
    }

    public function getMarkup(Company $company, int $user_id, AvailablePeriod $periodRecord): float
    {

        if ($periodRecord->period_months === 12 && $this->isStaffMember($user_id)) {
            return Config::get('test.staff_markup'); // 20
        } else if ($company->reverse_calc == 1) {
            return (Config::get('test.rvs') - 1) * 100; // (1.42 - 1) * 100 = 42
        }
        return $periodRecord->markup;
    }

    private function isStaffMember(int $user_id): bool
    {
        return StaffPersonal::query()->where('status', StaffPersonal::STATUS_WORKS)
            ->whereIn('pinfl', BuyerPersonal::where('user_id', $user_id)->select('pinfl_hash'))
            ->exists();
    }

    public function calculateByPeriod(Company $company, AvailablePeriod $availablePeriod, array $products, bool $with_payments = false,  Buyer $buyer = null): array
    {

        $config = Config::get('test.paycoin');
        $sale = 0;
        // если у покупателя приобретена общая скидка
        if (isset($buyer->settings) && $buyer->settings->paycoin_sale > 0) {
            $sale = $buyer->settings->paycoin_sale * $config['sale'];
        }

        if ($availablePeriod->is_mini_loan) {
            $balance = $buyer->settings->mini_balance ?? 0;
        } else {
            $balance = $buyer->settings->balance ?? 0;
        }


        $period = $availablePeriod->period_months;
        $discount = $availablePeriod->discount;

        $month_discount = 0;
        $nds = Config::get('test.nds');


        // если период 12 месяцев и покупатель наш сотрудник, то наценка особая
        $month_markup = $buyer ? self::getMarkup($company, $buyer->id, $availablePeriod) : $availablePeriod->markup;
        // если клиент приобрел скидку, вычитаем из маржи
        $month_markup = ($month_markup - $sale) / 100;

        $order = [
            'tariff' => $availablePeriod->period,
            'period_months' => $availablePeriod->period_months,
            'title_ru' => $availablePeriod->title_ru ?? '',
            'title_uz' => $availablePeriod->title_uz ?? '',
            'client_photo_upload' => $availablePeriod->client_photo_upload ?? null,
            'total' => 0,   //Конечная цена с учетом всех параметров и кредитов
            'origin' => 0,   //Цена без кредита,
            'month' => 0,
            'partner' => 0,
            'deposit' => 0,
            'balance' => 0,
            'is_available' => false,
            'products' => [],
        ];

        //Формируем договор
        foreach ($products as $key => &$product) {
            //Число товаров в договоре
            $order['origin'] += $product['price'] * $product['amount'];
            $price = $product['price'] * $product['amount'];
            // если есть скидка на конкретный товар, сразу считаем со скидкой
            if (isset($product['price_discount']) && $product['price_discount'] > 0) {
                $price -= ($product['price_discount'] / 100) * $price;
                $order['origin'] -= $order['origin'] * $product['price_discount'] / 100;
            } else {
                $month_discount = $discount / 100;
                $price -= $price * $month_discount;
            }

            //Сумма конкретного договора УЖЕ со скидками
            $order['total'] += $price;
            // цена конкретного товара УЖЕ со скидкой
            if (isset($company->reverse_calc) && $company->reverse_calc == 1) {
                $product['price'] = round(($product['price'] / Config::get('test.rvs') - ($product['price'] * $month_discount)), 2);
            } else {
                $product['price'] = $product['price'] - ($product['price'] * $month_discount);
            }

            $origin = $product['price'];
            $price = $product['price'] + ($product['price'] * $month_markup);

            $order_products[$key] = $product;
            $order_products[$key]['sum'] = $price * $product['amount'];
            $order_products[$key]['price'] = $price;
            $order_products[$key]['origin'] = $origin;

        }
        $order['products'] = $order_products;

        //Сумма конкретного договора для продавца
        $order['partner'] += $order['origin'] - ($order['origin'] * $month_discount);  // со скидкой
        //Округляем до 2 знаков
        $order['origin'] = round($order['origin'], 2);
        $order['total'] = round($order['total'], 2);  // со скидкой
        $order['partner'] = round($order['partner'], 2);
        // обратная калькуляция процентов (уже заложены в цене)
        $rvs = Config::get('test.rvs');
        if (isset($company->reverse_calc) && $company->reverse_calc == 1) {  // если это обратные проценты
            $order['origin'] = $order['origin'] / $rvs;
            $order['total'] = $order['total'] / $rvs;
            $order['partner'] = round($order['partner'] / $rvs); // вычитаем проценты из партнерской цены
        }
        //Конечная цена с учетом кредитной наценки
        $order['total'] += $order['total'] * $month_markup;

        $order['total'] = round($order['total']);
        $order['balance'] = $balance;


        $paymentMonthly = round($order['total'] / $period, 2);
        $paymentMonthlyOrigin = round($order['origin'] / $period, 2);
        $priceOrigin = $order['origin'];


        if ($balance - $order['origin'] >= 0) {
            $order['is_available'] = true;
        }

        $payments = [];
        for ($i = 0; $i < $period; $i++) {
            if ($i < ($period - 1)) {
                $payments[] = [
                    'total' => $paymentMonthly,
                    'origin' => $paymentMonthlyOrigin,
                ];
            } else {
                $payments[] = [
                    'total' => round($order['total'] - $paymentMonthly * ($period - 1), 2),
                    'origin' => round($priceOrigin - $paymentMonthlyOrigin * ($period - 1), 2)
                ];
            }
        }
        $order['month'] = $payments[0]['total'];
        $order['contract']['payments'] = $payments;
        if (!$with_payments) {
            unset($order['contract']);
            unset($order['partner']);
        }

        return $order;
    }

    private function isExpiredContract(Contract $contract): bool
    {
        $created_at = strtotime(Carbon::parse($contract->created_at));
        $today = strtotime(Carbon::now());
        $dif = ($today - $created_at);
        if ($dif > 3600) {    // 1 час
            return true;
        }
        return false;
    }

    private function isNotEnoughBalance(Contract $contract): bool
    {
        if ($contract->price_plan->is_mini_loan) {
            if ($contract->order->credit > $contract->buyer->settings->mini_balance) {
                return true;
            }
        } else {
            if ($contract->order->credit > $contract->buyer->settings->balance) {
                return true;
            }
        }
        return false;
    }

    private function isActiveContractWithSameRequestId(int $ext_order_id,int $company_id):bool {
        if(!empty($ext_order_id) && Contract::where('company_id',$company_id)->where('ext_order_id',$ext_order_id)->where('status',Contract::STATUS_ACTIVE)->exists()) {
            return true;
        }
        return false;
    }

    private function setRedisKey($contract_id) : void
    {
        $key = Hash::make('mfo-payment'.time());
        try {
            Redis::set('mfo_contract_activation_'.$contract_id,$key,'EX', '10');
        }
        catch (\Exception $exception) {
            Log::channel('mfo_order')->error(self::class.'->error->'.$exception->getMessage());
        }
    }

    private function getRedisKey($contract_id)
    {
        try {
            return Redis::get('mfo_contract_activation_'.$contract_id);
        }
        catch (\Exception $exception) {
            Log::channel('mfo_order')->error(self::class.'->error->'.$exception->getMessage());
            return false;
        }
    }

    private function deleteRedisKey($contract_id):void
    {
        try {
            Redis::del('mfo_contract_activation_'.$contract_id);
        }
        catch (\Exception $exception) {
            Log::channel('mfo_order')->error(self::class.'->error->'.$exception->getMessage());
        }
    }

}
