<?php

namespace App\Services\API\V3\Partners;

use App\Helpers\FileHelper;
use App\Http\Controllers\Core\UniversalController;
use App\Http\Requests\V3\UploadPassportDocsRequest;
use App\Models\AvailablePeriod;
use App\Models\Buyer;
use App\Models\BuyerGuarant;
use App\Models\BuyerPersonal;
use App\Models\Contract;
use App\Models\KycHistory;
use App\Models\Partner;
use App\Models\PartnerSetting;
use App\Models\Saller;
use App\Models\StaffPersonal;
use App\Models\User;
use App\Rules\CheckGuarantPhone;
use App\Rules\Uppercase;
use App\Services\API\V3\BaseService;
use App\Services\API\V3\LoginService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PartnerBuyerService extends BaseService
{
    public static $rules1;
    public static $rules2;

    public function __construct()
    {
        self::$rules1 = ['phone' => 'required|numeric|digits:12|regex:/(998)[0-9]{9}/',];
        self::$rules2 = [
            'phone' => 'required|numeric|regex:/(998)[0-9]{9}/',
            'name' => ['required', 'string', 'max:20', 'regex:/[A-Za-z]/i', new Uppercase],
            'surname' => ['required', 'string', 'max:45', 'regex:/[A-Za-z]/i', new Uppercase],
            'patronymic' => ['required', 'string', 'max:45', 'regex:/[A-Za-z]/i', new Uppercase],
            'birthday' => ['nullable', 'string', 'max:255'],
            'work_company' => ['nullable', 'string', 'max:255'],
            'work_phone' => ['required_without:home_phone', 'max:255'],
            'home_phone' => ['required_without:work_phone', 'max:255'],
            'pinfl' => ['nullable', 'string', 'max:255'],
            'address_region' => ['required'],
            'address_area' => ['required'],
            'address_city' => ['sometimes'],
            'address' => ['required', 'string'],
            'passport_selfie' => ['required', 'image'],
            'passport_first_page' => ['required', 'image'],
            'passport_with_address' => ['required', 'image'],
            'card_number' => ['nullable', 'string', 'max:255'],
            'card_valid_date' => ['nullable', 'string', 'max:255'],
        ];
    }

    public static function validatePhone(Request $request)
    {
        $inputs = $request->only('phone');
        $validator = Validator::make($inputs, [
            'phone' => 'required|numeric|digits:12|regex:/(998)[0-9]{9}/',
        ]);
        if ($validator->fails()) {
            self::handleError($validator->errors()->getMessages());
        }
        return $validator->validated();
    }

    public static function validateAddGuarant(Request $request)
    {
        Log::channel('guarantor')->info(self::class.'->request->'.json_encode($request->all(),JSON_UNESCAPED_UNICODE).'ID='.Auth::id());
        $validator = Validator::make($request->all(), [
            'data' => 'required|array|size:2',
            'data.*.name' => 'required|string',
            'data.*.phone' => ['required','numeric','regex:/(998)[0-9]{9}/', new CheckGuarantPhone],
            'buyer_id' => 'required|exists:users,id',
        ]);
        if ($validator->fails()) {
            Log::channel('guarantor')->info(self::class.'->validation->'.json_encode($validator->errors()->getMessages(),JSON_UNESCAPED_UNICODE).'ID='.Auth::id());
            return self::handleError($validator->errors()->getMessages());
        }
        return $validator->validated();
    }

    public static function checkSmsCode(Request $request)
    {
        $inputs = LoginService::validateAuth($request);
        $response = LoginService::checkSmsCode($request);
        if ($response['code'] == 1) {
            $user = User::where("phone", $inputs['phone'])->first();
            //Add buyer
            $user = self::add($request);
            $data['user_status'] = $user->status;
            $data['user_id'] = $user->id;
            $data['api_token'] = $user->api_token;
            $data['is_seller'] = $user->is_saller ?? 0;

            $data['address_is_received'] = ($user->addressRegistration && $user->addressRegistration->address !== null) ? 1 : 0;

            return self::handleResponse($data);
        }
        return self::handleError([__('auth.error_code_wrong')]);
    }

    public static function checkVip(Request $request)
    {
        $user = Auth::user();
        $partner = Partner::with('company')->find($user->id);
        $vip = $partner->company->vip ? 1 : 0;
        $buyer = Buyer::where('phone', $request->phone)->first();
        if (!$buyer) {
            return self::handleError([__('panel/buyer.err_buyer_not_found')], 'error', 404);
        }
        if ($vip) {
            if (!$buyer->vip) {
                $buyer->vip = 1;
                // поменять лимит на 7000000
                if ($buyer->status >= 5) {
                    $limit = Config::get('test.vip_limit');  // 7000000
                    $buyer->settings->limit = $limit;
                    $buyer->settings->balance = $limit;
                    $buyer->settings->save();
                }
            }
        } else {
            if ($buyer->vip) {
                $buyer->vip = 0;
                // если вип стал не вип, и его карта проигнорировала скоринг
                if ($buyer->status >= 5) {
                    // проскорить карту заново, выдать лимит по скорингу
                    $request->merge(['buyer_id' => $buyer->id]);
                    $scoring_result = UniversalController::cardRescoring($request);
                }
            }
        }
        if ($user->id != $buyer->created_by) $buyer->created_by = $user->id;
        $buyer->save();
        $result = ['vip' => $buyer->vip];

        return self::handleResponse($result);
    }

    public static function add(Request $request): User
    {
        $validator = Validator::make($request->all(), self::$rules1);
        if ($validator->fails()) {
            return self::handleError($validator->errors()->getMessages());
        }
        $phone = $request->phone;
        $user = Auth::user();
        $partner = Partner::find($user->id);
        $buyer = Buyer::where('phone', correct_phone($phone))->first();
        $log_info = [
            'class' => self::class,
            'method' => 'add',
            'info' => 'vendor id:' . $user->id,
            'request' => $request->all(),
        ];
        if (!$buyer) {
            $buyer = new User();
            $buyer->phone = $phone;
            $buyer->created_by = $user->id;
            $buyer->status = User::STATUS_CARD_ADD;
            $buyer->doc_path = 1; //  файлы на новом сервере
            if ($partner->company->vip) $buyer->vip = 1;   // если вендор сам платит за клиента
            $buyer->save();
            $buyer->attachRole('buyer');
            //Create personals
            $personals = new BuyerPersonal();
            $personals->user_id = $buyer->id;
            $personals->save();
            KycHistory::insertHistory($buyer->id, User::KYC_STATUS_CREATE, User::KYC_STATUS_NULL);
            LoginService::generateApiToken($buyer);
            $log_info['info'] .= ' - new buyer created ID: ' . $buyer->id;
        }
        if ($buyer->status == User::KYC_STATUS_BLOCKED) {
            return self::handleError([__('app.user_is_blocked')]);
        }
        Log::info($log_info);
        return $buyer;
    }

    public static function addGuarant(Request $request)
    {
        $inputs = $request->all();
        $buyer = Buyer::find($inputs['buyer_id']);
        if (!$buyer) {
            return self::handleError([__('auth.error_user_not_found')]);
        }
        $buyer_status = $buyer->status;
        Log::channel('guarantor')->info(self::class.'->start->ID='.$buyer->id.' STATUS='.$buyer_status);
        if ($buyer->status == User::KYC_STATUS_BLOCKED) {
            return self::handleError([__('app.user_is_blocked')]);
        }
        if ($buyer->status == User::KYC_STATUS_VERIFY) {
            return self::handleError([__('api.user_verified')]);
        }
        $errors = [];
        $guarants = $inputs['data'];
        //Check for duplicate phone numbers
        if (Arr::first($guarants)['phone'] == Arr::last($guarants)['phone']) {
            $errors[] = __('api.duplicate_phone_number');
        }
        // Check for buyers phone not equals to guarant's phone
        if (Arr::first($guarants)['phone'] == $buyer->phone || Arr::last($guarants)['phone'] == $buyer->phone) {
            $errors[] = __('api.user_phone_equals_to_buyers');
        }
        if (count($errors) > 0) {
            Log::channel('guarantor')->info(self::class.'->validation->'.json_encode($errors,JSON_UNESCAPED_UNICODE).' ID='.$buyer->id);
            return self::handleError($errors);
        }
        foreach ($guarants as $guarant) {
            $buyerGuarant = new BuyerGuarant();
            $buyerGuarant->name = $guarant['name'];
            $buyerGuarant->phone = $guarant['phone'];
            $buyerGuarant->user_id = $buyer->id;
            $buyerGuarant->save();
        }
        self::changeUserStatus($buyer, 2);
        KycHistory::insertHistory($buyer->id, User::KYC_STATUS_UPDATE);
        Log::channel('guarantor')->info(self::class.'->Buyer status changed from '.$buyer_status.' to '. 2 .' ID='.$buyer->id);
        return self::handleResponse();
    }

    public function getPartnerDetailInformation()
    {
        $user = Auth::user();

        $partner = Partner::where('id', $user->id)->first();

        //getting-relation
        $company = $partner->company;
        $settings = $partner->settings;

        $limits_arr = [3,6,9,12];
        $limits = [];

        foreach ($limits_arr as $limit) {
            $flag = $settings['limit_'.$limit];
            if($flag) {
                $limits[] = $limit;
            }
        }

        $sellers = Saller::where('seller_company_id', $company->id)->where('is_saller', 1)->get(['id', 'name', 'surname', 'patronymic', 'phone']);

        $result = [
            'partner_id' => $user->id,
            'company_id' => $company->id,
            'partner_name' => $company->name,
            'partner_address' => $company->address,
            'partner_phone' => $company->phone,
            'general_company' => $company->general_company_id,
            'is_allowed_online_signature' => $company->is_allowed_online_signature,
            'seller_coefficient' => (float)$company->seller_coefficient,
            'seller_list' => $sellers->toArray(),
            'general_company_id' => $company->general_company_id,
            'limits' => $limits,
        ];

        return self::handleResponse($result);
    }

    public static function uploadPassportDocs(UploadPassportDocsRequest $request)
    {
        $user = Auth::user();
        $buyer = Buyer::find($request->get('buyer_id'));
        if (!$user->hasRole('partner')) {
            return self::handleError([__('app.err_access_denied')]);
        }
        if ($buyer->status == User::KYC_STATUS_VERIFY) {
            return self::handleError([__('api.user_verified')]);
        }
        $buyerPersonals = $buyer->personals ?? new BuyerPersonal();
        $buyerPersonals->user_id = $buyer->id;
        $buyerPersonals->passport_type = $request->get('passport_type');
        $buyerPersonals->save();
        $params = [
            'files' => $request->file(),
            'element_id' => $buyerPersonals->id,
            'model' => 'buyer-personal'
        ];
        FileHelper::upload($params);
        if ($buyer->status == 5) {
            User::changeStatus($buyer, 12);
        }
        // меняем kyc статус покупателя
        $buyer->kyc_status = User::KYC_STATUS_MODIFY;
        $buyer->kyc_id = null;
        $buyer->save();
        // добавляем в историю запись
        KycHistory::insertHistory($buyer->id, User::KYC_STATUS_MODIFY);
        return self::handleResponse();
    }

    public static function checkBuyerStatus(Request $request)
    {

        $partner = Auth::user();


        $buyer = Buyer::with('personals')->where('phone', $request->get('phone'))->first();
        $data = [];

        $link = Config::get('test.webview_link');

        if($partner->id === (int)Config::get('test.resus_partner_id')) {
            $link = Config::get('test.resus_webview_link');
        }

        $data['webview'] = $link . '?phone=' . $request->get('phone');
        if ($request->has('callback')) {
            $data['webview'] .= '&callback=' . $request->get('callback');
        }

        $balance = 0;
        $mini_balance = 0;
        if (isset($buyer)) {
            $balance = $buyer->settings->balance ?? 0;
            $balance = number_format($balance, 2, ".", "");
            $mini_balance = $buyer->settings->mini_balance ?? 0;
            $mini_balance = number_format($mini_balance, 2, ".", "");
            $data['has_overdue_contracts'] = Contract::where('user_id','=',$buyer->id)->whereIn('status',[Contract::STATUS_OVERDUE_60_DAYS,Contract::STATUS_OVERDUE_30_DAYS])->exists() || $buyer->black_list;
        }
        $data['status'] = isset($buyer) ? $buyer->status : 0;
        $data['buyer_id'] = isset($buyer) ? $buyer->id : 0;
        $periods = AvailablePeriod::where('status', AvailablePeriod::STATUS_ACTIVE)
            ->get(['period', 'is_mini_loan', 'title_uz', 'title_ru']);
        foreach ($periods as $period) {
            if ($period->is_mini_loan) {
                $period->available_balance = $mini_balance;
            } else {
                $period->available_balance = $balance;
            }
            unset($period->is_mini_loan);
        }
        $data['available_periods'] = isset($buyer) ? $periods : [];
        return self::handleResponse($data);
    }
}
