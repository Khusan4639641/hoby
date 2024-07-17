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

use App\Models\Discount as Model;
use App\Models\DiscountLanguage;

class DiscountController extends CoreController
{
    private $config;


    /**
     * Fields validator
     *
     * @param array $data
     * @return Validator
     */
    private $validatorRules = [
        'date_start' => ['required', 'date', 'date_format:d.m.Y'],
        'time_start' => ['required', 'date_format:H:i'],

        'date_end' => [/*'required',*/ 'date', 'date_format:d.m.Y'],
        'time_end' => [/*'required',*/ 'date_format:H:i'],

        'discount_3' => ['required', 'numeric'],
        'discount_6' => ['required', 'numeric'],
        'discount_9' => ['required', 'numeric'],
        'discount_12' => ['required', 'numeric'],
    ];

    private $localeValidatorRules = [
        'title' => ['required', 'string', 'max:255'],
        'slug' => ['required', 'string', 'max:255'],
        'preview_text' => ['required'],
        'detail_text' => ['required']
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

        //Eager load
        $this->loadWith = ['languages', 'languages.images'];
    }


    /**
     * @param $user
     * @param $news
     * @return bool
     */
    private function isValid($user, $news) {
        if (
            ($user != null && $user->can('modify', $news)) ||
            (
                $news->status == 1 && $news->language(app()->getLocale())->first()
            )
        )
            return true;
        return false;
    }


    /**
     * @param array $params
     * @return array
     */
    public function filter($params = []){

        //Firstly search DiscountLanguage
        if(isset($params['search'])) {
            $id = DiscountLanguage::where('title','like', '%'.$params['search'].'%')->pluck('discount_id')->toArray();
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
                foreach ($item->languages as $language) {
                    $locales[$language->language_code] = $language;
                    foreach($language->images as $image){
                        $locales[$language->language_code][$image->type] = $image;
                        $locales[$language->language_code][$image->type]->preview = Storage::url($image->path);
                    }
                }
                $item->locales = $locales;

                //Current locale
                $item->locale = $locales[app()->getLocale()] ?? $locales[$this->defaultLocale];

            }else
                $filter['result']->forget($index);


        //Collect data
        $this->result['response']['total']  = $filter['total'];
        $this->result['status']             = 'success';

        //Preparing list
        if(isset($params['list_type']) && $params['list_type'] == 'data_tables')
            $filter['result'] = $this->formatDataTables( $filter['result'] );

        $this->result['data']               = $filter['result'];

        //Return data
        return $this->result();
    }


    /**
     * Detail news
     *
     * @param $id
     * @return array|bool|false|string
     */
    public function detail(int $id) {
        $user = Auth::user();
        $discount = $this->single($id);

        if($discount) {
            if($user->can('detail', $discount)) {
                $this->result['status'] = 'success';

                //Locales
                $locales = [];

                foreach ($discount->languages as $language) {
                    $locales[$language->language_code] = $language;
                    foreach($language->images as $image){
                        $locales[$language->language_code][$image->type] = $image;
                        $locales[$language->language_code][$image->type]->preview = Storage::url($image->path);
                    }
                }
                $discount->locales = $locales;

                //Current locale
                $discount->locale = $locales[app()->getLocale()] ?? $locales[$this->defaultLocale];

                $this->result['data'] = $discount;
            }else {
                //Error: access denied
                $this->result['status'] = 'error';
                $this->result['response']['code'] = 403;
                $this->message( 'danger', __( 'app.err_access_denied' ) );
            }
        }else{
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message( 'danger', __( 'app.err_not_found' ) );
        }

        return $this->result();
    }


    /**
     * @param $image
     */
    private function savePreview($image) {
        $previewName = 'preview_'.$image->name;
        $storagePath = Storage::disk('local')->getAdapter()->getPathPrefix().'public/';
        $previewPath = $storagePath.str_replace($image->name, $previewName, $image->path);

        $preview = new ImageHelper($storagePath.$image->path);
        $preview->resize($this->config['width'], $this->config['height']);
        $preview->save($previewPath);
    }



    /**
     * Add discount
     *
     * @param Request $request
     * @return array|bool|false|string
     */
    public function add(Request $request) {
        $user = Auth::user();

        if($user->can('add', Model::class)) {

            //Fields validation
            $localeRules = array_merge($this->localeValidatorRules, ['image_list' => ['required', 'image'], 'image_detail' => ['required', 'image']]);
            $fieldsAndRules = LocaleHelper::prepareFieldsAndRules($request->all(), $localeRules, $this->defaultLocale);
            $validator = $this->validator($request->all(), $fieldsAndRules['rules'], $this->validatorRules);

            if ($validator->fails() ) {
                //Error: validation error
                $this->result['status']  = 'error';
                $this->result['response']['errors']  = $validator->errors();

            } else {
                //Create discount
                $discount = new Model();
                $discount->user_id              = $user->id;
                $discount->datetime_start       = $request->date_start . ' ' . $request->time_start;
                $discount->datetime_end         = $request->date_end . ' ' . $request->time_end;
                $discount->discount_3           = $request->discount_3;
                $discount->discount_6           = $request->discount_6;
                $discount->discount_9           = $request->discount_9;
                $discount->discount_12           = $request->discount_12;
                $discount->save();

                //Create news item locale(s)
                foreach ($fieldsAndRules['fields'] as $code => $fields) {
                    //Create locale
                    $language = new DiscountLanguage();
                    $language->language_code    = $code;
                    $language->discount_id      = $discount->id;
                    $language->title            = $fields['title'];
                    $language->slug             = $fields['slug'];
                    $language->preview_text     = $fields['preview_text'];
                    $language->detail_text      = $fields['detail_text'];
                    $language->save();

                    //Save file
                    if(isset($fields['image_list'])) {
                        $params = [
                            'files' => ['image_list' => $fields['image_list'] , 'image_detail' => $fields['image_detail']],
                            'element_id' => $language->id,
                            'model' => 'discount-language',
                            'language_code' => $code
                        ];
                        FileHelper::upload($params, [], true);

                        //Making previews
                        $this->savePreview($language->image('image_list')->first());
                        $this->savePreview($language->image('image_detail')->first());
                    }

                }

                //Success: news item created
                $this->result['status'] = 'success';
                $this->message( 'success', __( 'panel/discount.txt_created' ) );
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
     * Modify discount
     *
     * @param Request $request
     * @return array|bool|false|string
     */
    public function modify(Request $request) {
        $user = Auth::user();
        $discount = Model::find($request->id);

        if($discount) {
            if ($user->can('add', Model::class)) {
                //Fields validation
                $fieldsAndRules = LocaleHelper::prepareFieldsAndRules($request->all(), $this->localeValidatorRules, $this->defaultLocale);
                $validator = $this->validator($request->all(), $fieldsAndRules['rules'], $this->validatorRules);


                if ($validator->fails()) {
                    //Error: validation error
                    $this->result['status'] = 'error';
                    $this->result['response']['errors'] = $validator->errors();

                } else {
                    //Update discount
                    $discount->datetime_start   = $request->date_start . ' ' . $request->time_start;
                    $discount->datetime_end     = $request->date_end . ' ' . $request->time_end;
                    $discount->discount_3           = $request->discount_3;
                    $discount->discount_6           = $request->discount_6;
                    $discount->discount_9           = $request->discount_9;
                    $discount->discount_12           = $request->discount_12;
                    $discount->save();

                    //Deleting old files
                    $filesToDelete = ($request->files_to_delete != '') ? explode(',', $request->files_to_delete) : [];
                    FileHelper::delete($filesToDelete);

                    //Update or create news locales
                    foreach ($fieldsAndRules['fields'] as $code => $fields) {
                        $language = $discount->language($code)->first()??new DiscountLanguage();

                        $language->language_code    = $code;
                        $language->discount_id      = $discount->id;
                        $language->title            = $fields['title'];
                        $language->slug             = $fields['slug'];
                        $language->preview_text     = $fields['preview_text'];
                        $language->detail_text      = $fields['detail_text'];
                        $language->save();

                        //Save new files
                        if(isset($fields['image_list'])) {
                            $params = [
                                'files' => ['image_list' => $fields['image_list']],
                                'element_id' => $language->id,
                                'model' => 'discount-language',
                                'language_code' => $code
                            ];
                            FileHelper::upload($params, [], true);

                            //Making previews
                            $this->savePreview($language->image('image_list')->first());
                        }
                        if(isset($fields['image_detail'])) {
                            $params = [
                                'files' => ['image_detail' => $fields['image_detail']],
                                'element_id' => $language->id,
                                'model' => 'discount-language',
                                'language_code' => $code
                            ];
                            FileHelper::upload($params, [], true);

                            //Making previews
                            $this->savePreview($language->image('image_detail')->first());
                        }
                    }

                    //Success: news item created
                    $this->result['status'] = 'success';
                    $this->message('success', __('panel/discount.txt_updated'));
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
     * @param Model $discount
     * @return array|bool|false|string
     */
    public function publish(Model $discount){
        $user    = Auth::user();

        if($discount) {
            if ($user->can('modify', $discount)) {
                $discount->status = 1;
                $discount->save();

                $this->result['status'] = 'success';
                $this->message('success', __('panel/discount.txt_published'));
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
     * @param Model $discount
     * @return array|bool|false|string
     */
    public function archive(Model $discount){
        $user    = Auth::user();

        if($discount) {
            if ($user->can('modify', $discount)) {
                $discount->status = 8;
                $discount->save();

                $this->result['status'] = 'success';
                $this->message('success', __('panel/discount.txt_archived'));
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
     * @param Model $discount
     * @return array|false|string
     * @throws Exception
     */
    public function delete(Model $discount){
        $user    = Auth::user();

        if($discount) {
            if ($user->can('delete', $discount)) {

                foreach($discount->languages as $language){
                    foreach($language->images as $image)
                        FileHelper::delete($image->id);
                    $language->delete();
                }

                $discount->delete();

                $this->result['status'] = 'success';
                $this->message('success', __('panel/discount.txt_deleted'));
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
