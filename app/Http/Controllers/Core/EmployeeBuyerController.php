<?php


namespace App\Http\Controllers\Core;


// Helpers
use App\Helpers\EncryptHelper;
use App\Helpers\FileHelper;
use App\Helpers\NotificationHelper;
use App\Helpers\SmsHelper;

// Models
use App\Models\BlackList;
use App\Models\Buyer;
use App\Models\Buyer as Model;
use App\Models\BuyerAddress;
use App\Models\BuyerPersonal;
use App\Models\Card;
use App\Models\Contract;
use App\Models\KatmRegion;
use App\Models\KycHistory;
use App\Models\User;

// Services
use App\Services\API\Core\AutopayDebitHistoryService;

// Laravel
use App\Services\TestCardService;
use DDZobov\PivotSoftDeletes\Tests\Integration\Database\DatabaseTestCase;
use Illuminate\Http\Request;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;

// Enums
use App\Enums\BuyerPersonalsEnum;

// FormRequest Validators
use App\Http\Requests\Core\EmployeeBuyerController\SearchBuyerRequest;
use App\Http\Requests\SaveBuyerPersonalPhotoData;
use App\Http\Requests\ShowOverdueContractsRequest;

// API Resources & API ResourceCollections
use App\Http\Resources\Core\EmployeeBuyerController\BuyerJsonResourceCollection;


class EmployeeBuyerController extends CoreController
{

    private $config = [
        'status' => null
    ];
    /**
     * Fields validator
     *
     * @param array $data
     *
     * @return Validator
     */
    private $validatorRules = [
        'name' => ['required', 'string', 'max:255'/*, 'regex:/^([A-Z]{1})([a-z` ]*)$/u'*/],
        'surname' => ['required', 'string', 'max:255'/*, 'regex:/^([A-Z]{1})([a-z` ]*)$/u'*/],
        'patronymic' => ['nullable', 'string', 'max:255'/*, 'regex:/^([A-Z]{1})([a-z` ]*)$/u'*/],
        'phone' => ['nullable', 'string', 'max:20'],

        /*'birthday'              => [ 'required' ],
        'home_phone'            => [ 'nullable', 'string', 'max:20' ],*/

        'pinfl' => ['required'],
        'passport_number' => ['required'],
        'passport_issued_by' => ['required'],
        'passport_date_issue' => ['required'],
        //'city_birth'            => [ 'nullable' ],

        //'address_registration_region'   => [ 'required' ],
        'address_registration_address' => ['required'],
        //'address_registration_area'     => [ 'required' ],

        /*'address_residential_region'    => [ 'required' ],
        'address_residential_area'      => [ 'required' ],
        'address_residential_address'   => [ 'required' ],*/

        //'limit'                 => [ 'required' ],

        /*'work_company'          => [ 'required', 'string', 'max:255' ],
        'work_phone'            => [ 'nullable', 'string', 'max:20' ],*/
    ];

    private $encryptedFields = [
        'birthday',
        'city_birth',
        'work_company',
        'work_phone',
        'passport_number',
        'passport_date_issue',
        'passport_issued_by',
        'passport_expire_date',
        'home_phone',
        'pinfl',
        'inn',
        'social_vk',
        'social_facebook',
        'social_linkedin',
        'social_instagram',
    ];


    private $addressFields = ['region', 'area', 'city', 'address'];

    public function __construct()
    {
        parent::__construct();
        $this->config['status'] = Config::get('test.user_status');
        $this->model = app(Model::class);
    }

    /**
     * @param array $params
     *
     * @return array
     */
    public function filter($params = [])
    {

        $buyersId = [];

        /*
         * тестовые данные
         * if(!empty($params['status'])) {
            $buyersId = User::whereRoleIs('buyer')->where('status', $params['status'])->pluck('id')->toArray() ?? [];
        }else{
            $buyersId = []; // User::whereRoleIs('buyer')->pluck('id')->toArray() ?? [];
        }*/

        $paramsSearch = [];

        //Searching query
        if (isset($params['search']) || isset($params['searchID'])) {

            if (isset($params['search']) && $params['search'] != '') {
                if (is_numeric($params['search'])) {
                    $paramsSearch['phone__like'] = $params['search'];
                } else {
                    $paramsSearch['or__surname__like'] = $params['search'];
                    $paramsSearch['or__name__like'] = $params['search'];
                    $paramsSearch['or__patronymic__like'] = $params['search'];
                }
                unset($params['search']);
            }

            if (isset($params['searchID']) && $params['searchID'] != '') {
                $paramsSearch['id'] = $params['searchID'];
                unset($params['searchID']);

            }

            $filterSearch = parent::filter($paramsSearch);
            $filterSearch = $filterSearch['result']->pluck('id')->toArray();

            //$buyersId = array_intersect( $filterSearch, $buyersId );
            $params['id'] = $filterSearch;
        }

        // $params['id'] = $buyersId;

        return parent::filter($params);
    }


    /**
     * @param array $params
     *
     * @return array
     */
    public function list(array $params = [])  // route: "{BASE_URL}/ru/panel/buyers/list"
    {

        $user = Auth::user();
        $request = request()->all();

        if (isset($request['api_token'])) {
            unset($request['api_token']);
            $params = $request;
        }

        //Filter elements
        $filter = $this->filter($params);

        foreach ($filter['result'] as $index => $item) {

            if ($user->can('detail', $item)) {
                $item->permissions = $this->permissions($item, $user);
                $totalDebt = 0;
                foreach ($item->contracts->whereIn('status', [Contract::STATUS_ACTIVE, Contract::STATUS_OVERDUE_30_DAYS, Contract::STATUS_OVERDUE_60_DAYS]) as $contract) {
                    foreach ($contract->debts as $debt) {
                        $totalDebt += $debt->total;
                    }
                }

                if ($item->personals) {
                    foreach ($item->personals->getAttributes() as $key => $value) {
                        $item->personals[$key] = in_array($key, $this->encryptedFields) ? EncryptHelper::decryptData($value) : $value;
                    }
                }
                if ($item->addresses) {
                    foreach ($item->addresses as $address) {
                        foreach ($address->getAttributes() as $key => $value) {
                            if (in_array($key, $this->addressFields)) {
                                $address->string .= ($address->string ? ', ' : '') . $value;
                            }
                        }
                    }
                }

                $item->totalDebt = $totalDebt;

                $item->status_caption = __('user.status_' . $item->status);
            } else {
                $filter['result']->forget($index);
            }
        }
        $this->result['status'] = 'success';
        $this->result['response']['total'] = $filter['total'];


        //Format data
        if (isset($params['list_type']) && $params['list_type'] == 'data_tables') {
            $filter['result'] = $this->formatDataTables($filter['result']);
        }

        //Collect data
        $this->result['data'] = $filter['result'];

        //Return data
        return $this->result();
    }

    /**
     * Route: "{BASE_URL}/api/v1/employee/buyers/search"
     * Auth: Bearer Token
     * Params:
     *   {
     *       "buyer_id":         (integer, 8)                        id    - nullable
     *       "phone":            (integer, 12|string, 12)  9989XYYYZZZZ    - nullable
     *       "name":             (string, 32)                  "Nurlan"    - nullable
     *       "surname":          (string, 64)              "Sarsenbaev"    - nullable
     *       "passport_number":  (string, 32)          "md5(XX1234567)"    - nullable
     *       "status":           (integer, 2)                 status_id    - nullable
     *   }
     *
     * @param SearchBuyerRequest $request
     *
     * @return BuyerJsonResourceCollection
     */
    protected function search ( SearchBuyerRequest $request ) : BuyerJsonResourceCollection {

        $validated_data  = $request->validated();

        $buyer_id        = data_get($validated_data, "buyer_id");
        $status          = data_get($validated_data, "status");

        $phone           = data_get($validated_data, "phone");
        $name            = data_get($validated_data, "name");
        $surname         = data_get($validated_data, "surname");
        $passport_number = data_get($validated_data, "passport_number");

        $query = Buyer::with([
            'contracts',
            'history:id,user_id,reason',
            'kyc:id,kyc_id,name,surname',
            'personals:id,user_id,passport_number',
            'settings:id,user_id,limit',
        ])
            ->select(  // buyer
                "id",
                "phone",
                "name",
                "surname",
                "patronymic",
                "gender",
                "birth_date",
                "black_list",
                "status",
                "kyc_id",
                "created_at",
                "updated_at",
            )
            ->orderByDesc("updated_at")  // аналог ->orderBy('updated_at', 'desc')
        ;
        if ( !is_null( $status ) ) {    // (int) 0-99
//            $query->where("status", 2);  // Не верифицированные (37) - со счётчиком
//            $query->where("status", 4);  // Верифицированные
//            $query->where("status", 5);  // Нужны фото
//            $query->where("status", 8);  // Отказано
            $query->where("status", $status);
        }

        if ( $buyer_id ) {
            $buyer = $query->find($buyer_id);
            if ( !$buyer ) {
                return new BuyerJsonResourceCollection(new Collection());
            }
            return new BuyerJsonResourceCollection([$buyer]);
        }

        if ($passport_number) {
            $buyer_personals = BuyerPersonal::select("id", "user_id", "passport_number_hash")
                ->where("passport_number_hash" , $passport_number)
                ->get()
                ->toArray()
            ;

            if ( !$buyer_personals || ( count($buyer_personals) === 0 ) ) {
                return new BuyerJsonResourceCollection(new Collection());
            }

            $buyer_personal_user_ids = [];
            if (count($buyer_personals) > 1) {
                foreach ($buyer_personals as $key => $value) {
                    $buyer_personal_user_ids[] = $value["user_id"];
                }
            } else {
                $buyer_personal_user_ids = [$buyer_personals[0]["user_id"]];
            }

            $buyers = $query->whereIn("id", $buyer_personal_user_ids)->get();
            if ( !$buyers ) {
                return new BuyerJsonResourceCollection(new Collection());
            }
            return new BuyerJsonResourceCollection($buyers);
        }

        if ( $phone ) {
            $query->where("phone", "like", "%$phone%");
        }
        if ( $name ) {
            $query->where("name", "like", "%$name%")
                ->orWhere("surname", "like", "%$name%")
            ;
        }
        if ( $surname ) {
            $query->where("surname", "like", "%$surname%")
                ->orWhere("name", "like", "%$surname%")
            ;
        }

        $buyers = $query->paginate(15);

        return new BuyerJsonResourceCollection($buyers);
    }

    /**
     * @param $id
     * @param array $with
     *
     * @return Builder|\Illuminate\Database\Eloquent\Model|object
     */
    protected function single($id, $with = [])
    {
        $single = parent::single($id, array_merge($this->loadWith, []));

        if (!$single) {
            return redirect()->to('panel.login');
        }

        $single->status_list = Config::get('test.user_status');
        $single->status_caption = __('user.status_' . $single->status);

        $dt = new \DateTime();
        $single->scoring = [
            'date_start' => $dt->modify('-6 months')->format('d.m.Y'),
            'date_end' => date('d.m.Y'),
            'sum' => 1000000,
        ];

        if ($single->personals) {
            foreach ($single->personals->getAttributes() as $key => $value) {
                $single->personals[$key] = in_array($key, $this->encryptedFields) ? EncryptHelper::decryptData($value) : $value;
            }
        }

        $single->totalDebt = 0;

        foreach ($single->full_debts as $debt) {
            if( $debt->contract_status !== Contract::STATUS_COMPLETED || empty($debt->cancel_reason) ){
                $single->totalDebt += $debt->balance;
            }
        }

        if(isset($single->deptOfMIB) && !empty($single->deptOfMIB)) {
            foreach ($single->deptOfMIB as $debt) {
                $single->totalDebt += $debt->balance;
            }
        }

        if(isset($single->deptOfAutopayHistory) && !empty($single->deptOfAutopayHistory)) {
            foreach ($single->deptOfAutopayHistory as $debt) {
                $single->totalDebt += $debt->balance;
            }
        }
        $katmRegion = KatmRegion::orderBy('local_region_name')->get();
        $regions = [];

        for ($k = 0; $k < sizeof($katmRegion); $k++)
        {
            $item = $katmRegion[$k];
            $regions[$item->region]['name'] = $item->region_name;
            $regions[$item->region]['id'] = $item->region;
            $regions[$item->region]['local_region'][] = ['id' => $item->local_region, 'name' => $item->local_region_name];
        }

        $single->katm_regions = json_encode($regions);

        return $single;
    }


    /**
     * @param $id
     *
     * @return array|bool|false|string
     */
    public function detail($id)
    {
        $buyer = $this->single($id, 'cards', 'guarants', 'cardsPnfl');

        $user = Auth::user();

        if ($buyer) {
            if ($user->can('detail', $buyer)) {

                $buyer->phonesEquals = true;

                if ($cards = $buyer->cards) {
                    foreach ($cards as $card) {
                        if ( EncryptHelper::decryptData($card->phone) !== $buyer->phone ) {
                            $buyer->phonesEquals = false;
                            break;
                        }
                    }
                }
//                // pnfl uzcard
//                if ($cards_pnfl = $buyer->cardsPnfl) {
//                // Автор Татьяна Пашкова, тут было пусто, закомментировал Сарсенбаев Нурлан 25.10.2022 10:50
//                }
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
     * @param Request $request
     *
     * @return array|bool|false|string
     */
    public function modify(Request $request)
    {
        $user = Auth::user();

        $buyer = Model::find($request->buyer_id);

        if ($buyer) {
            if ($user->can('modify', $buyer)) {

                $validator = $this->validator($request->all(), $this->validatorRules);
                if ($validator->fails()) {
                    // error
                    $this->result['status'] = 'error';
                    $this->result['response']['errors'] = $validator->errors();

                } else {

                    $buyer->name = $request->name;
                    $buyer->surname = $request->surname;
                    $buyer->patronymic = $request->patronymic;

                    $isStatusChange = false;

                    Log::info('employe/buyers/modify');
                    Log::info($request);

                    // Log::channel("payme")->info('rs: '.$request->status . ' vm: ' . $request->verify_message . ' bs: ' . $buyer->status);
                    if ($request->status) {

                        $old_status = $buyer->status;

                        if ($request->status == 4) {  // верифицирован
                            $buyer->verified_at = date("Y-m-d H:i:s");
                        }

                        if ($request->status == 3 || $request->status == 2) {// отказ в верификации или ожидает верификацию
                            $buyer->verify_message = $request->verify_message;

                            $buyer->status = $request->status;

                            // если месадж - недостаточно документов, отправляем смс
                            if ($request->verify_message == "Плохое фото паспорта" && $request->status == 3 && $buyer->status != 5) {
                                $txt = "resusNasiya / Hurmatli foydalanuvchi, iltimos pasport suratini sifatliroq qilib yuklang. Tel: " . callCenterNumber(2);

                                SmsHelper::sendSms($buyer->phone, $txt);
                                $buyer->status = 5; //$request->status;
                                Log::channel('payme')->info('status 5');
                            }

                            if ($request->verify_message == "Плохое селфи с паспортом" && $request->status == 3 && $buyer->status != 10) {

                                $txt = 'resusNasiya / Hurmatli foydalanuvchi, iltimos pasport bilan selfi suratini sifatliroq qilib yuklang. Tel: ' . callCenterNumber(2);

                                SmsHelper::sendSms($buyer->phone, $txt);
                                $buyer->status = 10;
                            }

                            if ($request->verify_message == "Плохое фото прописки" && $request->status == 3 && $buyer->status != 11) {
                                // $txt = "Uvajaemiy polzovatel, prosim vas perezagruzit fotografiyu propiski v polnom razvorote v lushem kachestve. Test Hurmatli foydalanuvchi, iltimos pasportingizni propiska qo'yilgan saxifasining ikki betini to'liq rasmini joylang.";
                                $txt = "Hurmatli foydalanuvchi, iltimos pasportingizni propiska qo'yilgan saxifasining ikki betini to'liq rasmini joylang. Tel: " . callCenterNumber(2);
                                SmsHelper::sendSms($buyer->phone, $txt);
                                $buyer->status = 11;
                            }

                            $reasons = [
                                "Отказ по возрасту",
                                "ИНВ",
                                "Испорченный документ",
                                "Фрод",
                                "ЛБТ"
                            ];
                            if(in_array($request->verify_message, $reasons) && $request->status == 3 && $buyer->status != 8){
                                $txt = "resusNasiya / Hurmatli mijoz, siz platformada ro'yxatdan o'tmadingiz. Tel: " . callCenterNumber(2);
                                SmsHelper::sendSms($buyer->phone, $txt);
                                $buyer->status = 8;
                            }

                        } else {
                            $buyer->verify_message = "";
                            $buyer->status = $request->status;
                        }

                        // 01.06
                        if ($request->verify_message == 'Держатель карты не соответствует') {
                            $txt = "resusNasiya / Karta egasi ro'yxatdan o'tgan foydalanuvchiga to'g'ri kelmaydi. Shaxsiy kartangizni qo'shing. Tel: " . callCenterNumber(2);
                            SmsHelper::sendSms($buyer->phone, $txt);
                            $buyer->status = 1;
                            // ищем карту, предполагается что на данном этапе карта 1
                            if ($card = Card::where('user_id', $buyer->id)->first()) {

                                $cardToken = $card->token_payment;
                                $card->delete(); // удаляем карту,

                                if ($cardToken) {
                                    $cardDeleteResponse = (new TestCardService())->delete($cardToken);
                                    if ($cardDeleteResponse['status'] == 'error') {
                                        Log::channel('cards_v2')->info("Couldn't delete card by token", [
                                            "Token" => $cardToken,
                                            "Message" => $cardDeleteResponse['message'],
                                        ]);
                                    }
                                }
                            }

                            Log::channel('payme')->info('status 1');
                        }

                        $buyer->verified_by = $user->id;  // ID продавца
                        $isStatusChange = $request->status != $buyer->status;

                        if ($old_status != $buyer->status) User::changeStatus($buyer, $buyer->status);

                    }

                    // 04,06 - запись в историю
                    $buyer->kyc_id = Auth::user()->id;
                    $buyer->kyc_status = User::KYC_STATUS_CREATE; // User::KYC_STATUS_BLOCKED;
                    $buyer->birth_date = !empty($request->birthday) ? date('Y-m-d',strtotime($request->birthday)) : null;

                    KycHistory::insertHistory($buyer->id, User::KYC_STATUS_EDIT, User::KYC_STATUS_BLOCKED, $request->verify_message);

                    $buyer->save();

                    if ($isStatusChange) {
                        $notifyData = [
                            'buyer' => $buyer->user,
                            'status' => $buyer->status
                        ];
                        NotificationHelper::buyerStatusChangedByKYC($notifyData, app()->getLocale());
                    }

                    # Buyer personals update
                    $buyerPersonals = $buyer->personals ?? new BuyerPersonal();
                    $buyerPersonals->user_id = $buyer->id;
                    $buyerPersonals->birthday = EncryptHelper::encryptData($request->birthday);
                    $buyerPersonals->home_phone = EncryptHelper::encryptData($request->home_phone);
                    $buyerPersonals->pinfl = EncryptHelper::encryptData($request->pinfl);
                    $buyerPersonals->inn = EncryptHelper::encryptData($request->inn);
                    $buyerPersonals->pinfl_hash = md5($request->pinfl);
                    $buyerPersonals->work_company = EncryptHelper::encryptData($request->work_company);
                    $buyerPersonals->work_phone = EncryptHelper::encryptData($request->work_phone);
                    $buyerPersonals->passport_number = EncryptHelper::encryptData($request->passport_number);
                    $buyerPersonals->passport_number_hash = md5($request->passport_number);
                    $buyerPersonals->passport_issued_by = EncryptHelper::encryptData($request->passport_issued_by);
                    $buyerPersonals->passport_date_issue = EncryptHelper::encryptData($request->passport_date_issue);
                    $buyerPersonals->city_birth = EncryptHelper::encryptData($request->city_birth);
                    $buyerPersonals->save();

                    // изменение пользователя из кабинета вендора

                    # Buyer settings update
                    /* if ( ! isset( $buyer->settings ) )
                        $buyerSettings = new BuyerSetting();
                    else
                        $buyerSettings = $buyer->settings;

                    $buyerSettings->user_id          = $buyer->id;
                    $buyerSettings->rating           = 0;
                    //$buyerSettings->zcoin            = 0;
                    //$buyerSettings->personal_account = 0;
                    $buyerSettings->save(); */

                    $buyerAddressResidential = $buyer->addressResidential ?? new BuyerAddress();
                    $buyerAddressResidential->user_id = $buyer->id;
                    $buyerAddressResidential->type = 'residential';
                    //$buyerAddressResidential->postcode = $request->address_residential_postcode;
                    //$buyerAddressResidential->country  = $request->address_residential_country;
                    $buyerAddressResidential->region = $request->address_residential_region;
                    $buyerAddressResidential->area = $request->address_residential_area;
                    $buyerAddressResidential->city = $request->address_residential_city;
                    $buyerAddressResidential->address = $request->address_residential_address;
                    $buyerAddressResidential->save();

                    $buyerAddressRegistration = $buyer->addressRegistration ?? new BuyerAddress();
                    $buyerAddressRegistration->user_id = $buyer->id;
                    $buyerAddressRegistration->type = 'registration';
                    //$buyerAddressRegistration->postcode = $request->address_registration_postcode;
                    //$buyerAddressRegistration->country  = $request->address_registration_country;
                    $buyerAddressRegistration->region = $request->address_registration_region;
                    $buyerAddressRegistration->area = $request->address_registration_area;
                    $buyerAddressRegistration->city = $request->address_registration_city;
                    $buyerAddressRegistration->address = $request->address_registration_address;
                    $buyerAddressRegistration->save();

                    //Save files
                    $filesToDelete = ($request->files_to_delete != '') ? explode(',', $request->files_to_delete) : [];
                    if (count($request->file()) > 0) {
                        $params = [
                            'files' => $request->file(),
                            'element_id' => $buyerPersonals->id,
                            'model' => 'buyer-personal'
                        ];
                        FileHelper::upload($params, $filesToDelete, true);
                    }

                    $this->result['status'] = 'success';
                    $this->result['data'] = $buyer;
                    $this->message('success', __('panel/buyer.txt_updated'));

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
            $this->message('danger', __('auth.error_user_not_found'));
        }

        return $this->result();

    }

    public function addAdditionalPhoto( SaveBuyerPersonalPhotoData $request ) {

        Log::info('kyc operator begin try adding buyer photo');

        $user               = Auth::user();
        $user_id            = $user->id;

        $validatedData      = $request->validated();
        $file               = $validatedData["file"];
        $type               = $validatedData["type"];
        $reason             = BuyerPersonalsEnum::BUYER_PERSONALS_ENUM["TYPES_REASONS"][$type];
        $buyer              = Model::find( $validatedData["buyer_id"] ); // Model == Buyer()
        $buyer_id           = $buyer->id;

        if (
            is_null($buyer->personals)
            && !in_array($type, BuyerPersonalsEnum::BUYER_PERSONALS_ENUM["LACKING_DOCUMENTS"], true)
        ) {
            $is_bio_passport = in_array($type, BuyerPersonalsEnum::BUYER_PERSONALS_ENUM["BIO_PASSPORT"], true);
            $passport_type = $is_bio_passport ? 6 : 0;  // 0 - id card, 6 - bio passport

            $buyerPersonals = new BuyerPersonal([
                'user_id'                =>   $buyer_id,      // NOT NULL
                'birthday'               =>   null,           // NULL
                'city_birth'             =>   null,           // NULL
                'work_company'           =>   null,           // NULL
                'work_phone'             =>   null,           // NULL
                'passport_number'        =>   null,           // NULL
                'passport_number_hash'   =>   null,           // NULL
                'passport_date_issue'    =>   null,           // NULL
                'passport_issued_by'     =>   null,           // NULL
                'passport_expire_date'   =>   null,           // NULL
                'passport_type'          =>   $passport_type, // NULL
                'home_phone'             =>   null,           // NULL
                'pinfl'                  =>   null,           // NULL
                'pinfl_hash'             =>   null,           // NULL
                'pinfl_status'           =>   1,              // NOT NULL
                'inn'                    =>   null,           // NULL
                'mrz'                    =>   null,           // NULL
                'social_vk'              =>   null,           // NULL
                'social_facebook'        =>   null,           // NULL
                'social_linkedin'        =>   null,           // NULL
                'social_instagram'       =>   null,           // NULL
                'vendor_link'            =>   null            // NULL
            ]);

            $buyer->personals()->save($buyerPersonals);
            $buyer->refresh();
        }

        $buyerPersonals     = $buyer->personals;
        $element_id         = $buyerPersonals->id;

        /** В БД колонку `Files.model` пишется строка обозначающая Модель Eloquent ORM,
         *  и в нашем случае мы пишем "buyer-personal" для фото документов потверждающих личность
         */
        $model  = BuyerPersonalsEnum::BUYER_PERSONALS_FILES_MODEL; // "buyer-personal"

        $this->result['status'] = 'error';

        if ( !($user->can('modify',$buyer)) ) {
            //Error: access denied
            $this->result['response']['code'] = 403;
            $this->message( 'danger', __( 'app.err_access_denied' ) );
            return $this->result();
        }

        if ( !$buyerPersonals ) {
            $this->result['response']['code'] = 404;
            $this->message( 'danger', __( 'app.buyer_personals_not_found' ) );
            return $this->result();
        }

        $file_path = FileHelper::simpleUpload( $file, $type, $model, $element_id, $user_id );
        // OCR

        if (!$file_path) {
            Log::info('kyc operator upload buyer photo error');
            $this->result['response']['code'] = 500;
            $this->message( 'danger', __( 'app.err_upload' ) );
            return $this->result();
        }

        KycHistory::insertHistory(
            $buyer_id,                                  // $buyer_id,
            User::KYC_STATUS_KYC_MODIFY,          // $status,
            null,                              // $kyc_status=null,
            $reason,                                    // $reason=null,
            null,                                  // $_title=null,
            $file_path,                                 // $image=null,
            null,                              // $old_phone=null,
            null                              // $old_address=null
        );

        $translated_buyer_personals = [];
        foreach ($buyerPersonals->files as $buyerPersonal_file) {
            $translated_buyer_personals[] = [
                "href"       => FileHelper::url($buyerPersonal_file->path),
                "imagesrc"   => $buyerPersonal_file->path,
                "doc_path"   => $buyerPersonal_file->doc_path,
                "imagelabel" => __('panel/buyer.'.$buyerPersonal_file->type)
            ];
        }

        $this->result['status'] = 'success';

        $this->result['data']   = $translated_buyer_personals;
        $this->message( 'success', __( 'panel/buyer.txt_data_saved' ) );

        Log::info('kyc operator ends adding buyer photo');

        return $this->result();
    }

    public function getBuyerPersonalsTypes() {

        $buyer_personals_types  = BuyerPersonalsEnum::BUYER_PERSONALS_ENUM["TYPES"]; // "passport_selfie"

        $types = [];

        foreach ($buyer_personals_types as $key => $value) {
            $types[$value] = __('panel/buyer.' . $value);
        }

        $this->result['status'] = 'success';
        $this->result['data']   = $types;

        return $this->result();
    }


    /**
     * @param Request $request
     *
     * @return array|bool|false|string
     */
    public function validateForm(Request $request)
    {
        $validator = $this->validator($request->all(), $this->validatorRules);

        if ($validator->fails()) {
            // error
            $this->result['status'] = 'error';
            $this->message('danger', __('app.err_check_form'));
            $this->result['response']['errors'] = $validator->errors();

        } else {
            $this->result['status'] = 'success';
        }

        return $this->result();
    }


    public function checkPinfl(Request $request)
    {

        Log::channel('katm')->info($request);

        if ($request->pinfl != '') {

            if ($black = BlackList::where('token', $request->pinfl)->first()) {
                $this->result['status'] = 'error';
                $this->error(__('panel/buyer.txt_pinfl_black_list'));

                if (!$buyerPersonal = BuyerPersonal::find($request->buyer_id)) {
                    $buyerPersonal = new BuyerPersonal();
                    $buyerPersonal->pinfl = EncryptHelper::encryptData($request->pinfl);
                    $buyerPersonal->pinfl_hash = md5($request->pinfl);
                    $buyerPersonal->pinfl_status = 0;
                    $buyerPersonal->user_id = $request->buyer_id;
                    $buyerPersonal->save();
                } else {
                    if ($buyerPersonal->pinfl == '' || is_null($buyerPersonal->pinfl)) {
                        $buyerPersonal->pinfl = EncryptHelper::encryptData($request->pinfl);
                        $buyerPersonal->pinfl_hash = md5($request->pinfl);
                        $buyerPersonal->pinfl_status = 0;
                        $buyerPersonal->save();
                    } elseif ($buyerPersonal->pinfl_hash == md5($request->pinfl)) {
                        $buyerPersonal->pinfl_status = 0;
                        $buyerPersonal->save();
                    }
                }
                Log::channel('katm')->info($request->buyer_id . ' Error pinfl black list ' . $request->pinfl);

                return $this->result();
            }

            $results = BuyerPersonal::where('pinfl_hash', md5($request->pinfl))->get();
            if ($results->count() > 0) {
                if ($results->count() > 1) {
                    $ids = implode(', ', $results->pluck('user_id')->toArray());
                    $this->message('danger', __('panel/buyer.txt_found_more_pinfl', ['id' => $ids]));
                    $this->error(__('panel/buyer.txt_found_more_pinfl', ['id' => $ids]));

                    foreach ($results as $item) {
                        $item->pinfl_status = 0;
                        $item->save();
                    }

                } else {
                    $userID = $results->first()->user_id;
                    $this->result['status'] = 'success';
                    $this->message('info', __('panel/buyer.txt_found_one_pinfl', ['id' => $userID]));

                    foreach ($results as $item) {
                        $item->pinfl_status = 1;
                        $item->save();
                    }

                }
            } else {
                $this->message('success', __('panel/buyer.txt_pinfl_not_found'));
                foreach ($results as $item) {
                    $item->pinfl_status = 1;
                    $item->save();
                }
            }
        } else {
            $this->result['status'] = 'error';
            $this->message('danger', __('app.err_check_form'));
            $this->error(__('app.err_check_form'));
        }

        return $this->result();
    }


    /**
     * @param Request $request
     * @return array|false|string
     */
    public function sendSms(Request $request)
    {
        $buyer = Model::find($request->buyer_id);

        if ($buyer) {
            if ($request->text) {
                SmsHelper::sendSms($buyer->phone, $request->text);
                $this->result['status'] = 'success';
                $this->message('success', __('panel/buyer.txt_sended'));
            } else {
                $this->result['status'] = 'error';
                $this->message('danger', __('panel/buyer.error_text_empty'));
            }
        } else {
            $this->result['status'] = 'error';
            $this->message('danger', __('panel/buyer.err_buyer_not_found'));
        }

        return $this->result();
    }


    /**
     * @param Request $request
     *
     * @return array|false|string
     */
    public function changeStatus(Request $request)
    {
        $user = Auth::user();
        $buyer = Model::find($request->id);

        if ($buyer) {
            if ($user->can('modify', $buyer) && in_array($request->status, $this->config['status'])) {

                $old_status = $buyer->status;

                if ($old_status == 8) {
                    $this->result['status'] = 'error';
                    $this->result['response']['code'] = 400;
                    $this->message('danger', 'Отказано в действии. Пользователь был раннее заблокирован');
                    return $this->result();
                }

                if ($request->status == 3) {
                    $buyer->verify_message = $request->verify_message;
                }
                $buyer->verified_by = $request->verified_by;
                $buyer->status = $request->status;

                if ($request->verify_message == "Плохое фото паспорта" && $request->status == 3 && $buyer->status != 5) {
                    // $txt = "Uvajaemiy polzovatel, prosim vas perezagruzit fotografiyu pasporta v lushem kachestve. test Hurmatli foydalanuvchi, iltimos pasport suratini sifatliroq qilib yuklang.";
                    $txt = "resusNasiya / Hurmatli foydalanuvchi, iltimos pasport suratini sifatliroq qilib yuklang. Tel: " . callCenterNumber(2);

                    SmsHelper::sendSms($buyer->phone, $txt);
                    $buyer->status = 5;
                }

                if ($request->verify_message == "Плохое селфи с паспортом" && $request->status == 3 && $buyer->status != 10) {
                    //$txt = "Uvajaemiy polzovatel, prosim vas perezagruzit fotografiyu selfie pasporta v lushem kachestve. test Hurmatli foydalanuvchi, iltimos selfie pasport suratini sifatliroq qilib yuklang.";
                    $txt = 'resusNasiya / Hurmatli foydalanuvchi, iltimos pasport bilan selfi suratini sifatliroq qilib yuklang. Tel: ' . callCenterNumber(2);

                    SmsHelper::sendSms($buyer->phone, $txt);
                    $buyer->status = 10;
                }

                if ($request->verify_message == "Плохое фото прописки" && $request->status == 3 && $buyer->status != 11) {
                    // $txt = "Uvajaemiy polzovatel, prosim vas perezagruzit fotografiyu propiski v polnom razvorote v lushem kachestve. test Hurmatli foydalanuvchi, iltimos pasportingizni propiska qo'yilgan saxifasining ikki betini to'liq rasmini joylang.";
                    $txt = "Hurmatli foydalanuvchi, iltimos pasportingizni propiska qo'yilgan saxifasining ikki betini to'liq rasmini joylang. Tel: " . callCenterNumber(2);

                    SmsHelper::sendSms($buyer->phone, $txt);
                    $buyer->status = 11;
                }

                $reasons = [
                    "Отказ по возрасту",
                    "ИНВ",
                    "Испорченный документ",
                    "Фрод",
                    "ЛБТ"
                ];
                if(in_array($request->verify_message, $reasons) && $request->status == 3 && $buyer->status != 8){
                    $txt = "Hurmatli mijoz, siz resusNasiya platformasida ro'yxatdan o'tmadingiz. Tel: " . callCenterNumber(2);
                    SmsHelper::sendSms($buyer->phone, $txt);
                    $buyer->status = 8;
                }


                // 01.06
                if ($request->verify_message == 'Держатель карты не соответствует') {
                    $txt = "resusNasiya / Karta egasi ro'yxatdan o'tgan foydalanuvchiga to'g'ri kelmaydi. Shaxsiy kartangizni qo'shing. Tel: " . callCenterNumber(2);

                    SmsHelper::sendSms($buyer->phone, $txt);
                    $buyer->status = 1;
                    // ищем карту, предполагается что на данном этапе карта 1
                    if ($card = Card::where('user_id', $buyer->id)->first()) {

                        $cardToken = $card->token_payment;
                        $card->delete(); // удаляем карту,

                        if ($cardToken) {
                            $cardDeleteResponse = (new testCardService())->delete($cardToken);
                            if ($cardDeleteResponse['status'] == 'error') {
                                Log::channel('cards_v2')->info("Couldn't delete card by token", [
                                    "Token" => $cardToken,
                                    "Message" => $cardDeleteResponse['message'],
                                ]);
                            }
                        }
                    }
                    Log::channel('payme')->info('status 1');
                }

                // 12.11.21 dev_t
                if ($request->verify_message == 'Недостаточно средств на карте') {
                    $str = EncryptHelper::decryptData($buyer->card->card_number);
                    $card_number = substr($str, 0, 4) . '****' . substr($str, -4);
                    $txt = "Hurmatli mijoz, sizning " . $card_number ." kartangiz tekshiruvdan o'tmadi. Kartadagi qoldiq kamida 1000 so'm bo'lishi kerak. Qayta tekshirish uchun kartani to'ldiring. Tel: " . callCenterNumber(2);
                    $buyer->status = 2;

                    SmsHelper::sendSms($buyer->phone, $txt);
                    KycHistory::insertHistory($buyer->id, User::KYC_STATUS_UPDATE, User::KYC_STATUS_SCORING_CARD_BALANCE, $request->verify_message);
                }else{
                    // 04,06 - запись в историю
                    $buyer->kyc_id = Auth::user()->id;
                    $buyer->kyc_status = User::KYC_STATUS_CREATE; // User::KYC_STATUS_BLOCKED;
                    KycHistory::insertHistory($buyer->id, User::KYC_STATUS_UPDATE, User::KYC_STATUS_BLOCKED, $request->verify_message);
                }

                // 29.03.2022 new_feature.principal_absent_refusal_reason_for_KYC_moderation
                if ($request->verify_message === "Необходимо добавить доверителя") {
                    $txt = "resusNasiya / Hurmatli foydalanuvchi, aloqa uchun shaxs maydoniga 2 ta har xil shaxs malumotlarini va ularning FISH larini to'liq kiritishingizni so'raymiz. Tel: " . callCenterNumber(2);

                    $buyer->status = 12;

                    SmsHelper::sendSms($buyer->phone, $txt);
                }


                $buyer->save();

                if ($old_status != $buyer->status) User::changeStatus($buyer, $buyer->status);


                $this->result['status'] = 'success';
                $this->message('success', __('panel/buyer.txt_status_changed'));
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

    public function showOverdueContracts(ShowOverdueContractsRequest $request) {

        if ( !(Auth::user()->hasRole(['admin', 'kyc'])) ) {
            return response()->json([
                'status' => "error",
                'message' => __('app.err_access_denied')
            ], 403);
        }

        $debts = (new AutopayDebitHistoryService())->getOverdueContracts($request->validated()["buyer_id"]);

        if ( empty($debts) ) {
            return response()->json([
                "status" => "success",
                "message" => []
            ], 200);
        }

        return response()->json([
            "status" => "success",
            "message" => trans_choice("panel/buyer.autopay_debt_found", count($debts), ["debt" => implode(", ", $debts)])
        ], 200);
    }

    public function showOverdueContractsForBuyer() {
        $user = Auth::user();

        if ( !($user->hasRole(['buyer'])) ) {
            return response()->json([
                'status' => "error",
                'message' => __("app.err_access_denied")
            ], 403);
        }

        $debts = (new AutopayDebitHistoryService())->getOverdueContracts($user->id);

        if ( empty($debts) ) {
            return response()->json([
                "status" => "success",
                "message" => []
            ], 200);
        }

        return response()->json([
            "status" => "success",
            "message" => trans_choice("panel/buyer.buyer_autopay_debt_found", count($debts), ["debt" => implode(", ", $debts)])
        ], 200);
    }


}
