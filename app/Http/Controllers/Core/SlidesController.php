<?php

namespace App\Http\Controllers\Core;

use App\Helpers\FileHelper;
use App\Helpers\ImageHelper;
use App\Helpers\LocaleHelper;
use App\Models\Slide as Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request as FacadesRequest;
use Illuminate\Support\Facades\Storage;

class SlidesController extends CoreController
{
    private $validatorRules = [
        'title' => ['required', 'string', 'max:255'],
        'text' => ['required'],
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
     * @param int $id
     * @return array|bool|false|string
     */
    public function detail(int $id) {

        $slide = $this->single($id);
        $user = Auth::user();

        if($slide){
            if($user->can('modify', $slide)){

                $this->result['status'] = 'success';

                if($slide->image) {
                    //Preparing preview
                    $previewPath = str_replace($slide->image->name, 'preview_' . $slide->image->name, $slide->image->path);
                    $slide->image->preview = Storage::url($previewPath);
                }

                $this->result['data'] = $slide;
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

        foreach ($filter['result'] as $index => $item){

            if($user != null)
                $item->permissions = $this->permissions($item, $user);

            //Preparing preview
            if($item->image) {
                $previewPath = str_replace($item->name, 'preview_' . $item->name, $item->image->path);
                $item->image->preview = Storage::url($previewPath);
            }

       }


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
     * Add news
     *
     * @param Request $request
     * @return array|bool|false|string
     */
    public function add(Request $request) {
        $user = Auth::user();

        if($user->can('add', Model::class)) {

            //Fields validation
            $validator = $this->validator($request->all(), $this->validatorRules);

            if ($validator->fails() ) {
                //Error: validation error
                $this->result['status']  = 'error';
                $this->result['response']['errors']  = $validator->errors();
            } else {

                //Create news item
                $slide = new Model();
                $slide->slider_id           = $request->slider_id;
                $slide->title               = $request->title;
                $slide->text                = $request->text;
                $slide->link                = $request->link;
                $slide->label               = $request->label;
                $slide->language_code       = $request->language_code;
                $slide->sort                = $request->sort;
                $slide->save();

                //Save files
                if ($request->image) {
                    $params = [
                        'files'         => ['image' => $request->image],
                        'element_id'    => $slide->id,
                        'model'         => 'slide',
                    ];
                    FileHelper::upload($params, [], true);

                    if($slide->image){
                        //Making preview
                        $previewName = 'preview_'.$slide->image->name;
                        $storagePath = Storage::disk('local')->getAdapter()->getPathPrefix().'public/';
                        $previewPath = $storagePath.str_replace($slide->image->name, $previewName, $slide->image->path);
                        $preview = new ImageHelper($storagePath.$slide->image->path);
                        $preview->resize($this->config['width'], $this->config['height']);
                        $preview->save($previewPath);
                    }
                }

                //Success: news item created
                $this->result['status'] = 'success';
                $this->message( 'success', __( 'panel/slide.txt_created' ) );
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
     * Delete news
     *
     * @param Model $slide
     * @return array|bool|false|string
     * @throws \Exception
     */
    public function delete(Model $slide){
        $user    = Auth::user();

        if($slide) {
            if ($user->can('delete', $slide)) {

                if($slide->image)
                    FileHelper::delete($slide->image->id);

                $slide->delete();

                $this->result['status'] = 'success';
                $this->message('success', __('panel/slide.txt_deleted'));
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


    /**
     * Modify news
     *
     * @param Request $request
     * @return array|bool|false|string
     */
    public function modify(Request $request){
        $user = Auth::user();
        $slide = Model::find($request->id);

        if($slide) {
            if ($user->can('modify', $slide)) {
                //Fields validation
                $validator = $this->validator($request->all(), $this->validatorRules);

                if ($validator->fails()) {
                    //Error: validation error
                    $this->result['status'] = 'error';
                    $this->result['response']['errors'] = $validator->errors();
                } else {
                    //Update news item
                    $slide->title               = $request->title;
                    $slide->text                = $request->text;
                    $slide->link                = $request->link;
                    $slide->label               = $request->label;
                    $slide->sort                = $request->sort;
                    $slide->language_code       = $request->language_code;
                    $slide->save();

                    //Deleting old files
                    $filesToDelete = ($request->files_to_delete != '') ? explode(',', $request->files_to_delete) : [];
                    FileHelper::delete($filesToDelete);

                    //Save files
                    if ($request->image) {
                        $params = [
                            'files'         => ['image' => $request->image],
                            'element_id'    => $slide->id,
                            'model'         => 'slide',
                        ];
                        FileHelper::upload($params, [], true);

                        if($slide->image){
                            //Making preview
                            $previewName = 'preview_'.$slide->image->name;
                            $storagePath = Storage::disk('local')->getAdapter()->getPathPrefix().'public/';
                            $previewPath = $storagePath.str_replace($slide->image->name, $previewName, $slide->image->path);
                            $preview = new ImageHelper($storagePath.$slide->image->path);
                            $preview->resize($this->config['width'], $this->config['height']);
                            $preview->save($previewPath);
                        }
                    }

                    //Success: news item created
                    $this->result['status'] = 'success';
                    $this->message('success', __('panel/slide.txt_updated'));
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
}
