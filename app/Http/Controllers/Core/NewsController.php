<?php

namespace App\Http\Controllers\Core;

use App\Helpers\FileHelper;
use App\Helpers\ImageHelper;
use App\Helpers\LocaleHelper;
use App\Helpers\PushHelper;
use App\Http\Requests\NewsRequest;
use App\Models\Buyer;
use Exception;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request as FacadesRequest;
use Illuminate\Support\Facades\Storage;

use App\Models\News as Model;
use App\Models\NewsLanguage;



class NewsController extends CoreController
{

    private $config;

    /**
     * Fields validator
     *
     * @param array $data
     * @return Validator
     */
    private $validatorRules = [
        'date' => ['required', 'date'],
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
        $this->loadWith = ['languages', 'languages.image'];
    }


    /**
     * @param array $params
     * @return array
     */
    public function filter($params = []){

        //Firstly search newslanguages
        if(isset($params['search'])) {
            $id = NewsLanguage::where('title','like', '%'.$params['search'].'%')->pluck('news_id')->toArray();
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

        $request = request();

        if($request->has('type')){
            if($request->type=='mobile') {
                $params['is_mobile'] = 1;
            }elseIf($request->type=='web') {
                $params['status'] = 1;
                $params['is_mobile'] = 0;
            }elseIf($request->type=='all') {
                $params['status'] = 1;
            }
        }else{
           // $params['is_mobile'] = 0;
            $params['status'] = 1;
        }


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
                    if($locales[$language->language_code]['image']) {
                        //News image
                        $locales[$language->language_code]['image'] = $language->image;

                        //Preparing preview
                        $previewPath = str_replace($language->image->name, 'preview_' . $language->image->name, $language->image->path);
                        $locales[$language->language_code]['image']->preview = "'" . Storage::url($previewPath) ."'";
                        //$locales[$language->language_code]['image']->preview = "'" . \App\Helpers\FileHelper::url($previewPath) . "'";
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

        //Format data
        if(isset($params['list_type']) && $params['list_type'] == 'data_tables')
            $filter['result'] = $this->formatDataTables( $filter['result'] );

        $this->result['data'] = $filter['result'];

        //Return data
        return $this->result();
    }


    /**
     * @param $user
     * @param $news
     * @return bool
     */
    private function isValid($user, $news) {
        $routeName = request()->route()->getName();
        $re = '/^panel/m';
        preg_match_all($re, $routeName, $matches, PREG_SET_ORDER, 0);
        $isPanel = !empty($matches);

        if($isPanel && $user != null && $user->can('modify', $news) ||
           (
               $news->status == 1 && $news->language(app()->getLocale())->first()
           )
        )
            return true;
        return false;
    }


    /**
     * Detail news
     *
     * @param int $id
     * @return array|bool|false|string
     */
    public function detail(int $id) {

        //$request = request();
        //$id = $request->id ?? $id;


        $news = $this->single($id);
        $user = Auth::user();

        if($news && $this->isValid($user, $news)){
            $this->result['status'] = 'success';

            //Locales
            $locales = [];

            foreach ($news->languages as $language) {
                $locales[$language->language_code] = $language;
                if($locales[$language->language_code]['image']) {
                    //News image
                    $locales[$language->language_code]['image'] = $language->image;

                    //Preparing preview
                    $previewPath = str_replace($language->image->name, 'preview_' . $language->image->name, $language->image->path);
                    $locales[$language->language_code]['image']->preview = "'" . Storage::url($previewPath) . "'";
                   // $locales[$language->language_code]['image']->preview = "'" . \App\Helpers\FileHelper::url($previewPath) . "'";
                }
            }
            $news->locales = $locales;

            //Current locale
            $news->locale = $locales[app()->getLocale()] ?? $locales[$this->defaultLocale];

            $this->result['data'] = $news;
        }else{
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message( 'danger', __( 'app.err_not_found' ) );
        }

        return $this->result();
    }



    /**
     * Add news
     *
     * @param NewsRequest $request
     * @return array|bool|false|string
     */
    public function add(NewsRequest $request) {
        $user = Auth::user();

        if($user->can('add', Model::class)) {

            //Fields validation
            $localeRules = array_merge($this->localeValidatorRules, ['image' => ['image']]);
            $fieldsAndRules = LocaleHelper::prepareFieldsAndRules($request->all(), $localeRules, $this->defaultLocale);
            $validator = $this->validator($request->all(), $fieldsAndRules['rules'], $this->validatorRules);

            if ($validator->fails() ) {
                //Error: validation error
                $this->result['status']  = 'error';
                $this->result['response']['errors']  = $validator->errors();
            } else {

                //Create news item
                $news = new Model();
                $news->status    = $request->status;
                $news->user_id   = $user->id;
                $news->date      = $request->date;
                $news->is_mobile = $request->is_mobile;
                $news->save();

                $news_data = ['ru','uz'];

                //Create news item locale(s)
                foreach ($fieldsAndRules['fields'] as $code => $fields) {

                    //Create locale
                    $language = new NewsLanguage();
                    $language->language_code    = $code;
                    $language->news_id          = $news->id;
                    $language->title            = $fields['title'];
                    $language->slug             = $fields['slug'];
                    $language->preview_text     = $fields['preview_text'];
                    $language->detail_text      = $fields['detail_text'];
                    $language->save();

                    //Save file
                    if(isset($fields['image'])) {
                        $params = [
                            'files' => ['image' => $fields['image']],
                            'element_id' => $language->id,
                            'model' => 'news-language',
                            'language_code' => $code
                        ];

                        FileHelper::upload($params, [], true);

                        if($language->image){
                            //Making preview
                            $previewName = 'preview_'.$language->image->name;
                            $storagePath = Storage::disk('local')->getAdapter()->getPathPrefix().'public/';
                            $previewPath = $storagePath.str_replace($language->image->name, $previewName, $language->image->path);
                            $preview = new ImageHelper($storagePath.$language->image->path);
                            $preview->resize($this->config['width'], $this->config['height']);
                            $preview->save($previewPath);
                        }
                    }

                    $news_data[$code] = ['title'=>$language->title,'text'=>$language->preview_text];

                }

                $buyerInfo = Buyer::getInfo($user->id);

                // 25.08 - 15.07.2021 - пуш уведомление для всех клиентов
                $options = [
                    'type'=>PushHelper::TYPE_NEWS_ALL,
                    'id' => $news->id,
                    'title' => strip_tags(@$news_data[$buyerInfo['lang']]['title']),
                    'text' => strip_tags(@$news_data[$buyerInfo['lang']]['text']),
                    'system' => $scheduleItem->buyer->device_os ?? 'android',
                    'buyer' => $buyerInfo
                ];
                PushHelper::send($options);

                //Success: news item created
                $this->result['status'] = 'success';
                $this->message( 'success', __( 'panel/news.txt_created' ) );
                Log::channel('push')->info('PUSH SUCCESS news_id: ' ,  ['newsID' => $news->id]);

            }
        }else {
            //Error: access denied
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 403;
            $this->message( 'danger', __( 'app.err_access_denied' ) );
            Log::channel('push')->info('ERROR access denied');
        }

        return $this->result();
    }



    /**
     * Modify news
     *
     * @param NewsRequest $request
     * @return array|bool|false|string
     */
    public function modify(NewsRequest $request){
        $user = Auth::user();
        $news = Model::find($request->id);

        if($news) {
            if ($user->can('modify', $news)) {
                //Fields validation
                $fieldsAndRules = LocaleHelper::prepareFieldsAndRules($request->all(), $this->localeValidatorRules, $this->defaultLocale);
                $validator = $this->validator($request->all(), $fieldsAndRules['rules'], $this->validatorRules);

                if ($validator->fails()) {
                    //Error: validation error
                    $this->result['status'] = 'error';
                    $this->result['response']['errors'] = $validator->errors();
                } else {
                    //Update news item
                    $news->date = $request->date;
                    $news->status = $request->status;
                    $news->is_mobile = $request->is_mobile;
                    $news->save();

                    //Deleting old files
                    $filesToDelete = ($request->files_to_delete != '') ? explode(',', $request->files_to_delete) : [];
                    FileHelper::delete($filesToDelete);

                    //Update or create news locales
                    foreach ($fieldsAndRules['fields'] as $code => $fields) {
                        $language = $news->language($code)->first()??new NewsLanguage();

                        $language->language_code    = $code;
                        $language->news_id          = $news->id;
                        $language->title            = $fields['title'];
                        $language->slug             = $fields['slug'];
                        $language->preview_text     = $fields['preview_text'];
                        $language->detail_text      = $fields['detail_text'];
                        $language->save();

                        //Save new files
                        if(isset($fields['image'])) {
                            $params = [
                                'files' => ['image' => $fields['image']],
                                'element_id' => $language->id,
                                'model' => 'news-language',
                                'language_code' => $code
                            ];
                            FileHelper::upload($params, [], true);

                            if($language->image){
                                //Making preview
                                $previewName = 'preview_'.$language->image->name;
                                $storagePath = Storage::disk('local')->getAdapter()->getPathPrefix().'public/';
                                $previewPath = $storagePath.str_replace($language->image->name, $previewName, $language->image->path);
                                $preview = new ImageHelper($storagePath.$language->image->path);
                                $preview->resize($this->config['width'], $this->config['height']);
                                $preview->save($previewPath);
                            }
                        }
                    }

                    //Success: news item created
                    $this->result['status'] = 'success';
                    $this->message('success', __('panel/news.txt_updated'));
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
     * @param Model $news
     * @return array|bool|false|string
     */
    public function publish(Model $news){
        $user    = Auth::user();
        if($news) {
            if ($user->can('modify', $news)) {
                $news->status = 1;
                $news->save();

                $this->result['status'] = 'success';
                $this->message('success', __('panel/news.txt_published'));
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
     * @param Model $news
     * @return array|bool|false|string
     */
    public function archive(Model $news){
        $user    = Auth::user();
        if($news) {
            if ($user->can('modify', $news)) {
                $news->status = 8;
                $news->save();

                $this->result['status'] = 'success';
                $this->message('success', __('panel/news.txt_archived'));
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
     * Delete news
     *
     * @param Model $news
     * @return array|bool|false|string
     * @throws Exception
     */
    public function delete(Model $news){
        $user    = Auth::user();

        if($news) {
            if ($user->can('delete', $news)) {

                foreach($news->languages as $language){
                    if($language->image)
                        FileHelper::delete($language->image->id);
                    $language->delete();
                }

                $news->delete();

                $this->result['status'] = 'success';
                $this->message('success', __('panel/news.txt_deleted'));
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
