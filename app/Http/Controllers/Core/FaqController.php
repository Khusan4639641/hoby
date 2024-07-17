<?php

namespace App\Http\Controllers\Core;

use App\Helpers\FileHelper;
use App\Helpers\ImageHelper;
use App\Helpers\LocaleHelper;
use Exception;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request as FacadesRequest;
use Illuminate\Support\Facades\Storage;

use App\Models\Faq as Model;
use App\Models\FaqLanguage;



class FaqController extends CoreController
{

    private $config;

    /**
     * Fields validator
     *
     * @param array $data
     * @return Validator
     */
    private $validatorRules = [
    ];


    private $localeValidatorRules = [
        'title' => ['required', 'string', 'max:255'],
        'text' => ['required']
    ];


    /**
     * NewsController constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->model = app(Model::class);


        //Eager load
        $this->loadWith = ['languages'];
    }


    /**
     * @param array $params
     * @return array
     */
    public function filter($params = []){

        //Firstly search faq languages
        if(isset($params['search'])) {
            $id = FaqLanguage::where('title','like', '%'.$params['search'].'%')->pluck('faq_id')->toArray();
            $params['id'] = $id??[];
        }

        return parent::filter($params);
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
     * @param $user
     * @param $faq
     * @return bool
     */
    private function isValid($user, $faq) {
        if (
            ($user != null && $user->can('modify', $faq)) ||
            (
                $faq->status == 1 && $faq->language(app()->getLocale())->first()
            )
        )
            return true;
        return false;
    }


    /**
     * Detail faq
     *
     * @param int $id
     * @return array|bool|false|string
     */
    public function detail(int $id) {

        $faq = $this->single($id);
        $user = Auth::user();

        if($faq && $this->isValid($user, $faq)){
            $this->result['status'] = 'success';

            //Locales
            $locales = [];

            foreach ($faq->languages as $language)
                $locales[$language->language_code] = $language;

            $faq->locales = $locales;

            //Current locale
            $faq->locale = $locales[app()->getLocale()] ?? $locales[$this->defaultLocale];

            $this->result['data'] = $faq;
        }else{
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message( 'danger', __( 'app.err_not_found' ) );
        }

        return $this->result();
    }



    /**
     * Add faq
     *
     * @param Request $request
     * @return array|bool|false|string
     */
    public function add(Request $request) {
        $user = Auth::user();

        if($user->can('add', Model::class)) {

            //Fields validation
            $fieldsAndRules = LocaleHelper::prepareFieldsAndRules($request->all(), $this->localeValidatorRules, $this->defaultLocale);
            $validator = $this->validator($request->all(), $fieldsAndRules['rules'], $this->validatorRules);

            if ($validator->fails() ) {
                //Error: validation error
                $this->result['status']  = 'error';
                $this->result['response']['errors']  = $validator->errors();
            } else {

                //Create faq item
                $faq = new Model();
                $faq->status    = $request->status;
                $faq->user_id   = $user->id;
                $faq->date      = $request->date;
                $faq->save();

                //Create faq item locale(s)
                foreach ($fieldsAndRules['fields'] as $code => $fields) {

                    //Create locale
                    $language = new FaqLanguage();
                    $language->language_code    = $code;
                    $language->faq_id           = $faq->id;
                    $language->title            = $fields['title'];
                    $language->text             = $fields['text'];
                    $language->save();

                }

                //Success: faq item created
                $this->result['status'] = 'success';
                $this->message( 'success', __( 'panel/faq.txt_created' ) );
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
     * Modify faq
     *
     * @param Request $request
     * @return array|bool|false|string
     */
    public function modify(Request $request){
        $user = Auth::user();
        $faq = Model::find($request->id);

        if($faq) {
            if ($user->can('modify', $faq)) {
                //Fields validation
                $fieldsAndRules = LocaleHelper::prepareFieldsAndRules($request->all(), $this->localeValidatorRules, $this->defaultLocale);
                $validator = $this->validator($request->all(), $fieldsAndRules['rules'], $this->validatorRules);

                if ($validator->fails()) {
                    //Error: validation error
                    $this->result['status'] = 'error';
                    $this->result['response']['errors'] = $validator->errors();
                } else {
                    //Update faq item
                    $faq->status = $request->status;
                    $faq->save();


                    //Update or create faq locales
                    foreach ($fieldsAndRules['fields'] as $code => $fields) {
                        $language = $faq->language($code)->first()??new FaqLanguage();

                        $language->language_code    = $code;
                        $language->faq_id          = $faq->id;
                        $language->title            = $fields['title'];
                        $language->text     = $fields['text'];
                        $language->save();

                    }

                    //Success: faq item created
                    $this->result['status'] = 'success';
                    $this->message('success', __('panel/faq.txt_updated'));
                }
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
     * @param Model $faq
     * @return array|bool|false|string
     */
    public function publish(Model $faq){
        $user    = Auth::user();
        if($faq) {
            if ($user->can('modify', $faq)) {
                $faq->status = 1;
                $faq->save();

                $this->result['status'] = 'success';
                $this->message('success', __('panel/faq.txt_published'));
            }else {
                $this->result['status'] = 'error';
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
     * @param Model $faq
     * @return array|bool|false|string
     */
    public function archive(Model $faq){
        $user    = Auth::user();
        if($faq) {
            if ($user->can('modify', $faq)) {
                $faq->status = 8;
                $faq->save();

                $this->result['status'] = 'success';
                $this->message('success', __('panel/faq.txt_archived'));
            }else {
                $this->result['status'] = 'error';
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
     * Delete faq
     *
     * @param Model $faq
     * @return array|bool|false|string
     * @throws Exception
     */
    public function delete(Model $faq){
        $user    = Auth::user();

        if($faq) {
            if ($user->can('delete', $faq)) {

                foreach($faq->languages as $language){
                    $language->delete();
                }

                $faq->delete();

                $this->result['status'] = 'success';
                $this->message('success', __('panel/faq.txt_deleted'));
            } else {
                $this->result['status'] = 'error';
                $this->message('danger', __('app.err_access_denied'));
            }
        }else{
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message( 'danger', __( 'app.err_not_found' ) );
        }

        return $this->result();
    }
}
