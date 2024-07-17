<?php

namespace App\Http\Controllers\Core;

use App\Helpers\FileHelper;
use App\Helpers\ImageHelper;
use App\Models\Company;

use App\Rules\AffiliateInn;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

use App\Models\Partner as Model;

class PartnerProfileController extends CoreController
{

    private $config;

    /**
     * Fields validator
     *
     * @param array $data
     * @return Validator
     */
    private $validatorRules = [
        'name' => ['required', 'string', 'max:255'],
        'surname' => ['required', 'string', 'max:255'],
        //'patronymic' => ['required', 'string'],
        'logo' => ['image'],

        'company_name' => ['required', 'string', 'max:255'],
        'company_address' => ['required', 'string', 'max:255'],
        'company_legal_address' => ['required', 'string', 'max:255'],
        'company_bank_name' => ['required', 'string', 'max:255'],
        'company_payment_account' => ['required', 'string', 'max:255'],
    ];


    /**
     * NewsController constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->model = app(Model::class);

        //Config
        $this->config =  Config::get('test.preview');
    }

    /**
     * Detail news
     *
     * @param $id
     * @return array|bool|false|string
     */
    public function detail(int $id) {

        $user = Auth::user();
        $partner = $this->single($id, ['company', 'company.logo', 'settings']);

        if($partner){
            if ($user->can('detail', $partner)) {
                $this->result['status'] = 'success';
                $this->result['data']['partner'] = $partner;
            } else {
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
     * Modify employee
     *
     * @param Request $request
     * @return array|bool|false|string
     */
    public function modify(Request $request) {
        $user = Auth::user();
        $partner = Model::find($request->id);

        if($user->can('modify', $partner)) {

            $moreValidatorRules = [
                'company_inn' => ['required', 'string', 'max:255', new AffiliateInn()],
                'phone'       => [ 'required', 'string', 'max:15', 'regex:/^\+?[0-9]+$/', 'unique:users,phone,' . $request->id],
            ];
            if(isset($request->password))
                $moreValidatorRules = [
                    'password' => ['required', 'string', 'min:8', 'confirmed'],
                ];

            $validator = $this->validator($request->all(), $this->validatorRules, $moreValidatorRules);

            if ($validator->fails() ) {

                //Error: validation error
                $this->result['status'] = 'error';
                $this->result['response']['errors'] = $validator->errors();
            }else{

                //Modify user
                $partner->name          = $request->name;
                $partner->surname       = $request->surname;
                $partner->patronymic    = $request->patronymic;
                $partner->phone         = $request->phone;
                if(isset($request->password))
                    $partner->password  = Hash::make($request->password);
                $partner->save();

                //Modify company
                $company = Company::find($partner->company->id);
                $company->description       = $request->company_description;
                $company->short_description       = $request->company_short_description;
                $company->brand             = $request->company_brand;
                $company->name              = $request->company_name;
                $company->inn               = $request->company_inn;
                $company->address           = $request->company_address;
                $company->legal_address     = $request->company_legal_address;
                $company->bank_name         = $request->company_bank_name;
                $company->payment_account   = $request->company_payment_account;
                $company->website           = $request->company_website;
                $company->working_hours     = $request->company_working_hours;
                $company->phone             = $request->company_phone;
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

                //Success: news item created
                $this->result['status'] = 'success';

                $this->message( 'success', __( 'billing/profile.txt_updated' ) );

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
     * @param Request $request
     * @return array|false|string
     */
    public function modifySettings(Request $request){
        $user = Auth::user();
        $partner = Model::find($request->id);

        if($user->can('modify', $partner)) {
            $partner->settings->discount_3          = $request->discount_3;
            $partner->settings->discount_6          = $request->discount_6;
            $partner->settings->discount_9          = $request->discount_9;
            $partner->settings->check_quantity      = $request->discount_9;
            $partner->settings->save();

            //Success: news item created
            $this->result['status'] = 'success';
            $this->message( 'success', __( 'billing/profile.txt_updated' ) );
        }else {
            //Error: access denied
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 403;
            $this->message( 'danger', __( 'app.err_access_denied' ) );
        }

        return $this->result();
    }
}
