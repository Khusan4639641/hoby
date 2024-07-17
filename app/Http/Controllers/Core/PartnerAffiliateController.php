<?php

namespace App\Http\Controllers\Core;


use App\Helpers\FileHelper;
use App\Helpers\ImageHelper;
use App\Http\Controllers\Core\Auth\RegisterController;
use App\Models\Company;
use App\Models\Partner;
use App\Models\Company as Model;
use App\Models\PartnerSetting;
use App\Models\User;
use App\Rules\AffiliateInn;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class PartnerAffiliateController extends CoreController
{
    protected $config;

    private $validatorRules = [
        'name'                    => [ 'required', 'string', 'max:255' ],
        'surname'                 => [ 'required', 'string', 'max:255' ],
        'patronymic'              => [ 'required', 'string' ],
        'company_name'            => [ 'required', 'string', 'max:255' ],
        'company_address'         => [ 'required', 'string' ],
        'company_legal_address'   => [ 'required', 'string' ],
        'company_bank_name'       => [ 'required', 'string' ],
        'company_payment_account' => [ 'required', 'string', 'max:20' ],
    ];


    /**
     * PartnerAffiliateController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->model = app( Model::class );
        $this->config = Config::get('test.preview');
    }


    /**
     * @param array $params
     * @return array
     */
    public function filter( $params = [] ) {

        $partner = Partner::find(Auth::user()->id);
        $params['parent_id'] = $partner->company->id;

        return parent::filter( $params );
    }


    /**
     * @param Request $request
     * @return array|false|string
     */
    public function add(Request $request){
        $user = Auth::user();
        $partner = Partner::find(Auth::user()->id);

        if($user->can('add', Model::class)) {
            $moreValidatorRules = [
                'phone' => [ 'required', 'string', 'max:15', 'regex:/^\+?[0-9]+$/', 'unique:users' ],
                'company_inn' => [ 'required', 'string', new AffiliateInn()]
            ];

            $validator = $this->validator($request->all(), $this->validatorRules, $moreValidatorRules);

            if ($validator->fails() ) {
                //Error: validation error
                $this->result['status'] = 'error';
                $this->result['response']['errors'] = $validator->errors();
            }else{
                //Create company
                $company = new Company();
                $company->name              = $request->company_name;
                $company->inn               = $request->company_inn;
                $company->address           = ($request->company_address??null);
                $company->legal_address     = ($request->company_legal_address??null);
                $company->bank_name         = ($request->company_bank_name??null);
                $company->payment_account   = ($request->company_payment_account??null);
                $company->parent_id         = $partner->company->id;
                $company->status            = 0;
                $company->website             = $request->company_website;
                $company->phone               = $request->company_phone;
                $company->description         = $request->company_description;
                $company->short_description   = $request->company_short_description;
                $company->save();

                //Delete files
                $filesToDelete = ($request->files_to_delete != '') ? explode(',', $request->files_to_delete) : [];

                //Save files
                if (count($request->file()) > 0) {
                    $params = [
                        'files' => $request->file(),
                        'element_id' => $company->id,
                        'model' => 'company'
                    ];
                    FileHelper::upload($params, $filesToDelete, true);

                    if($company->logo){
                        //Making preview
                        $previewName = 'preview_'.$company->logo->name;
                        $storagePath = Storage::disk('local')->getAdapter()->getPathPrefix().'public/';
                        $previewPath = $storagePath.str_replace($company->logo->name, $previewName, $company->logo->path);
                        $preview = new ImageHelper($storagePath.$company->logo->path);
                        $preview->resize($this->config['width'], $this->config['height']);
                        $preview->save($previewPath);
                    }
                }

                //Creating partner settings
                $partnerSettings = new PartnerSetting();
                $partnerSettings->company_id    = $company->id;
                $partnerSettings->nds           = 1;

                $discounts = Config::get('test.partner_discounts');
                foreach($discounts as $discount=>$percent)
                    $partnerSettings['discount_'.$discount] = $percent;

                $plans = Config::get('test.plans');
                foreach($plans as $plan=>$percent)
                    $partnerSettings['markup_'.$plan] = $percent;
                $partnerSettings->save();

                //Create user
                $user = new User();
                $user->status       = 1;
                $user->name         = $request->name;
                $user->surname      = $request->surname;
                $user->patronymic   = $request->patronymic;
                $user->phone        = partner_phone_short($request->phone); // mb_substr($request->phone,3);
                $user->company_id   = $company->id;
                $user->doc_path = 1; //  файлы на новом сервере
                $user->save();

                RegisterController::generateApiToken($user);

                //User role
                $user->attachRole('partner');



                //Success: news item created
                $this->result['status'] = 'success';
                $this->message( 'success', __( 'billing/affiliate.txt_created' ) );
            }
        }else {
            //Error: access denied
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 403;
            $this->message( 'danger', __( 'app.err_access_denied' ) );
        }

        return $this->result();
    }


    /**
     * @param $id
     * @return array|false|string
     */
    public function detail( $id ) {
        $user    = Auth::user();
        $affiliate = $this->single( $id, ['user', 'logo', 'settings'] );

        if ( $affiliate ) {
            if ( $user->can( 'detail', $affiliate ) ) {
                $this->result['status'] = 'success';
                $this->result['data']   = $affiliate;
            } else {
                //Error: access denied
                $this->result['status']           = 'error';
                $this->result['response']['code'] = 403;
                $this->message( 'danger', __( 'app.err_access_denied' ) );
            }
        } else {
            $this->result['status']           = 'error';
            $this->result['response']['code'] = 404;
            $this->message( 'danger', __( 'app.err_not_found' ) );
        }

        return $this->result();
    }



    /**
     * @param Request $request
     *
     * @return array|false|string
     */
    public function modify( Request $request ) {

        $user    = Auth::user();
        $affiliate = Model::find( $request->id );

        if ( $affiliate ) {
            if ( $user->can( 'modify', $affiliate ) ) {
                $rules = $this->validatorRules;
                //$rules['company_inn'] = [ 'required', 'string', 'unique:companies,inn,' . $request->id ];
                $moreValidatorRules = [
                    'phone' => [ 'required', 'string', 'max:15', 'regex:/^\+?[0-9]+$/', 'unique:users,phone,'. $request->id],
                    'company_inn' => [ 'required', 'string', new AffiliateInn()]
                ];
                $validator = $this->validator( $request->all(), $rules, $moreValidatorRules );

                if ( $validator->fails() ) {
                    // error
                    $this->result['status']             = 'error';
                    $this->result['response']['errors'] = $validator->errors();

                } else {
                    //User
                    $affiliate->user->name       = $request->name;
                    $affiliate->user->surname    = $request->surname;
                    $affiliate->user->patronymic = $request->patronymic;
                    $affiliate->user->phone      = $request->phone;
                    $affiliate->user->status     = 0;
                    $affiliate->user->save();

                    //Company
                    $affiliate->status              = 0;
                    $affiliate->name                = $request->company_name;
                    $affiliate->inn                 = $request->company_inn;
                    $affiliate->address             = $request->company_address;
                    $affiliate->legal_address       = $request->company_legal_address;
                    $affiliate->bank_name           = $request->company_bank_name;
                    $affiliate->payment_account     = $request->company_payment_account;
                    $affiliate->website             = $request->company_website;
                    $affiliate->phone               = $request->company_phone;
                    $affiliate->description         = $request->company_description;
                    $affiliate->short_description   = $request->company_short_description;

                    $affiliate->save();

                    //Delete files
                    $filesToDelete = ($request->files_to_delete != '') ? explode(',', $request->files_to_delete) : [];

                    $params = [
                        'files' => $request->file(),
                        'element_id' => $affiliate->id,
                        'model' => 'company'
                    ];
                    FileHelper::upload($params, $filesToDelete, true);

                    //Save files
                    if (count($request->file()) > 0) {

                        if($affiliate->logo){
                            //Making preview
                            $previewName = 'preview_'.$affiliate->logo->name;
                            $storagePath = Storage::disk('local')->getAdapter()->getPathPrefix().'public/';
                            $previewPath = $storagePath.str_replace($affiliate->logo->name, $previewName, $affiliate->logo->path);
                            $preview = new ImageHelper($storagePath.$affiliate->logo->path);
                            $preview->resize($this->config['width'], $this->config['height']);
                            $preview->save($previewPath);
                        }
                    }

                    $this->result['status'] = 'success';
                    $this->result['data']   = $affiliate;
                    $this->message( 'success', __( 'billing/affiliate.txt_updated' ) );

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
            $this->message( 'danger', __( 'app.error_not_found' ) );
        }

        return $this->result();

    }
}
