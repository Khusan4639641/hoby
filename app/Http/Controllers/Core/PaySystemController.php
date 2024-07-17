<?php

namespace App\Http\Controllers\Core;


use App\Helpers\FileHelper;
use App\Helpers\ImageHelper;
use App\Helpers\SmsHelper;
use App\Http\Controllers\Core\Auth\RegisterController;
use App\Models\CatalogCategory;
use App\Models\Company;
use App\Models\PaySystem as Model;

use App\Models\Partner;
use App\Models\PartnerSetting;
use App\Models\PaySystem;
use App\Models\User;
use App\Rules\AffiliateInn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PaySystemController extends CoreController
{


    /**
     * PartnerController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->model = app(Model::class);
        $this->config = Config::get('test.preview');
        //$this->loadWith = ['logo'];
    }


    /**
     * @param $id
     * @param array $with
     * @return Builder|\Illuminate\Database\Eloquent\Model|object
     */
    protected function single($id, $with = [])
    {
        $single = parent::single($id, array_merge($this->loadWith, []));
        return $single;
    }


    /**
     * @param $id
     * @return array|false|string
     */
    public function detail($id)
    {
        $user = Auth::user();
        $pay_system = $this->single($id);

        if ($pay_system) {
            if ($user /*&& $user->can('detail', $pay_system)*/) {
                $this->result['status'] = 'success';
                $this->result['data'] = $pay_system;
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
     * Get items ids list
     *
     * @param array $params
     * @return array|bool|false|string
     */
    public function list( array $params = []) {

        $user = Auth::user();

        //Get data from REQUEST if api_token is set
        $request = FacadesRequest::all();
        if ( isset( $request['api_token'] ))
            $params = $request;

        //Filter elements
        $filter = $this->filter($params);

        foreach ($filter['result'] as $index => $item)
            if ($this->isValid($user, $item)) {
                if($user != null)
                    $item->permissions = $this->permissions($item, $user);
                //Locales
                $locales = [];

                foreach ($item->languages as $language)
                    $locales[$language->language_code] = $language;


                $item->locales = $locales;

                //Current locale
                $item->locale = $locales[app()->getLocale()] ?? $locales[$this->defaultLocale];

            }else
                $filter['result']->forget($index);


        //Collect data
        $this->result['response']['total']  = $filter['total'];
        $this->result['status']             = 'success';

        //Format data
        if(isset($params['list_type']) && $params['list_type'] == 'data_tables')
            $filter['result'] = $this->formatDataTables( $filter['result'] );

        $this->result['data'] = $filter['result'];

        //Return data
        return $this->result();
    }


    /**
     * @param Request $request
     * @return array|false|string
     */
    public function add(Request $request)
    {

        $user = Auth::user();

        if ($user->can('add', Model::class)) {

            //Create payment system
            $paysys = new PaySystem();
            $paysys->title = $request->title;
            $paysys->url = $request->link;
            $paysys->status = $request->status;
            $paysys->save();

            //Save files
            if (count($request->file()) > 0) {
                $params = [
                    'files' => $request->file(),
                    'element_id' => $paysys->id,
                    'model' => 'paysys'
                ];
                FileHelper::upload($params, [], true);

                if ($paysys->logo) {
                    //Making preview
                    $previewName = 'preview_' . $paysys->logo->name;
                    $storagePath = Storage::disk('local')->getAdapter()->getPathPrefix() . 'public/';
                    $previewPath = $storagePath . str_replace($paysys->logo->name, $previewName, $paysys->logo->path);
                    $preview = new ImageHelper($storagePath . $paysys->logo->path);
                    $preview->resize($this->config['width'], $this->config['height']);
                    $preview->save($previewPath);

                }
            }




            //Success: news item created
            $this->result['status'] = 'success';
            $this->message('success', __('billing/affiliate.txt_created'));

        } else {
            //Error: access denied
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 403;
            $this->message('danger', __('app.err_access_denied'));
        }

        return $this->result();
    }


    /**
     * @param Request $request
     *
     * @return array|false|string
     */
    public function modify(Request $request)
    {

        $user = Auth::user();
        $pay_sys = Model::find($request->id);


        if ($pay_sys) {
            if ($user->can('modify', $pay_sys)) {

                $moreValidatorRules = [
                    'title' => ['required', 'string', 'max:255'],
                    'url' => ['required', 'string', 'max:255'],
                    'status' => ['required', 'number']
                ];

                $validator = $this->validator($request->all(), $this->validatorRules, $moreValidatorRules);

                if ($validator->fails()) {
                    // error
                    $this->result['status'] = 'error';
                    $this->result['response']['errors'] = $validator->errors();

                } else {
                    //User
                    $pay_sys->title = $request->name;
                    $pay_sys->url = $request->name;
                    $pay_sys->status = $request->name;

                    $pay_sys->save();


                    //Delete files
                    $filesToDelete = ($request->files_to_delete != '') ? explode(',', $request->files_to_delete) : [];
                    if (count($filesToDelete) > 0) FileHelper::delete($filesToDelete);

                    //Save files
                    if (count($request->file()) > 0) {
                        $params = [
                            'files' => $request->file(),
                            'element_id' => $pay_sys->id,
                            'model' => 'company'
                        ];
                        FileHelper::upload($params, [], true);

                        if ($pay_sys->logo) {
                            //Making preview
                            $previewName = 'preview_' . $pay_sys->logo->name;
                            $storagePath = Storage::disk('local')->getAdapter()->getPathPrefix() . 'public/';
                            $previewPath = $storagePath . str_replace($pay_sys->logo->name, $previewName, $pay_sys->logo->path);
                            $preview = new ImageHelper($storagePath . $pay_sys->logo->path);
                            $preview->resize($this->config['width'], $this->config['height']);
                            $preview->save($previewPath);
                        }
                    }

                    $this->result['status'] = 'success';
                    $this->result['data'] = $pay_sys;
                    $this->message('success', __('panel/$pay_sys.txt_updated'));

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
}
