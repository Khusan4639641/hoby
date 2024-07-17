<?php

namespace App\Http\Controllers\Core;

use App\Helpers\CardHelper;
use App\Helpers\EncryptHelper;
use App\Helpers\FileHelper;
use App\Helpers\ImageHelper;
use App\Helpers\KatmHelper;
use App\Helpers\NotificationHelper;
use App\Helpers\OCRHelper;
use App\helpers\UniversalHelper;
use App\Http\Requests\Core\BuyerProfileController\SaveAddressRequest;
use App\Models\Buyer;
use App\Models\Buyer as Model;
use App\Models\BuyerAddress;
use App\Models\BuyerPersonal;
use App\Models\Card;
use App\Models\CardPassportScoring;
use App\Models\File;
use App\Models\KycHistory;
use App\Models\Ocr;
use App\Models\User;
use App\Rules\Uppercase;
use App\Services\API\V3\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BuyerProfileController extends CoreController {

    private $basedRules = [];
    private $customRulesStep1;
    private $customRulesStep2;

    private $messages;

    private $encryptedFields = [
        'birthday',
        'city_birth',
        'work_company',
        'work_phone',
        'passport_number',
        'passport_date_issue',
        'passport_issued_by',
        'home_phone',
        'pinfl',
        'inn',
        'social_vk',
        'social_facebook',
        'social_linkedin',
        'social_instagram',
    ];


    private $editableFields = [
        'birthday',
        'city_birth',
        'work_company',
        'work_phone',
        'home_phone',
        'social_vk',
        'social_facebook',
        'social_linkedin',
        'social_instagram',
    ];

    private $addressFields = ['postcode', 'country', 'region', 'area', 'city', 'address'];

    private $config;

    /**
     * NewsController constructor
     */
    public function __construct()
    {
        parent::__construct();

        //Model
        $this->model = app(Model::class);
        //Config
        $this->config = Config::get('test');

        $this->messages = [
            'regex' => __('validation.attributes.lat_only'),
        ];

        $this->basedRules = [
            'name' => ['required', 'string', 'max:20', 'regex:/^([A-Z]{1})([a-z` ]*)$/u', new Uppercase ],
            'surname' => ['required', 'string', 'max:45', 'regex:/^([A-Z]{1})([a-z` ]*)$/u', new Uppercase],
            'patronymic' => ['required', 'string', 'max:45', 'regex:/^([A-Z]{1})([a-z` ]*)$/u', new Uppercase],
            'birthday' => ['nullable', 'string', 'max:255'],
            'work_company' => ['nullable', 'string', 'max:255'],
            'work_phone' => ['required_without:home_phone', 'min:12', 'max:255'],
            'home_phone' => ['required_without:work_phone', 'min:12', 'max:255'],
        ];

        $this->verifiedProfileRules = [
            'birthday' => ['nullable', 'string', 'max:255'],
            'work_company' => ['nullable', 'string', 'max:255'],
            'work_phone' => ['required_without:home_phone', 'min:12', 'max:255'],
            'home_phone' => ['required_without:work_phone', 'min:12', 'max:255'],
        ];


        $this->customRulesStep1 = [

            'address_region' => ['required'],
            'address_area' => ['required'],
            'address_city' => ['sometimes'],
            'address' => ['required', 'string'],

            /*'passport_selfie'       => [ 'required', 'image' ],
            'passport_first_page'   => [ 'required', 'image' ],
            'passport_with_address' => [ 'required', 'image' ],*/
        ];

        $this->customRulesStep2 = [
            'card_number' => ['nullable', 'string', 'max:255'],
            'card_valid_date' => ['nullable', 'string', 'max:255'],
        ];
    }


    /**
     * @param $id
     * @param array $with
     * @return Collection
     */
    protected function single($id, $with = [])
    {
        $single = parent::single($id, $with);

        if ($single) {

            //Prepare personals
            if ($single->personals)
                foreach ($single->personals->getAttributes() as $key => $value)
                    $single->personals[$key] = in_array($key, $this->encryptedFields) ? EncryptHelper::decryptData($value) : $value;

            //Prepare avatar
            if ($single->avatar)
                $single->avatar->path = Storage::url($single->avatar->path);

        }

        return $single;
    }


    /**
     * Detail news
     *
     * @param $id
     * @return array|bool|false|string
     */
    public function detail(int $id)
    {
        $user = Auth::user();
        $buyer = $this->single($id, ['avatar', 'personals', 'addresses']);

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
     * Modify buyers profile
     *
     * @param Request $request
     * @return array|bool|false|string
     */
    public function modify(Request $request)
    {

        Log::info('BuyerProfile:modify');
        Log::info($request);

        $user = Auth::user();
        $buyer = Model::find($user->id);

        if ($buyer) {
            if ($user->can('modify', $buyer)) {

                // $fields = $request->all();

                $validator = $this->validator($request->all(), $this->verifiedProfileRules, [], $this->messages);

                if ($validator->fails()) {
                    // error
                    $this->result['status'] = 'error';
                    $this->result['response']['errors'] = $validator->errors();

                } else {

                    //Update FIO if status != 4
                    /*
                     *  данные пользователя берутся из OCR
                     *
                     *  if ($buyer->status != 4) {
                         if (isset($fields['surname']))
                             $buyer->surname = $fields['surname'];

                         if (isset($fields['name']))
                             $buyer->name = $fields['name'];

                         if (isset($fields['patronymic']))
                             $buyer->patronymic = $fields['patronymic'];

                         $buyer->save();
                     }

                     //Personal data update
                     foreach ($fields as $field => $value) {
                         if (in_array($field, $this->editableFields)) {
                             $buyer->personals[$field] = EncryptHelper::encryptData($value);
                         }
                     }
                     $buyer->personals->save(); */

                    //Process files
                    $filesToDelete = ($request->files_to_delete != '') ? explode(',', $request->files_to_delete) : [];

                    if ( count($request->file()) > 0) {
                        //$i = 0;
                        foreach ($request->file() as $file) {
                            $img = new ImageHelper($file);
                            $img->resize($this->config['preview']['width'], $this->config['preview']['height']);
                            $img->save($file->getRealPath(), 100, $file->extension());
                        }

                        //Save
                        $params = [
                            'files' => $request->file(),
                            'element_id' => $user->id,
                            'model' => 'user'
                        ];

                        FileHelper::upload($params, $filesToDelete, true);
                        // Log::info('file_save_' . $i);
                        // Log::info('BuyerProfile:load passport');
                        // Log::info('BuyerProfile:OCR passport');

                        $status_changed = true;

                        if( $file = File::where('element_id',$buyer->personals->id)->where('type','passport_first_page')->orderBy('id','DESC')->first() ){

                            // $data = ['user_id' => $buyer->personals->id, 'file_name' => $file->name];
                            // Log::info('OCR data:');
                            // Log::info($data);

                            // --------------------------------------------------------------------------------------
                            // для паспорта отправка на распознование
                            // получаем PINFL, FIO ...

                            // $ocrData = OCRHelper::send($data);
                            // Log::info($ocrData);
                            $ocrData['status'] = 'false';

                            if ($ocrData['status'] == 'success') { // } && (isset($ocrData['data'])  && is_array($ocrData['data'])) ) {

                                // результат распознования
                                $ocr = new Ocr();
                                $ocr->user_id = $buyer->id;
                                $ocr->response = json_encode($ocrData['data'], JSON_UNESCAPED_UNICODE);
                                $ocr->save();

                                // сохранить покупателю Buyer полученную информацию от OCR
                                Buyer::saveOcrData($buyer, $ocrData['data']);

                                // оср пройден устанавливаем статус
                                User::changeStatus($buyer,11); //2
                                Log::info('OCR add-passport sucess');

                                // Log::info('BuyerProfile: KATM get buyer address');

                                // по PINFL получаем адрес клиента
                                // $katmResult['address'] = KatmHelper::getClientAddress($buyer);

                                // добавляем полученные данные паспорта и ПИНФЛ для обращения к катм,
                                // либо у $buyer-user должны быть заполнены эти данные
                                //$request->merge(['passport' => $ocrData['passport'], 'pinfl' => $ocrData['pinfl']]);

                                // Log::info('BuyerProfile:KATM register');
                                // регистрируем заявку в катм
                                // $katmResult['info'] = KatmHelper::registerKatm($buyer, $request);
                            }elseif($buyer->status == User::STATUS_OCR_PASSPORT_STEP1 ){
                                // после первой попытки устанавливаем
                                /*   User::changeStatus($buyer,User::STATUS_OCR_PASSPORT_STEP2);
                                   Log::info('OCR first pass add-passport');
                               }elseif($buyer->status == User::STATUS_OCR_PASSPORT_STEP2 ){ */
                                // после второй попытки отправляем на KYC
                                User::changeStatus($buyer,11); //2
                                Log::info('OCR second pass add-passport ' . $buyer->id);

                            }elseif( $buyer->status == 1 || $buyer->status == 5 ){ // если карта добавлена или скоринг пройден
                                // первая попытка добавить фото
                                User::changeStatus($buyer,User::STATUS_OCR_PASSPORT_STEP1);
                                Log::info('OCR add-passport error ' . $buyer->id );


                            }else{
                                Log::info('OCR buyer->status = ' . $buyer->status . ' ' . $buyer->id );
                                $status_changed = false;
                            }

                            if($status_changed) {

                                // меняем kyc статус покупателя
                                $user->kyc_status = User::KYC_STATUS_MODIFY;
                                $user->kyc_id = null;
                                $user->save();

                                // добавляем в историю запись
                                KycHistory::insertHistory($user->id,User::KYC_STATUS_VERIFY,User::KYC_STATUS_VERIFY);

                            }

                        } // file

                        if($request->type==2 && isset($request->passport_selfie) ){ // Селфи
                            User::changeStatus($buyer,12);
                            Log::info('add selfie ' . $buyer->id);
                        }

                        if($request->type==2 && isset( $request->passport_with_address ) ){ // прописка в паспорте
                            User::changeStatus($buyer,11);
                            Log::info('add passport_with_address buyer_id: ' . $buyer->id );
                        }

                        //07.04  сравнение имени карты и паспорта из OCR

                        //$userFullName = Str::uppercase( $user->name . ' ' . $user->surname );

                        //$cardInfo = Card::where('user_id',$user->id)->orderBy('id','DESC')->take(1);

                        //$cardName = EncryptHelper::encryptData($cardInfo->card_name);

                        // проверка имени полученного для карты с именем введенным пользователем
                        // допуск от 95%
                        // имя вводимое пользвоателем может быть на кирилице!!
                        // имя по карте выдается в латинице!!!

                        /* $simplify = UniversalHelper::strDifferent(Str::uppercase($cardName), $userFullName);

                        if( ! $cardPassportScoring = CardPassportScoring::where('user_id',$user->id)->where('card_id',$cardInfo->id)->one() ){
                            $cardPassportScoring = new CardPassportScoring();
                            $cardPassportScoring->user_id = $user->id;
                            $cardPassportScoring->card_id = $cardInfo->id;
                        }

                        $cardPassportScoring->simplify = $simplify;
                        $cardPassportScoring->save(); */

                        // автосписание
                        // $a = UniversalHelper::getPayment($request->card_number,$request->card_valid_date);

                    }

                    $this->result['status'] = 'success';
                    $this->result['data']['buyer_id'] = $buyer->id;
                    $this->message('success', __('cabinet/profile.txt_updated'));

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

    /**
     * Modify buyers address
     *
     * @param Request $request
     * @return array|bool|false|string
     */
    public function modifyAddress(Request $request)
    {
        Log::info('BuyerAddress:modify');
        Log::info($request);

        $user = Auth::user();

        $buyer = Model::find($user->id);  // use App/Models/Buyer as Model

        if ($buyer) {
            if ($user->can('modify', $buyer)) {

                if (!$request->has('buyer_address') || $request->buyer_address === null) {
                    // error
                    $this->result['status'] = 'error';
                    $this->result['response']['errors'] = __('panel/buyer.address_residential_empty');
                }
                else {
                    if($buyerAddress = BuyerAddress::where('user_id', $buyer->id)->first()) {
                        $buyerAddress->address = $request->buyer_address;
                        $buyerAddress->save();
                    } else {
                        $this->result['status'] = 'error';
                        $this->result['response']['errors'] = __('api.buyer_not_found');
                    }
                    $this->result['status'] = 'success';
                    $this->result['data']['buyer_id'] = $buyer->id;
                    $this->message('success', __('cabinet/profile.txt_updated'));

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

    public function saveAddress(SaveAddressRequest $request)
    {
        BuyerAddress::updateOrCreate
        ([
            'user_id' => $request->buyer_id,
            'type' => BuyerAddress::TYPE_REGISTRATION,
        ],
            $request->validated(),
        );

        BaseService::handleResponse([__('panel/contract.successfully_saved')]);
    }

    public function saveWorkplaceAddress(Request $request)
    {
        Log::info('BuyerWorkplaceAddress:save');
        Log::info($request);

        $user = Auth::user();

        if ($request->has('api_token') && ($user->api_token === $request->api_token)) {

            if ($request->buyer_id && $request->director_name && $request->address) {

                $buyer = Model::find($request->buyer_id);

                if ($buyer) {

                    if ($user->can('modify', $buyer)) {

                        $workplace_address = BuyerAddress::where([
                            ['user_id', '=', $buyer->id],
                            ['type', '=', 'workplace'],
                        ])->first();

                        if ($workplace_address) {

                            $old_address = $workplace_address->address;
                            $old_director_name = $workplace_address->director_name;

                            $changes_made = $old_address !== $request->address || $old_director_name !== $request->director_name;

                            if ($changes_made) {

                                $workplace_address->address = $request->address;
                                $workplace_address->director_name = $request->director_name;

                                $workplace_address->save();

                                $this->result['status'] = 'success';
                                $this->result['data']['buyer_id'] = $buyer->id;
                                $this->message('success', __('panel/buyer.txt_data_saved'));

                            } else {

                                $this->result['status'] = 'error';
                                $this->result['response']['code'] = 404;
                                $this->message('danger', __('panel/buyer.err_no_changes_in_data'));
                                //$this->result['response']['errors'] = __('panel/buyer.err_no_changes_in_data');
                            }

                        } else {

                            $workplace_address = new BuyerAddress();

                            $workplace_address->user_id = $buyer->id;
                            $workplace_address->type = 'workplace';
                            $workplace_address->address = $request->address;
                            $workplace_address->director_name = $request->director_name;

                            $workplace_address->save();

                            $this->result['status'] = 'success';
                            $this->result['data']['buyer_id'] = $buyer->id;
                            $this->message('success', __('panel/buyer.txt_data_saved'));
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
                    $this->message('danger', __('panel/buyer.err_buyer_not_found'));
                }

            } else {

                $this->result['status'] = 'error';
                $this->result['response']['code'] = 404;
                $this->message('danger', __('panel/buyer.err_invalid_data'));
            }
        }

        return $this->result();
    }

    /** сохранение данных и файлов при добавлении пользователя
     * @param Request $request
     * @return array|false|string
     */
    public function modifyVerification(Request $request)
    {
        Log::info('BuyerProfile:modifyVerification - new');

        Log::info($request);

        $user = Auth::user();
        $buyer = Model::find($user->id);

        if ($buyer) {
            if ($user->can('modify', $buyer)) {

                switch ($request->step) {

                    case "1": // проверка карты
                        $validator = $this->validator($request->all(), $this->customRulesStep2);

                        if ($validator->fails()) {
                            // error
                            $this->result['status'] = 'error';
                            $this->result['response']['errors'] = $validator->errors();
                        } else {

                            $card = new CardController();
                            return $card->add( $request );
                        }

                        break;

                    case "2": // проверка паспорта
                        if($buyer->personals){
                            if(!$buyer->personals->passport_selfie)
                                $this->customRulesStep1['passport_selfie' ] = [ 'required', 'image' ];
                            if(!$buyer->personals->passport_first_page)
                                $this->customRulesStep1['passport_first_page' ] = [ 'required', 'image' ];
                            if(!$buyer->personals->passport_with_address)
                                 $this->customRulesStep1['passport_with_address' ] = [ 'required', 'image' ];
                        }else{
                            $this->customRulesStep1 = array_merge($this->customRulesStep1, [
                                'passport_selfie'       => [ 'required', 'image' ],
                                'passport_first_page'   => [ 'required', 'image' ],
                                'passport_with_address' => [ 'required', 'image' ]
                            ]);
                        }

                        // $validator = $this->validator($request->all(), $this->basedRules, $this->customRulesStep1, $this->messages);
                        $validator = $this->validator($request->all(), [], [], $this->messages);

                        if ($validator->fails()) {
                            // error
                            $this->result['status'] = 'error';
                            $this->result['response']['errors'] = $validator->errors();
                        } else {

                            // $buyer->name = $request->name;
                            // $buyer->surname = $request->surname;
                            // $buyer->patronymic = $request->patronymic;

                            $buyer->save();

                            $buyerPersonals = $buyer->personals ?? new BuyerPersonal();
                            $buyerPersonals->user_id = $buyer->id;
                            $buyerPersonals->passport_type = $buyerPersonals->passport_type ?? $request->passport_type;
                            /*
                             $buyerPersonals->birthday = EncryptHelper::encryptData($request->birthday);
                             $buyerPersonals->home_phone = EncryptHelper::encryptData($request->home_phone);
                             $buyerPersonals->work_company = EncryptHelper::encryptData($request->work_company);
                             $buyerPersonals->work_phone = EncryptHelper::encryptData($request->work_phone);
                            */

                            $buyerPersonals->save();

                            //Save files
                            $filesToDelete = ($request->files_to_delete != '') ? explode(',', $request->files_to_delete) : [];
                            if (count($request->file()) > 0) {

                                Log::info('buyer_id: ' . $buyer->id);
                                Log::info('save files' . __FILE__);
                               // $i=0;
                                foreach ($request->file() as $file) {
                                    $img = new ImageHelper($file);
                                    $img->resize($this->config['documents_size']['width'], $this->config['documents_size']['height']);
                                    $img->save($file->getRealPath(), 100, $file->extension());

                                }

                                $params = [
                                    'files' => $request->file(),
                                    'element_id' => $buyerPersonals->id,
                                    'model' => 'buyer-personal'
                                ];


                                FileHelper::upload($params, $filesToDelete, true);

                                //-----------

                                //Log::info('file_save_' . $i);
                                $file = File::where('element_id', $buyer->personals->id)->where(function($file) {
                                    $file->where('type', 'passport_first_page')->orWhere('type', 'id_first_page')->latest();
                                })->first();

                                if (isset($file)) {
                                    $data = ['user_id' => $buyer->personals->id, 'file_name' => $file->name];

                                    Log::info('OCR data new:');
                                    // Log::info($data);

                                    // --------------------------------------------------------------------------------------
                                    // для паспорта отправка на распознование
                                    // получаем PINFL, FIO ...
                                    $ocrData = OCRHelper::send($data);
                                    Log::info($ocrData);

                                    Log::info('OCR user->status: ' . $buyer->status);

                                    if ($ocrData['status'] == 'success' && (isset($ocrData['data'])  && is_array($ocrData['data'])) )
                                    {
                                        // результат распознования
                                        $ocr = new Ocr();
                                        $ocr->user_id = $buyer->id;
                                        $ocr->response = json_encode($ocrData, JSON_UNESCAPED_UNICODE);
                                        $ocr->save();

                                        // сохранить покупателю Buyer полученную информацию от OCR
                                        Buyer::saveOcrData($buyer, $ocrData['data']);

                                        /*
                                        Log::info('BuyerProfile: KATM get buyer address');

                                        // по PINFL получаем адрес клиента
                                        $katmResult['address'] = KatmHelper::getClientAddress($buyer);

                                        // добавляем полученные данные паспорта и ПИНФЛ для обращения к катм,
                                        // либо у $buyer-user должны быть заполнены эти данные
                                        $request->merge(['passport' => $ocrData['data']['number'], 'pinfl' => $ocrData['data']['personal_number']]);

                                        Log::info('BuyerProfile:KATM register');
                                        // регистрируем заявку в катм
                                        $katmResult['info'] = KatmHelper::registerKatm($buyer, $request);
                                        */

                                        //Log::info($katmResult);

                                        /*
                                        $buyerAddressResidential           = $buyer->addressResidential ?? new BuyerAddress();
                                        $buyerAddressResidential->user_id  = $buyer->id;
                                        $buyerAddressResidential->type     = 'residential';
                                        //$buyerAddressResidential->postcode = $request->address_residential_postcode;
                                        //$buyerAddressResidential->country  = $request->address_residential_country;
                                        $buyerAddressResidential->region   = $request->address_residential_region;
                                        $buyerAddressResidential->area   = $request->address_residential_area;
                                        $buyerAddressResidential->city     = $request->address_residential_city;
                                        $buyerAddressResidential->address  = $request->address_residential_address;
                                        $buyerAddressResidential->save();
                                        */

                                        // оср пройден устанавливаем статус
                                        //User::changeStatus($buyer,10);
                                        //Log::info('OCR add-passport sucess');

                                        /*}elseif($buyer->status == User::STATUS_OCR_PASSPORT_STEP1 ){
                                            // после первой попытки устанавливаем
                                            // User::changeStatus($buyer, User::STATUS_OCR_PASSPORT_STEP2);
                                                //Log::info('OCR first pass add-passport');
                                            //}elseif($buyer->status == User::STATUS_OCR_PASSPORT_STEP2 ){
                                            // после второй попытки отправляем на KYC
                                            User::changeStatus($buyer,10);
                                            Log::info('OCR second pass add-passport');

                                        }elseif( $buyer->status == 1 || $buyer->status == 5 ){ // если карта добавлена или скоринг пройден
                                            // первая попытка добавить фото
                                            User::changeStatus($buyer,User::STATUS_OCR_PASSPORT_STEP1);
                                            Log::info('OCR add-passport error');


                                        }elseif($ocrData['status'] == 'incorrect'){
                                            Log::info('OCR status = incorrect');
                                            User::changeStatus($buyer,User::STATUS_OCR_PASSPORT_STEP1);
                                        }else{
                                            Log::info('OCR status ');

                                        User::changeStatus($buyer,User::STATUS_OCR_PASSPORT_STEP1); */
                                    }

                                    User::changeStatus($buyer,11);
                                    Log::info('OCR add-passport sucess. set buyer status 10, buyer_id: ' . $buyer->id);

                                } // file

                                if( $request->type==2 && (isset( $request->passport_selfie ) || isset( $request->id_selfie )) ) { // Селфи
                                    User::changeStatus($buyer,12); // отправляем на добавление доверенного лица
                                    Log::info('add selfie buyer_id: ' . $buyer->id );
                                }

                                if( $request->type==2 && (isset( $request->passport_with_address ) || isset( $request->id_with_address )) ) { // прописка в паспорте
                                    User::changeStatus($buyer,10);
                                    Log::info('add passport_with_address buyer_id: ' . $buyer->id );
                                }

                                // меняем kyc статус покупателя
                                $buyer->kyc_status = User::KYC_STATUS_MODIFY;
                                $buyer->kyc_id = null;
                                $buyer->save();

                                // добавляем в историю запись
                                KycHistory::insertHistory($buyer->id,User::KYC_STATUS_MODIFY);

                            }

                            $this->result['status'] = 'success';
                            $this->result['data'] = $buyer;
                            $this->message('success', __('panel/buyer.txt_data_saved'));

                        }

                        break;

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


    /**  проверка по шагам из кабинета
     * @param Request $request
     * @return array|false|string
     */
    public function validateForm(Request $request)
    {

        $step = $request->step;
        $customRules = [];
        switch ($step) {
            case "2":
                // валидация полей пользователя при регистрации
                // поля ФИО, адрес и др не используются при регистрации
                $customRules = [] ;//   array_merge($this->basedRules, $this->customRulesStep1);
                break;
            case "1":

                $customRules = $this->customRulesStep2;
                break;
        }

        $validator = $this->validator($request->all(), $customRules, [], $this->messages);

        if ($validator->fails()) {
            // error
            $this->result['status'] = 'error';
            $this->result['response']['errors'] = $validator->errors();

        } else {
            $this->result['status'] = 'success';
        }

        return $this->result();
    }


    /** // отправка верификации пользвоателя
     * @param Request $request
     * @return array|false|string
     */
    public function sendVerification(Request $request)
    {
        $buyer = Model::find($request->buyer_id);
        $user = Auth::user();

        if ($buyer) {

            if ($user->can('modify', $buyer)) {
                Log::info('send verification change buyer status to 2');
                $buyer->status = 2;
                $buyer->save();

                $notifyData = [
                    'buyer' => $buyer
                ];
                NotificationHelper::buyerSendVerification($notifyData, app()->getLocale());

                $this->result['status'] = 'success';
            } else {
                //Error: access denied
                $this->result['status'] = 'error';
                $this->result['response']['code'] = 403;
                $this->message('danger', __('app.err_access_denied'));
            }
        }

        return $this->result();
    }


    public function refillAccountByCard(Request $request){
        $user = Auth::user();

        //Если сумма указана верно
        if($request->sum && is_numeric($request->sum )) {

            // Если передан идентифкатор карты
            if(isset($request->card_id)) {
                $request->merge([
                    'buyer_id' => $user->id,
                    'type' => 'user'
                ]);

                //Отправляем платеж на оплату
                $cardController = new CardController();
                $result = $cardController->payment($request);
                //Если платеж удачный
                if($result['status'] == 'success') {
                    //Корректируем лицевой счет клиента
                    $buyer = Model::find($user->id);
                    $buyer->settings->personal_account += $request->sum;
                    $buyer->settings->save();

                    /*// уведомить о пополнении сервер автосписания
                            $data = [
                                'user_id' => $buyer->id,
                                'amount' => $request->sum,
                            ];
                            App\Helpers\PaymentHelper::fillAccount($data);*/

                    $this->result['status'] = 'success';
                    $this->result['data']['balance'] = $buyer->settings->personal_account;
                    $this->message('success', __('cabinet/cabinet.msg_refill_success'));
                }else {
                    $this->result['status'] = 'error';
                    $this->result['response']['message'] = $result['response']['message'];
                }
            }else {
                $this->result['status'] = 'error';
                $this->message('danger', __('cabinet/cabinet.error_card'));
            }
        }else{
            $this->result['status'] = 'error';
            $this->message('danger', __('cabinet/cabinet.error_empty_sum'));
        }

        return $this->result();
    }


    // функции для скоринга клиента




}
