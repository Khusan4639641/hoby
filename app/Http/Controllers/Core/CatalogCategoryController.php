<?php

namespace App\Http\Controllers\Core;

use App\Helpers\FileHelper;
use App\Helpers\ImageHelper;
use App\Helpers\LocaleHelper;
use App\Models\CatalogCategory;
use App\Models\CatalogCategory as Model;
use App\Models\CatalogCategoryLanguage;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class CatalogCategoryController extends CoreController {

    /**
     * Fields validator
     *
     * @param array $data
     *
     * @return Validator
     */
    private $validatorRules = [

    ];

    private $localeValidatorRules = [
        'title'        => [ 'required', 'string', 'max:255' ],
        'slug'         => [ 'required', 'string', 'max:255' ],
        'preview_text' => [ 'nullable' ],
        'detail_text'  => [ 'nullable' ],
        'image' => ['image']
    ];

    public function __construct() {
        parent::__construct();
        $this->model = app( Model::class );
        $this->config = Config::get( 'test.preview' );
        $this->loadWith = ['parent'];
    }


    /**
     * @OA\Get(
     *      path="/catalog/categories/list",
     *      operationId="categories-list",
     *      tags={"Products action"},
     *      summary="List categories of products",
     *      description="Return json",
     *      security={{"api_token_security":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       ),
     *      @OA\Response(
     *       response=201,
     *       description="Success",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     *     )
     */
    /**
     * Orders list
     *
     * @param array $params
     * @return array|bool|false|string
     */
    public function list( array $params = [] ) {
        $user     = Auth::user();
        $request       = request()->all();

        if (isset($request['api_token'])) {
            unset($request['api_token']);
            $params = $request;
        }

        if(!isset($params['parent_id'])){
            $params['parent_id'] = 0;
        }

        if($params['parent_id'] === false){
            unset($params['parent_id']);
        }

        //Filter elements
        $filter = $this->filter($params);


        foreach ( $filter['result'] as $index => $item ) {

            if($user) $item->permissions = $this->permissions($item, $user);

            //Locales
            $locales = [];

            foreach ( $item->languages as $language ) {
                $locales[ $language->language_code ] = $language;
            }

            //Current locale
            $item->locale = isset($locales[ app()->getLocale() ]) ?  $locales[ app()->getLocale() ] : $locales[ Config::get('app.fallback_locale') ];

        }

        //Collect data
        $this->result['response']['total'] = $filter['total'];
        $this->result['status'] = 'success';

        if (isset($params['list_type']) && $params['list_type'] == 'data_tables') {
            $filter['result'] = $this->formatDataTables(self::tree());
        }

        $this->result['data'] = $filter['result'];

        //Return data
        return $this->result();
    }

    private static function createTree($data, $parent = 0) {
        $parents = [];
        foreach ($data as $key => $item):
            $parents[$item->parent_id][$item->id] = $item;
        endforeach;

        $treeElem = $parents[$parent] ?? [];
        self::generateElemTree($treeElem, $parents);
        return $treeElem;
    }

    /**
     * @param $treeElem
     * @param $parents
     * Генерируем элементы дерева с учётом удобного вывода потомков
     */
    private static function generateElemTree(&$treeElem, $parents, $level = 0) {

        foreach ($treeElem as $key => $item):
            $item->level = $level;
            if (!isset($item->child)):
                $treeElem[$key]->child = [];
            endif;
            $locales = [];

            foreach ( $item->languages as $language ) {
                $locales[ $language->language_code ] = $language;
            }
            $treeElem[$key]->locales = $locales;

            //Current locale
            $treeElem[$key]->locale = $locales[ app()->getLocale() ] ?? $locales[ Config::get('app.fallback_locale') ];
            if (array_key_exists($key, $parents)):
                $treeElem[$key]->child = $parents[$key];
                self::generateElemTree($parents[$key], $parents, $level + 1);
            endif;
        endforeach;
    }

    public static function tree( $parent = 0, $exclude = [], $parentsOnly=false ) {
        $request       = request()->all();
        if (isset($request['api_token'])) {
            unset($request['api_token']);
            $parent = $request['parent'] ?? 0;
        }

        $query = Model::query();

        $query->with('image'); // берем фото

        if(count($exclude) > 0){
            $query->whereNotIn('id', $exclude);
        }

        // берем только корневые
        if($parentsOnly) $query->where('parent_id',0);

        $categories = $query->get();

        //$categories = Model::get();
        return self::createTree($categories, $parent);
    }

    /**
     * @param $id
     * @param array $with
     * @return Builder|\Illuminate\Database\Eloquent\Model|object
     */
    public function single( $id, $with = [] ) {
        $single = parent::single( $id, array_merge($this->loadWith, $with) );
        //Locales
        $locales = [];

        if($single) {
            foreach ( $single->languages as $language ) {
                $locales[ $language->language_code ] = $language;
            }
            $single->locales = $locales;
            //Current locale
            $single->locale = $locales[ app()->getLocale() ] ?? $locales[ Config::get('app.fallback_locale') ];
        }

        return $single;
    }



    /**
     * Detail product
     *
     * @param $id
     *
     * @return array|bool|false|string
     */
    public function detail($id) {
        $category = $this->single($id, ['languages', 'products', 'products.languages', 'fields']);

        if ( $category ) {
            $this->result['status']          = 'success';
            $this->result['data'] = $category;
        } else {
            $this->result['status']           = 'error';
            $this->result['response']['code'] = 404;
            $this->message( 'danger', __( 'app.err_not_found' ) );
        }

        return $this->result();
    }


    /**
     * @param array $params
     * @return array
     */
    public function filter( $params = [] ) {
        if(isset($params['title__like'])) {
            $categoriesID = CatalogCategoryLanguage::where('title', 'like', '%'.$params['title__like'].'%')->pluck('category_id')->toArray();
            $params['id'] = $categoriesID??[];
        }

        return parent::filter( $params );
    }


    /**
     * @param $user
     * @param $category
     * @return bool
     */
    private function isValid($user, $category) {

        if($user)
            if ($user->can('detail', $category))
                return true;
            else
                return false;
        return true;
    }




    /**
     * Add category
     *
     * @param Request $request
     *
     * @return array|bool|false|string
     */
    public function add( Request $request ) {
        $user = Auth::user();
        if ( $user->can( 'add', Model::class ) ) {
            $fieldsAndRules = LocaleHelper::prepareFieldsAndRules( $request->all(), $this->localeValidatorRules, 'ru' );

            $rules = array_merge($this->validatorRules, []);

            $validator      = $this->validator( $request->all(), $rules, $fieldsAndRules['rules'] );

            if ( $validator->fails() ) {
                $this->result['status']             = 'error';
                $this->result['response']['errors'] = $validator->errors();
            } else {

                $category           = new Model();
                $category->sort  = $request->sort;
                $category->parent_id  = $request->parent;
                $category->save();

                if($request->fields) {
                    $arrFields = [];
                    foreach ($request->fields as $id) {
                        $arrFields[$id] = [
                            "sort" => $request->fields_sort[$id] ?? 500
                        ];
                    }


                    $category->fields()->sync($arrFields);

                }
                //Delete files
                $filesToDelete = ($request->files_to_delete != '') ? explode(',', $request->files_to_delete) : [];

                //Save files
                if (count($request->file()) > 0) {
                    $params = [
                        'files' => $request->file(),
                        'element_id' => $category->id,
                        'model' => 'catalog-category'
                    ];
                    FileHelper::upload($params, $filesToDelete, true);

                    //Making preview
                    if($category->image){
                        $previewName = 'preview_'.$category->image->name;
                        $storagePath = Storage::disk('local')->getAdapter()->getPathPrefix().'public/';
                        $previewPath = $storagePath.str_replace($category->image->name, $previewName, $category->image->path);
                        $preview = new ImageHelper($storagePath.$category->image->path);
                        $preview->resize($this->config['width'], $this->config['height']);
                        $preview->save($previewPath);
                    }

                }

                //Create product item locale(s)
                foreach ( $fieldsAndRules['fields'] as $code => $fields ) {

                    $categoryLanguage                = new CatalogCategoryLanguage();
                    $categoryLanguage->category_id   = $category->id;
                    $categoryLanguage->language_code = $code;
                    $categoryLanguage->title         = $fields['title'];
                    $categoryLanguage->preview_text  = $fields['preview_text'] ?? '';
                    $categoryLanguage->detail_text   = $fields['detail_text'] ?? '';
                    $categoryLanguage->slug          = $fields['slug'];
                    $categoryLanguage->save();

                }


                $this->result['status'] = 'success';
                $this->message( 'success', __( 'panel/catalog.category.txt_created' ) );
            }

            //Success: product item created

        } else {
            $this->result['status']           = 'error';
            $this->result['response']['code'] = 403;
            $this->message( 'danger', __( 'app.err_access_denied' ) );
        }

        return $this->result();
    }


    /**
     * Modify category
     *
     * @param Request $request
     *
     * @param Model $category
     * @return array|bool|false|string
     */
    public function modify(Request $request, Model $category ) {

        $user = Auth::user();
        if ( $user->can( 'modify', $category ) ) {
            $fieldsAndRules = LocaleHelper::prepareFieldsAndRules( $request->all(), $this->localeValidatorRules, 'ru' );
            $validator      = $this->validator( $request->all(), $this->validatorRules, $fieldsAndRules['rules'] );

            if ( $validator->fails() ) {
                $this->result['status']             = 'error';
                $this->result['response']['errors'] = $validator->errors();
            } else {

                $category->sort  = $request->sort;
                $category->parent_id  = $request->parent;
                $category->save();

                $arrFields = [];

                if($request->fields){
                    foreach ($request->fields as $id) {
                        $arrFields[$id] = [
                            "sort" => $request->fields_sort[$id] ?? 500
                        ];
                    }
                }

                $category->fields()->sync( $arrFields );



                //Delete files
                $filesToDelete = ($request->files_to_delete != '') ? explode(',', $request->files_to_delete) : [];

                //Save files

                $params = [
                    'files' => $request->file(),
                    'element_id' => $category->id,
                    'model' => 'catalog-category'
                ];

                FileHelper::upload($params, $filesToDelete, true);

                if (count($request->file()) > 0) {
                    //Making preview
                    if($category->image){
                        $previewName = 'preview_'.$category->image->name;
                        $storagePath = Storage::disk('local')->getAdapter()->getPathPrefix().'public/';
                        $previewPath = $storagePath.str_replace($category->image->name, $previewName, $category->image->path);
                        $preview = new ImageHelper($storagePath.$category->image->path);
                        $preview->resize($this->config['width'], $this->config['height']);
                        $preview->save($previewPath);
                    }
                }

                //Create product item locale(s)
                foreach ( $fieldsAndRules['fields'] as $code => $fields ) {

                    $categoryLanguage = $category->language($code)->first()??new CatalogCategoryLanguage();

                    $categoryLanguage->category_id    = $category->id;
                    $categoryLanguage->language_code = $code;
                    $categoryLanguage->title         = $fields['title'];
                    $categoryLanguage->preview_text  = $fields['preview_text'] ?? '';
                    $categoryLanguage->detail_text   = $fields['detail_text'] ?? '';
                    $categoryLanguage->slug          = $fields['slug'];
                    $categoryLanguage->save();

                }

                $this->result['status'] = 'success';
                $this->message( 'success', __( 'panel/catalog.category.txt_updated' ) );
            }

            //Success: product item created

        } else {
            $this->result['status']           = 'error';
            $this->result['response']['code'] = 403;
            $this->message( 'danger', __( 'app.err_access_denied' ) );
        }

        return $this->result();
    }


    /**
     * Delete category
     *
     * @param Model $category
     * @return array|bool|false|string
     * @throws \Exception
     */
    public function delete( Model $category ) {

        $user = Auth::user();

        if ( $category ) {
            if ( $user->can( 'delete', $category ) ) {
                if($category->childCategories->count() > 0){
                    $this->result['status'] = 'error';
                    $this->message( 'danger', __( 'panel/catalog.category.err_category_have_childs' ) );
                } else {
                    $category->products()->detach();
                    $category->fields()->detach();
                    $category->languages()->delete();
                    $category->delete();

                    $this->result['status'] = 'success';
                    $this->message( 'success', __( 'panel/catalog.category.txt_deleted' ) );
                }

            } else {
                $this->result['status'] = 'error';
                $this->message( 'danger', __( 'app.err_access_denied' ) );
            }
        } else {
            $this->result['status']           = 'error';
            $this->result['response']['code'] = 404;
            $this->message( 'danger', __( 'app.err_not_found' ) );
        }

        return $this->result();
    }

    public function fields(CatalogCategory $category){
        if ( $category ) {
            $this->result['status']          = 'success';
            $this->result['data'] = $category->fields;
        } else {
            $this->result['status']           = 'error';
            $this->result['response']['code'] = 404;
            $this->message( 'danger', __( 'app.err_not_found' ) );
        }

        return $this->result();
    }

}
