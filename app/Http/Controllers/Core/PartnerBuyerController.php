<?php


namespace App\Http\Controllers\Core;


use App\Helpers\CardHelper;
use App\Helpers\EncryptHelper;
use App\Helpers\FileHelper;
use App\Http\Controllers\Core\Auth\AuthController;
use App\Http\Requests\Core\PartnerBuyerController\AddRequest;
use App\Models\Buyer;
use App\Models\Buyer as Model;
use App\Models\BuyerAddress;
use App\Models\BuyerPersonal;
use App\Models\BuyerSetting;
use App\Models\Card;
use App\Models\KycHistory;
use App\Models\Order;
use App\Models\Partner;
use App\Models\User;
use App\Rules\Uppercase;
use App\Services\API\V3\UserPayService;
use App\Traits\SmsTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PartnerBuyerController extends CoreController {

    use SmsTrait;

    private $config;

    /**
     * Fields validator
     *
     * @param array $data
     *
     * @return Validator
    */

    private $validatorRules = [];

    private $customRulesStep1 = [
        'phone' => [ 'required', 'string', 'max:15', 'regex:/^\+?[0-9]+$/', 'unique:users' ]
    ];

    private $customRulesStepPartner = [
        'phone' => [ 'required', 'string', 'max:15', 'regex:/^\+?[0-9]+$/' ]
    ];

    private $customRulesStep2;

    private $messages;



    /**
     * BillingBuyerController constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->model = app( Model::class );
        //Eager load
        $this->loadWith = ['settings', 'personals', 'personals.passport_selfie'];

        $this->config = Config::get('test.buyer_defaults');
        $this->messages = [
            'phone.unique' => Lang::get( 'auth.error_phone_is_used' ),
            'name.regex' => Lang::get('validation.attributes.lat_only'),
            'surname.regex' => Lang::get('validation.attributes.lat_only'),
            'patronymic.regex' => Lang::get('validation.attributes.lat_only'),

        ];

        $this->customRulesStep2 = [
            'name' => ['required', 'string', 'max:20', 'regex:/[A-Za-z]/i', new Uppercase ],
            'surname' => ['required', 'string', 'max:45', 'regex:/[A-Za-z]/i', new Uppercase],
            'patronymic' => ['required', 'string', 'max:45', 'regex:/[A-Za-z]/i', new Uppercase],
            'birthday' => ['nullable', 'string', 'max:255'],
            'work_company' => ['nullable', 'string', 'max:255'],
            'work_phone' => ['required_without:home_phone', 'max:255'],
            'home_phone' => ['required_without:work_phone', 'max:255'],
            'pinfl'            => [ 'nullable', 'string', 'max:255' ],

            'address_region' => ['required'],
            'address_area' => ['required'],
            'address_city' => ['sometimes'],
            'address' => ['required', 'string'],

            'passport_selfie'       => [ 'required', 'image' ],
            'passport_first_page'   => [ 'required', 'image' ],
            'passport_with_address' => [ 'required', 'image' ],

            'card_number'     => [ 'nullable', 'string', 'max:255' ],
            'card_valid_date' => [ 'nullable', 'string', 'max:255' ],
        ];

        $this->customRulesStepNew = [
            'passport_selfie'       => [ 'required', 'image' ],
            'passport_first_page'   => [ 'required', 'image' ],

            'card_number'     => [ 'nullable', 'string', 'max:255' ],
            'card_valid_date' => [ 'nullable', 'string', 'max:255' ],
        ];

        $this->customRulesForIdCard = [
            'id_selfie'       => [ 'required', 'image' ],
            'id_first_page'   => [ 'required', 'image' ],
            'id_second_page'  => [ 'required', 'image' ],
            'id_with_address' => [ 'required', 'image' ],
        ];

    }


    /**
     * @param array $params
     * @return array
     */
    public function filter( $params = [] ) {
        $user = Auth::user();
        //Получаем клиентов, созданных партнером
        $createdById = User::where( 'created_by', $user->id)->pluck( 'id' )->toArray();
        $params['id'] = $createdById??[];

        $partner = Partner::find($user->id);
        if($partner){
            if($company = $partner->company){

                $fromAffiliatesId = Order::whereIn('company_id', $company->affiliates)->pluck( 'user_id' )->toArray();
                if($fromAffiliatesId) $params['id'] = array_merge($params['id'], $fromAffiliatesId);
            }
        }

        //Плюс добавить всех клиентов, которые что-то договорывали и клиентов филиалов
        $fromOrderId = Order::where('partner_id', $user->id)->pluck( 'user_id' )->toArray();
        if($fromOrderId) $params['id'] = array_merge($params['id'], $fromOrderId);

        //Отсеиваем все полученное по строке поиска
        if(isset($params['search'])) {
            if(is_numeric($params['search'])) {
                $paramsSearch['phone__like'] = $params['search'];
            }else {
                $paramsSearch['or__surname__like'] = $params['search'];
                $paramsSearch['or__name__like'] = $params['search'];
                $paramsSearch['or__patronymic__like'] = $params['search'];
            }
            $filterSearch = $this->filter($paramsSearch);
            $filterSearch = $filterSearch['result']->pluck('id')->toArray();
            $params['id'] = array_intersect($params['id'], $filterSearch);

            unset($params['search']);
        }

        return parent::filter( $params ); // TODO: Change the autogenerated stub
    }


    /**  добавление пользователя из кабинета вендора
     * @param Request $request
     * @return array|false|string
     */
    public function add( AddRequest $request )
    {
        Log::info('add buyer from partner');
        Log::info('vendor id: ' . Auth::user()->id);

        $phone = $request->phone;
        $card_exist = 0;
        $user = Auth::user();
        $partner = Partner::find($user->id);

        // если покупатель не существует и его пытается создать партнер из кабинета
        if(!$buyer = Buyer::where('phone',correct_phone($phone))
            ->with('personals', 'personals.passport_selfie', 'personals.passport_first_page', 'personals.passport_with_address', 'guarants')
            ->first() )
        {
            $buyer = new User();
            $buyer->phone = $phone;
            $buyer->created_by = $user->id;
            $buyer->status = 1;
            $buyer->doc_path = 1; // файлы на новом сервере
            if($partner->company->vip) $buyer->vip = 1; // если вендор сам платит за клиента
            $buyer->save();

            $buyer->attachRole( 'buyer' );

            //
            BuyerSetting::create(['user_id' => $buyer->id]);
            (new UserPayService)->createClearingAccount($buyer->id);
            // Create personals
            $personals = new BuyerPersonal();
            $personals->user_id = $buyer->id;
            $personals->save();

            /*  данная информация будет добавляться при скоринге */
            //Buyer settings
            /* $settings = new BuyerSetting();
             $settings->user_id =            $buyer->id;
             $settings->limit =              0; //$this->config['limit'];
             $settings->balance =            0; //$this->config['limit'];
             $settings->zcoin =              0;
             $settings->personal_account =   0;
             $settings->rating =             0;
             $settings->save(); */
            KycHistory::insertHistory($buyer->id,User::KYC_STATUS_CREATE,User::KYC_STATUS_NULL);

            AuthController::generateApiToken( $buyer );

            Log::info('user create PartnerBuyerController->add user_id: ' . $buyer->id);
            Log::info('New buyer set status 1');
        }
        else
        {
            $card = Card::where('user_id', $buyer->id)->first();

            if( $buyer->status == 4 || $buyer->status == 8 )
            {
                Log::info('Buyer exist=4/blocked=8 status: ' . $buyer->status);
            }
            elseif ($card)
            {
                $card_exist = 1;
                $buyer->save();
                Log::info('Buyer card exist ' . $buyer->id);
            }
        }

        $this->result['status']     = 'success';
        $this->result['data']       = $buyer;
        $this->result['data']['ce'] = $card_exist;
        $this->message( 'success', __( 'auth.txt_request_successful' ) );

        return $this->result();
    }

    public function single( $id, $with = [] ) {
        $single = parent::single( $id, array_merge($this->loadWith, $with) );
        $single->status_list = Config::get('test.order_status');
        return $single;
    }

    /**
     * @param $id
     *
     * @return array|bool|false|string
     */

    public function detail( $id ) {
        $buyer = $this->single($id);
        $user = Auth::user();

        if($buyer){
            if ($user->can('detail', $buyer)) {

                $this->result['status'] = 'success';
                $this->result['data'] = $buyer;
            }else {
                //Error: access denied
                $this->result['status'] = 'error';
                $this->result['response']['code'] = 403;
                $this->message('danger', __('app.err_access_denied'));
            }
        }else{
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message( 'danger', __( 'app.err_not_found' ) );
        }

        return $this->result();
    }


    /**
     * @param Request $request
     * @return array|false|string
     */
    public function validateForm( Request $request ) {

        $step        = $request->step;
        $customRules = [];
        switch ( $step ) {
            case "1":
                $customRules = $this->customRulesStepPartner;
                break;

            case "2":

                $customRules = $this->customRulesStep2;
                break;
        }

        //dd($request->all());
        $validator = $this->validator( $request->all(), $this->validatorRules, $customRules, $this->messages );

        if ( $validator->fails() ) {
            // error
            $this->result['status']             = 'error';
            $this->result['response']['errors'] = $validator->errors();

        } else {

            // проверяем статус добавляемого пользвоателя
            // если блокирован - 8, то не пропускать его
            $user = Auth::user();
            $partner = Partner::find($user->id);

            /*if($buyer = Buyer::where('phone',correct_phone($request->phone))->where('status',User::KYC_STATUS_BLOCKED)->first()){
                $this->result['status']             = 'error';
                $this->result['response']['errors'] = 'you_blocked';
            }else{
                $this->result['status'] = 'success';
            }*/

            if($buyer = Buyer::where('phone',correct_phone($request->phone))->where('status',User::KYC_STATUS_BLOCKED)->first()){

                if(!$partner->company->vip){   // если вендор сам платит за клиента - vip - пропускаем
                    $this->result['status']             = 'error';
                    $this->result['response']['errors'] = 'you_blocked';
                }else{
                    // меняем статус клиенту
                    $buyer->status = 1;
                    $buyer->save();
                    $this->result['status'] = 'success';
                }
            }else{
                $this->result['status'] = 'success';
            }

        }

        return $this->result();
    }



    /**  создание / изменение пользователя из кабинета вендора  billing
     * @param Request $request
     * @return array|false|string
     */
    public function modify( Request $request ) {
        Log::info('partner modify buyer');

        $user  = Auth::user();
        $buyer = Model::find( $request->buyer_id ); // Model == Buyer()

        if ( $buyer ) {
            if ( $user->can( 'add', $buyer ) ) {

                $validator = $this->validator( $request->all(), $this->customRulesStepNew, [], $this->messages ); // 14.04 - свои правила валидации ...RulesStep2

                $validatorForIdCard = $this->validator( $request->all(), $this->customRulesForIdCard, [], $this->messages ); // валидация для ID карты

                if ( $validator->fails() && $validatorForIdCard->fails()) { // пропускаем если есть фото паспорта или ID карты
                    // error
                    $this->result['status']             = 'error';
                    $this->result['response']['errors'] = $validator->errors();

                } else {

                    // данные вводятся автоматичсеки из OCR и хранятся в таблице.поле OCR.response
                    // !данные вручную не изменяются!
                    // данные ФИО, адреса, будут извлекаться из OCR и KATM и сохраняться в модель
                    // если данные для изменения есть в запросе


                    $buyer->name       = $request->name;
                    $buyer->surname    = $request->surname;
                    $buyer->patronymic = $request->patronymic;
                    $buyer->status     = 12;
                    $buyer->birth_date = date('Y-m-d',strtotime($request->birthday));
                    $buyer->save();

                    $buyerPersonals          = $buyer->personals ?? new BuyerPersonal();
                    $buyerPersonals->user_id = $buyer->id;

                    $buyerPersonals->birthday      = EncryptHelper::encryptData( $request->birthday );
                    $buyerPersonals->passport_type = $request->passport_type ?? null;
                    $buyerPersonals->home_phone    = EncryptHelper::encryptData( $request->home_phone );
                    $buyerPersonals->pinfl         = EncryptHelper::encryptData( $request->pinfl );
                    $buyerPersonals->pinfl_hash    = md5( $request->pinfl );

                    $buyerPersonals->work_company  = EncryptHelper::encryptData( $request->work_company );
                    $buyerPersonals->work_phone    = EncryptHelper::encryptData( $request->work_phone );

                    $buyerPersonals->save();

                    $buyerAddress = $buyer->addressResidential ?? new BuyerAddress();

                    $buyerAddress->user_id  = $buyer->id;
                    $buyerAddress->type     = 'residential';
                    //$buyerAddress->postcode = $request->address_postcode;
                    //$buyerAddress->country  = $request->address_country;
                    $buyerAddress->region = $request->address_region;
                    $buyerAddress->area = $request->address_area;
                    $buyerAddress->city = $request->address_city;
                    $buyerAddress->address = $request->address;

                    $buyerAddress->save();

                    //Save files
                    $filesToDelete = ( $request->files_to_delete != '' ) ? explode( ',', $request->files_to_delete ) : [];

                    if ( count( $request->file() ) > 0 ) {
                        $params = [
                            'files'      => $request->file(),
                            'element_id' => $buyerPersonals->id,
                            'user_id' => $buyerPersonals->user_id,
                            'model'      => 'buyer-personal'
                        ];

                        FileHelper::upload( $params, $filesToDelete, true );

                        // OCR

                    }

                    $this->result['status'] = 'success';
                    $this->result['data']   = $buyer;
                    $this->message( 'success', __( 'panel/buyer.txt_data_saved' ) );

                }
            } else {
                //Error: access denied
                $this->result['status']           = 'error';
                $this->result['response']['code'] = 403;
                $this->message( 'danger', __( 'app.err_access_denied' ) );
            }

        } else {
            $this->result['status']           = 'error';
            $this->result['response']['code'] = 404;
            $this->message( 'danger', __( 'auth.error_user_not_found' ) );
        }

        return $this->result();

    }

    // отправка otp UZcard или смс для HUMO
    public function sendOtpCode(Request $request){

        Log::info('send-otp');
        Log::info($request->all());

        if( CardHelper::checkTypeCard($request->card)=='UZCARD'){
            // отправка смс отп с UZCARD
            /*$uzcard = new UZCardController();
            $result = $uzcard->sendSmsCodeUz($request);*/
            // отправка смс с сайта
            $hash = $this->sendSmsCode($request, true,null,6);

            $result['type'] = 1;
            $result['hashed'] = $hash;

        }else{
            // отправка смс с сайта
            $hash = $this->sendSmsCode($request,true,null,6);
            $result['type'] = 2;
            $result['hashed'] = $hash;

        }

        return $result;

    }

    // проверка otp от клиента для UZcard или смс для HUMO
    public function checkOtpCode(Request $request){
        Log::info('check-otp');
        Log::info($request->all());

        // для тестовой проверки смс кода от uzcard
        //return ['status' => 'success'];

        switch($request->type){
            case 1: // uzcard
                /*$uzcard = new UZCardController();
                $result = $uzcard->checkSmsCodeUz($request);*/
                $result = $this->checkSmsCode($request);
                break;
            case 2: // humo
                $result = $this->checkSmsCode($request);

                break;
        }


        return $result;

    }

    /**
     *  13.08 - проверка статуса клиента партнером по номеру телефона
     *
     * @return array|bool|false|string
     */
    public function getStatus(Request $request) {

        $user = Auth::user();

        if(!$request->has('phone')){
            return $this->result();
        }

        if( $buyer = Buyer::where('phone',$request->phone)->first() ){
            if ($user->can('detail', $buyer)) {
                $this->result['status'] = 'success';
                $this->result['data'] = ['status' => $buyer->status];
            }else {
                //Error: access denied
                $this->result['status'] = 'error';
                $this->result['response']['code'] = 403;
                $this->message('danger', __('app.err_access_denied'));
            }
        }else{
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message( 'danger', __( 'app.err_not_found' ) );
        }

        return $this->result();
    }

    /**  вип переключалка при регестрации вендором - использовать только до добавления карты
     * @param Request $request
     * @return array|false|string
     */
    public function checkVip( Request $request ) {

        $user = Auth::user();
        $partner = Partner::find($user->id);
        $vip = $partner->company->vip ? 1 : 0;

        //dd($request);
        if($buyer = Buyer::wherePhone($request->phone)->first()){

            if($vip){
                if(!$buyer->vip){
                    $buyer->vip = 1;
                    // поменять лимит на 7000000
                    if($buyer->status >= 5 ) {
                        $limit = Config::get('test.vip_limit');  // 7000000
                        $buyer->settings->limit = $limit;
                        $buyer->settings->balance = $limit;
                        $buyer->settings->save();
                    }
                }
            }else{
                if($buyer->vip){
                    $buyer->vip = 0;
                    // если вип стал не вип, и его карта проигнорировала скоринг
                    if($buyer->status >= 5 ){
                        // проскорить карту заново, выдать лимит по скорингу
                        $request->merge([ 'buyer_id' => $buyer->id ]);
                        $scoring_result = UniversalController::cardRescoring($request);
                    }
                }
            }
            if($user->id != $buyer->created_by) $buyer->created_by = $user->id;
            $buyer->save();

            $this->result['status'] = 'success';
            $this->result['data'] = ['vip' => $buyer->vip];

        }else{
            $this->result['status'] = 'success';

            /*$this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message( 'danger',__('panel/buyer.err_buyer_not_found'));*/
        }

        return $this->result();
    }

    public function sendSmsCodeAuth(Request $request) {
        //Сделано для вендоров так как дефолтная отправка исправлена на 6-значный, а вендоров интеграция сделана на 4 значный //13.03.2023
        return $this->sendSmsCode($request, true,null,4);
    }


}
