<?php

namespace App\Http\Controllers\Core;


use App\Helpers\FileHelper;
use App\Helpers\ImageHelper;
use App\Helpers\SmsHelper;
use App\Http\Controllers\Core\Auth\RegisterController;
use App\Http\Requests\Core\PartnerController\BlockRequest;
use App\Models\CatalogPartners;
use App\Models\Company;
use App\Models\Company as Model;
use App\Models\GeneralCompany;
use App\Models\PartnerSetting;
use App\Models\Role;
use App\Models\User;
use App\Rules\AffiliateInn;
use App\Services\API\Core\BlockHistoryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PartnerController extends CoreController {

    private $validatorRules = [
        'name'                    => [ 'required', 'string', 'max:255' ],
        'surname'                 => [ 'required', 'string', 'max:255' ],
        //'patronymic'              => [ 'required', 'string' ],
       // 'discount_direct'                => [ 'required', 'numeric' ],
        'nds'                     => [ 'required', 'boolean' ],
        'company_nds_number'       => [ 'nullable', 'string', 'max:12',],  // Регистрационный номер НДС
        'company_oked'             => [ 'nullable', 'string', 'max:5' ],   // ОКЭД
        'company_mfo'              => [ 'string', 'max:5' ],  // мфо

        'company_uniq_num'         => [ 'required','string'],  // уникальный номер спецификации

        'company_inn'               => [ 'required', 'numeric', 'digits_between:9,14' ],  // инн

        'markup_1'                => [ 'required', 'numeric' ],
        'markup_3'                => [ 'required', 'numeric' ],
        'markup_6'                => [ 'required', 'numeric' ],
        'markup_9'                => [ 'required', 'numeric' ],
        'markup_12'                => [ 'required', 'numeric' ],
        'markup_24'                => [ 'required', 'numeric' ],
        'limit_for_24'             => [ 'required', 'numeric' ],

       /* 'discount_3'                => [ 'required', 'numeric' ],
        'discount_6'                => [ 'required', 'numeric' ],
        'discount_9'                => [ 'required', 'numeric' ],
        'discount_12'                => [ 'required', 'numeric' ],*/

        'company_name'            => [ 'required', 'string' ],
        'company_address'         => [ 'required', 'string' ],
        'company_legal_address'   => [ 'required', 'string' ],
        'company_bank_name'       => [ 'required', 'string' ],
        'company_payment_account' => [ 'required', 'string', 'max:20' ],  // расчетный счет
    ];

    /**
     * PartnerController constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->model = app( Model::class );
        $this->config = Config::get('test.preview');
        $this->loadWith = ['logo','categories','generalCompany','settings','user','region','tariffs'];
    }





    /**
     * @param $id
     * @param array $with
     * @return Builder|\Illuminate\Database\Eloquent\Model|object
     */
    protected function single($id, $with = []) {
        $single = parent::single($id, array_merge($this->loadWith, []));
        $single->status_list = Config::get( 'test.user_status' );
        return $single;
    }



    /**
     * @param $id
     * @return array|false|string
     */
    public function detail( int $id ) {

        $user    = Auth::user();
        $partner = $this->single( $id );

        if ( !$partner ) {
            $this->result['status']           = 'error';
            $this->result['response']['code'] = 404;
            $this->message( 'danger', __( 'app.err_not_found' ) );
            return $this->result();
        }

        if ( $user && $user->can( 'detail', $partner ) || $partner->status === 1 ) {
            $this->result['status'] = 'success';
            $this->result['data']   = $partner;
        } else {
            //Error: access denied
            $this->result['status']           = 'error';
            $this->result['response']['code'] = 403;
            $this->message( 'danger', __( 'app.err_access_denied' ) );
        }

        return $this->result();
    }


    /**
     * @param Request $request
     * @return array|false|string
     */
    public function confirm( Request $request ) {
        $partner = Model::find( $request->partner_id );
        $user    = Auth::user();

        if ( $partner ) {

            if ( /*$user->hasPermissions('modify-partner')*/  $user->can( 'modify', $partner ) ) {
                $partner->status = 1;
                $partner->save();
                BlockHistoryService::unBlock($request);
                $passwd = Str::random(12);
                $msg = Str::replaceArray('?', [$partner->id, $passwd], __('panel/partner.txt_sms_confirm'));
                SmsHelper::sendSms($partner->user->phone, $msg);
                $partner->user->password = Hash::make($passwd);
                $partner->user->save();

                RegisterController::generateApiToken($partner->user);

                $this->result['status'] = 'success';
                $this->message( 'success', __( 'panel/partner.txt_confirm' ) );
            } else {
                //Error: access denied
                $this->result['status']           = 'error';
                $this->result['response']['code'] = 403;
                $this->message( 'danger', __( 'app.err_access_denied' ) );

            }

        }

        return $this->result();
    }


    // перевыпустить пароль = на введенный админом номер телефона
    /**
     * @param Request $request
     * @return array|false|string
     */
    public function resend( Request $request ) {

        $partner = Model::find( $request->partner_id );

        //if($request->phone) $phone = correct_phone($request->phone);

        if ( $partner ) {
            $passwd = Str::random(12);
            $msg = Str::replaceArray('?', [$partner->id, $passwd], __('panel/partner.txt_sms_confirm'));
            SmsHelper::sendSms($partner->user->phone, $msg);  // input пользователя
            //SmsHelper::sendSms($phone, $msg);  // тел в бд продавца
            $partner->user->password = Hash::make($passwd);
            $partner->user->save();

            RegisterController::generateApiToken($partner->user);

            $data = [
                'partner' => $partner,
                'login' => $partner->id,
                'password' => $passwd,
            ];

            $pdf = \PDF::loadView('panel.partner.parts.credentials_pdf', $data);

            $this->result['status'] = 'success';
            $this->result['data']['pdf'] = base64_encode($pdf->output());
            $this->message( 'success', __( 'panel/partner.txt_resend' ) );

        } else {
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message( 'danger', __( 'auth.error_company_is_empty' ) );
        }

        return $this->result();
    }


    /**
     * @param Request $request
     * @return array|false|string
     */
    public function block(BlockRequest $request)
    {
        $partner = Model::find($request->partner_id);
        $partner->block_reason = $request->block_reason;
        $partner->status = 0;
        $partner->save();
        BlockHistoryService::add($request);
        RegisterController::generateApiToken($partner->user);
        $this->result['status'] = 'success';
        $this->message( 'success', __( 'panel/partner.txt_block' ) );

        return $this->result();
    }

    /**
     * @param Request $request
     * @return array|false|string
     */
    public function add(Request $request)
    {
        $user = Auth::user();

        if( $user->can('add', Model::class))
        {
            $moreValidatorRules = [
                'phone'             => [ 'required', 'string', 'max:15', 'regex:/^\+?[0-9]+$/' ],
                'company_parent_id' => ['exists:companies,id', 'nullable'],
                'logo'              => ['image', 'mimes:jpg,png,bmp', 'dimensions:max_width=1024,max_height=1024'],
                'reverse_calc'      => ['nullable'],
                'is_allowed_online_signature' => ['required'],
                'is_scoring_enabled'      => [ 'nullable', 'boolean' ],
                'is_mini_scoring_enabled' => [ 'nullable', 'boolean' ],
            ];

            $messages = [
                'logo.dimensions' => __( 'panel/partner.logo_size_should_not_exceed_1024_pixels' ),
                'company_parent_id.exists' => __( 'panel/partner.company_parent_id_not_found_or_incorrect' ),
                'company_uniq_num.required' => __( 'panel/partner.company_uniq_num_required' ),
                'markup_1.required' => __( 'panel/partner.markup_required' ),
                'markup_3.required' => __( 'panel/partner.markup_required' ),
                'markup_6.required' => __( 'panel/partner.markup_required' ),
                'markup_9.required' => __( 'panel/partner.markup_required' ),
                'markup_12.required' => __( 'panel/partner.markup_required' ),
                'markup_24.required' => __( 'panel/partner.markup_required' ),
            ];


            $validator = $this->validator($request->all(), $this->validatorRules, $moreValidatorRules, $messages);

            if ($validator->fails() )
            {
                //Error: validation error
                $this->result['status'] = 'error';
                $this->result['response']['errors'] = $validator->errors();
            }
            else
            {
                //Create company
                $company = new Company();
                $company->parent_id          = ($request->company_parent_id ?? null);
                $company->name               = $request->company_name;
                $company->inn                = $request->company_inn;
                $company->region_id          = $request->region_id;
                $company->address            = ($request->company_address         ?? null);
                $company->legal_address      = ($request->company_legal_address   ?? null);
                $company->bank_name          = ($request->company_bank_name       ?? null);
                $company->payment_account    = ($request->company_payment_account ?? null);
                $company->status             = 1;
                $company->website            = $request->company_website;
                $company->phone              = $request->company_phone;
                $company->description        = $request->company_description;
                $company->short_description  = $request->company_short_description;
                $company->uniq_num           = $request->company_uniq_num; // 21.06 - добавлено для сохранения
                $company->date_pact          = $request->company_date_pact; // 22.06
                $company->lat                = $request->company_lat ?? null;
                $company->lon                = $request->company_lon ?? null;
                $company->nds_numder         = ($request->company_nds_number        ?? null);  // регистрационный ндс номер платещика
                $company->mfo                = $request->company_mfo                ?? null; // МФО
                $company->oked               = $request->company_oked               ?? null; // ОКЭД
                $company->seller_coefficient = $request->company_seller_coefficient ?? 0; // Коэффицент бонусной премии для продавцов магазина
                $company->uniq_num           = $request->company_uniq_num           ?? null; // уникальный номер спецификации
                $company->brand              = $request->company_brand              ?? null;
                $company->reverse_calc       = (bool) ($request->reverse_calc       ?? 0);   // обратная калькуляция товаров
                $company->manager_id         = $request->manager_id                 ?? null;
                $company->general_company_id = $request->general_company_id ?? 1;            // связываем нового партнера с нашей компанией
                $company->manager_phone      = $request->phone_manager;
                $company->is_allowed_online_signature = $request->is_allowed_online_signature;

                if($company->save() && isset($request->categories))
                {
                    foreach ($request->categories as $id => $category)
                    {
                        $catalogPartners = new CatalogPartners();
                        $catalogPartners->catalog_id = $id;
                        $catalogPartners->partner_id = $company->id;
                        $catalogPartners->save();
                    }
                }

                //Save files
                if (count($request->file()) > 0)
                {
                    $params = [
                        'files' => $request->file(),
                        'element_id' => $company->id,
                        'model' => 'company'
                    ];
                    FileHelper::upload($params, [], true);
                }

                //Creating partner settings

                $partnerSettings                        = new PartnerSetting();

                $partnerSettings->markup_1              = $request->markup_1;
                $partnerSettings->markup_3              = $request->markup_3;
                $partnerSettings->markup_6              = $request->markup_6;
                $partnerSettings->markup_9              = $request->markup_9;
                $partnerSettings->markup_12             = $request->markup_12;
                $partnerSettings->markup_24             = $request->markup_24;

                $partnerSettings->limit_3               = $request->limit_3;
                $partnerSettings->limit_6               = $request->limit_6;
                $partnerSettings->limit_9               = $request->limit_9;
                $partnerSettings->limit_12              = $request->limit_12;
                $partnerSettings->limit_for_24          = $request->limit_for_24;

                $partnerSettings->company_id            = $company->id;
                $partnerSettings->nds                   = $request->nds;
                $partnerSettings->discount_3            = $request->discount_3  ?? 0;
                $partnerSettings->discount_6            = $request->discount_6  ?? 0;
                $partnerSettings->discount_9            = $request->discount_9  ?? 0;
                $partnerSettings->discount_12           = $request->discount_12 ?? 0;
                $partnerSettings->discount_24           = $request->discount_24 ?? 0;
                $partnerSettings->discount_direct       = $request->discount_direct ?? 0;
                $partnerSettings->contract_confirm      = $request->contract_confirmation;
                $partnerSettings->plan_extended_confirm = $request->plan_extended_confirm;
                $partnerSettings->is_trustworthy        = $request->is_trustworthy;
                $partnerSettings->is_scoring_enabled      = (bool) $request->is_scoring_enabled;
                $partnerSettings->is_mini_scoring_enabled = (bool) $request->is_mini_scoring_enabled;

                $plans = Config::get('test.plans');
                foreach($plans as $plan=>$percent)
                    $partnerSettings['markup_'.$plan] = $request['markup_'.$plan]??$percent;

                $discounts = Config::get('test.partner_discounts');

                foreach($discounts as $discount=>$percent)
                    $partnerSettings['discount_'.$discount] = 0; //??? //$request['discount_'.$discount]??$percent;

                $partnerSettings->save();

                $parent_company_dummy = GeneralCompany::find($request->general_company_id);
                $is_mfo = $parent_company_dummy->is_mfo;
                if ( $is_mfo === 1) {
                    $limits_array = [];
                    if (!empty($request->tariffs)) {
                        $limits_array = array_keys($request->tariffs);
                    }
                    $company->tariffs()->sync($limits_array);
                }

                // Сохранение номера договора с партнером в таблице company_uniq_nums
                $company->currentUniqNum()->updateOrCreate(['general_company_id' => $company->general_company_id], [
                    'uniq_num' => $company->uniq_num
                ]);

                //Create user
                $user = new User();
                $user->status       = 1;
                $user->name         = $request->name;
                $user->surname      = $request->surname;
                $user->patronymic   = $request->patronymic;
                $user->phone        = partner_phone_short($request->phone);
                $user->company_id   = $company->id;

                if(isset($request->company_parent_id)) {
                    $user->role_id = Role::PARTNER_ROLE_ID;
                    $parent_company = Company::where('id', $request->company_parent_id)->first();
                    $parent_company->user->role_id = Role::SALES_MANAGER_ROLE_ID;
                    $parent_company->user->save();
                }
                else {
                    $user->role_id = Role::VENDOR_ROLE_ID;
                }

                $user->doc_path     = 1; //  файлы на новом сервере
                $user->save();

                RegisterController::generateApiToken($user);

                //User role
                $user->attachRole('partner');

                //Success: news item created
                $this->result['status'] = 'success';
                $this->message( 'success', __( 'billing/affiliate.txt_created' ) );
            }
        }
        else
        {
            //Error: access denied
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 403;
            $this->message( 'danger', __( 'app.err_access_denied' ) );
        }

        return $this->result();
    }


    /**
     * @param Request $request
     *
     * @return array|false|string
     */
    public function modify( Request $request )
    {
        $user    = Auth::user();
        $partner = Model::find( $request->partner_id );

        if ( $partner ) {
            if ( $user->hasRole('admin') || $user->hasRole('editor') ) {

                $moreValidatorRules = [
                    'company_inn' => ['required', 'string', 'max:255', new AffiliateInn()],
                    //'phone'       => [ 'required', 'string', 'max:15', 'regex:/^\+?[0-9]+$/', 'unique:users,phone,' . $request->partner_id],
                    'reverse_calc' => ['nullable'],
                    'is_allowed_online_signature' => ['required'],
                    'is_scoring_enabled'      => [ 'nullable', 'boolean' ],
                    'is_mini_scoring_enabled' => [ 'nullable', 'boolean' ],
                ];

                $validator = $this->validator( $request->all(), $this->validatorRules, $moreValidatorRules );

                if ( $validator->fails() ) {
                    // error
                    $this->result['status']             = 'error';
                    $this->result['response']['errors'] = $validator->errors();

                } else {
                    //User
                    $partner->user->name       = $request->name;
                    $partner->user->surname    = $request->surname;
                    $partner->user->patronymic = $request->patronymic;
                    $partner->user->phone      = $request->phone;
                    $partner->user->save();

                    //Settings
                    $partner->settings->markup_1 = $request->markup_1;
                    $partner->settings->markup_3 = $request->markup_3;
                    $partner->settings->markup_6 = $request->markup_6;
                    $partner->settings->markup_9 = $request->markup_9;
                    $partner->settings->markup_12 = $request->markup_12;
                    $partner->settings->markup_24 = $request->markup_24;

                    $partner->settings->limit_3 = $request->limit_3;
                    $partner->settings->limit_6 = $request->limit_6;
                    $partner->settings->limit_9 = $request->limit_9;
                    $partner->settings->limit_12 = $request->limit_12;
                    $partner->settings->limit_for_24 = $request->limit_for_24;

                    $partner->settings->nds                   = $request->nds;
                    $partner->settings->discount_3            = $request->discount_3??0;
                    $partner->settings->discount_6            = $request->discount_6??0;
                    $partner->settings->discount_9            = $request->discount_9??0;
                    $partner->settings->discount_12           = $request->discount_12??0;
                    $partner->settings->discount_24           = $request->discount_24??0;
                    $partner->settings->discount_direct       = $request->discount_direct??0;
                    $partner->settings->contract_confirm      = $request->contract_confirmation;
                    $partner->settings->plan_extended_confirm = $request->plan_extended_confirm;
                    $partner->settings->is_trustworthy        = $request->is_trustworthy;
                    $partner->settings->is_scoring_enabled      = (bool) $request->is_scoring_enabled;
                    $partner->settings->is_mini_scoring_enabled = (bool) $request->is_mini_scoring_enabled;
                    $partner->settings->save();

                    $parent_company_dummy = GeneralCompany::find($request->general_company_id);
                    $is_mfo = $parent_company_dummy->is_mfo;
                    if ( $is_mfo === 1) {
                        $limits_array = [];
                        if (!empty($request->tariffs)) {
                            $limits_array = array_keys($request->tariffs);
                        }
                        $partner->tariffs()->sync($limits_array);
                    }


                    //Company
                    $partner->brand             = $request->company_brand;
                    $partner->reverse_calc      = (bool) ($request->reverse_calc ?? 0); // обратная калькуляция товаров
                    $partner->manager_id        = $request->manager_id;
                    $partner->name              = $request->company_name;
                    $partner->description       = $request->company_description;
                    $partner->short_description = $request->company_short_description;
                    $partner->inn               = $request->company_inn;
                    $partner->address           = $request->company_address;
                    $partner->legal_address     = $request->company_legal_address;
                    $partner->bank_name         = $request->company_bank_name;
                    $partner->payment_account   = $request->company_payment_account;
                    $partner->phone             = $request->company_phone;
                    $partner->manager_phone     = $request->phone_manager;
                    $partner->website           = $request->company_website;
                    $partner->uniq_num          = $request->company_uniq_num; // 21.06 - добавлено для сохранения
                    $partner->date_pact         = $request->company_date_pact; // 22.06
                    $partner->lat               = $request->company_lat; // 13.08
                    $partner->lon               = $request->company_lon; // 13.08
                    $partner->nds_numder        = $request->company_nds_number;  // регистрационный ндс номер платещика
                    $partner->mfo               = $request->company_mfo; // МФО
                    $partner->oked              = $request->company_oked; // ОКЭД
                    $partner->seller_coefficient = $request->company_seller_coefficient??0; // Коэффицент бонусной премии для продавцов магазина
                    $partner->region_id          = $request->region_id??0;
                    $partner->is_allowed_online_signature = $request->is_allowed_online_signature;
                    $partner->general_company_id = $request->general_company_id ?? $partner->general_company_id; // связка нового партнера с нашей компанией

                    $partner->save();

                    //Delete files
                    $filesToDelete = ($request->files_to_delete != '') ? explode(',', $request->files_to_delete) : [];
                    if(count($filesToDelete) > 0) FileHelper::delete($filesToDelete);

                    //Save files
                    if (count($request->file()) > 0) {
                        $params = [
                            'files' => $request->file(),
                            'element_id' => $partner->id,
                            'model' => 'company'
                        ];
                        FileHelper::upload($params, [], true);

                        if($partner->logo){
                            //Making preview
                            $previewName = 'preview_'.$partner->logo->name;
                            $storagePath = Storage::disk('local')->getAdapter()->getPathPrefix().'public/';
                            $previewPath = $storagePath.str_replace($partner->logo->name, $previewName, $partner->logo->path);
                            $preview = new ImageHelper($storagePath.$partner->logo->path);
                            $preview->resize($this->config['width'], $this->config['height']);
                            $preview->save($previewPath);
                        }
                    }

                    // добавляем категории к партнеру
                    // удаляем старые, добавляем новые
                    if($request->categories){
                        CatalogPartners::where('partner_id',$request->partner_id)->delete();
                        foreach ($request->categories as  $id=>$category){
                            $cataloPartners = new CatalogPartners();
                            $cataloPartners->catalog_id = $id;
                            $cataloPartners->partner_id = $request->partner_id;
                            $cataloPartners->save();
                        }
                       // $partner->categories()->sync( $request->categories ); // test
                    }

                    // Сохранение номера договора с партнером в таблице company_uniq_nums
                    $partner->currentUniqNum()->updateOrCreate(['general_company_id' => $partner->general_company_id], [
                        'uniq_num' => $partner->uniq_num
                    ]);

                    $this->result['status'] = 'success';
                    $this->result['data']   = $partner;
                    $this->message( 'success', __( 'panel/sallers.txt_updated' ) );

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

	// 03.09.2021
   /* public function list($params = []){

        if(preg_match('/(api\/v1)/',$_SERVER['REQUEST_URI'])) {
            return parent::list(['id' => ['215242', '215038', '215033', '215034', '215035']]);
        }else{
            return parent::list($params);
        }
    }*/
    public function showReasons(){
        return BlockHistoryService::showReasons();
    }

    public function showBlockHistory(Request $request){
        return BlockHistoryService::show($request->partner_id);
    }
}
