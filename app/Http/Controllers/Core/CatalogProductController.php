<?php

namespace App\Http\Controllers\Core;

use App\Helpers\FileHelper;
use App\Helpers\ImageHelper;
use App\Helpers\LocaleHelper;
use App\Models\CatalogCategory;
use App\Models\CatalogCategoryLanguage;
use App\Models\CatalogCategoryProduct;
use App\Models\CatalogProduct;
use App\Models\CatalogProduct as Model;
use App\Models\CatalogProductLanguage;
use App\Models\Partner;
use Exception;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request as FacadesRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

class CatalogProductController extends CoreController {

    private $validatorRules = [
        'price_origin' => [ 'required' ],
        //'weight' => [ 'required' ],
        'quantity' => [ 'required' ],
        'categories' => [ 'required' ],
        //'vendor_code' => [ 'required' ],
    ];


    private $localeValidatorRules = [
        'title'        => [ 'required', 'string', 'max:255' ],
       // 'slug'         => [ 'required', 'string', 'max:255' ],
        //'preview_text' => [ 'required' ],
        //'detail_text'  => [ 'required' ],
        'fields'       => ['nullable']
    ];


    /**
     * CatalogProductController constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->model = app( Model::class );

        $this->config['preview'] = Config::get( 'test.preview' );
        $this->config['plans']  = Config::get( 'test.plans' );

        $this->loadWith = ['partner', 'categories.language', 'categories.fields', 'partner.company', 'partner.company.logo'];
    }



    /**
     * @param array $params
     * @return array
     */
    public function filter( $params = [] ) {
        if(isset($params['title__like'])) {
            $productsID = CatalogProductLanguage::where('title', 'like', '%'.$params['title__like'].'%')->pluck('product_id')->toArray();
            if(!$productsID){
                $productsID = CatalogProduct::where('vendor_code', 'like', '%'.$params['title__like'].'%')->pluck('id')->toArray();
            }
            $params['id'] = $productsID??[];
        }

        return parent::filter( $params );
    }


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

        //Filter elements
        $filter = $this->filter($params);

        foreach ( $filter['result'] as $index => $item ) {

            if ( $this->isValid($user, $item ) ) {
                if($user)
                    $item->permissions = $this->permissions($item, $user);

                //Locales
                $locales = [];
                foreach ( $item->languages as $language )
                    $locales[ $language->language_code ] = $language;
                $item->locales = $locales;

                //Current locale
                $item->locale = $locales[ app()->getLocale() ] ?? $locales[ Config::get('test.catalog_default_locale') ];

                //
                $item->credit_from = round(($item->price + $item->price*($this->config['plans'][3]/100))/3, 0);

                if($item->images->first()){
                    $previewPath = str_replace($item->images->first()->name, 'preview_' . $item->images[0]->name, $item->images->first()->path);
                    $item->preview = Storage::exists($previewPath) ? Storage::url($previewPath) : null;
                }

            } else {
                $filter['result']->forget( $index );
            }

        }

        //Collect data
        $this->result['response']['vendor_code'] = isset($filter['result'][0]['vendor_code']) ? $filter['result'][0]['vendor_code'] : null;
        //$this->result['response']['price_discount'] = isset($filter['result'][0]['price_discount']) ? $filter['result'][0]['price_discount'] : null;
        $this->result['response']['total'] = $filter['total'];
        $this->result['status'] = 'success';

        if (isset($params['list_type']) && $params['list_type'] == 'data_tables')
            $filter['result'] = $this->formatDataTables($filter['result']);

        $this->result['data'] = $filter['result'];

        //Return data
        return $this->result();
    }


    /**
     * @param $user
     * @param $product
     * @return bool
     */
    private function isValid($user, $product) {

        if($user)
            if ($user->can('detail', $product))
                return true;
            else
                return false;
        return true;
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
            $single->locale = $locales[ app()->getLocale() ] ?? $locales[ Config::get('test.catalog_default_locale') ];

            if($single->images->first()){
                $previewPath = str_replace($single->images->first()->name, 'preview_' . $single->images[0]->name, $single->images->first()->path);
                $single->preview = Storage::exists($previewPath) ? Storage::url($previewPath) : null;
            }

            $single->escapedFields = str_replace("'", "\'", $single->getRawOriginal('fields'));
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
        $product = $this->single($id);
        if ( $product ) {
            //Credit from
            $product->credit_from = round(($product->price + $product->price*($this->config['plans'][3]/100))/3, 0);

            $this->result['status']          = 'success';
            $this->result['data'] = $product;
            $this->result['data']['category_fields'] = isset($product->categories) && count($product->categories) > 0 ? $product->categories->first()->fields->keyBy('id') : [];
        } else {
            $this->result['status']           = 'error';
            $this->result['response']['code'] = 404;
            $this->message( 'danger', __( 'app.err_not_found' ) );
        }

        return $this->result();
    }


    /**
     * @OA\Post(
     *      path="/catalog/products/add",
     *      operationId="product-add",
     *      tags={"Products action"},
     *      summary="Add product partner",
     *      description="Return json",
     *      security={{"api_token_security":{}}},
     *
     *      @OA\Parameter(
     *          name="vendor_code",
     *          description="Vendor code (Article)",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="12534567F"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="price_origin",
     *          description="Price product",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="number",
     *              example="5999.99"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="quantity",
     *          description="Quantity product in storage",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer",
     *              example="99"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="weight",
     *          description="Weight product",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="number",
     *              example="125.15"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="weight",
     *          description="Weight product",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="number",
     *              example="125.15"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="categories[]",
     *          description="Categories product (use /catalog/categories/list)",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="array",
     *              @OA\Items(type="integer", example="12"),
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="uz_title",
     *          description="Title product (uz)",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="Title product (uz)"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="uz_slug",
     *          description="Slug product sef code (uz)",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="slug-item-product-uz"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="uz_preview_text",
     *          description="Preview text product (uz)",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="Preview text product (uz)"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="uz_detail_text",
     *          description="Detail text product (uz)",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="Detail product text (ru)"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="ru_title",
     *          description="Title product (ru)",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="Title prodcut item (ru)"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="ru_slug",
     *          description="Slug product sef code (ru)",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="slug-item-product-ru"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="ru_preview_text",
     *          description="Preview text product (ru)",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="Preview text lang (ru)"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="ru_detail_text",
     *          description="Detail text product (ru)",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="Detail text lang ru"
     *          )
     *      ),
     *     	@OA\RequestBody(
     *          required=true,
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="image[]",
     *                      description="Images product",
     *                      type="array",
     *                      @OA\Items(type="file", format="binary")
     *                   ),
     *               ),
     *           ),
     *       ),
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

    /*@ OA\RequestBody(
            required=true,
            description="Products body",
            @ OA\JsonContent(
                @ OA\Property(
                    property="products",
                    @ OA\Items(ref="#/components/schemas/products"),
                )
            )
          ),*/

    /**
     * Add product
     *
     * @param Request $request
     *
     * @return array|bool|false|string
     */
    public function add( Request $request ) {

        $user = Auth::user();
        if ( $user->can( 'add', Model::class ) ) {
            $requestFiltered = $request->all();

            foreach (LocaleHelper::languages() as $language){
                $code = $language->code . "_fields";
                unset($requestFiltered[$code]);
            }

            $fieldsAndRules = LocaleHelper::prepareFieldsAndRules( $requestFiltered, $this->localeValidatorRules, 'ru' );
            $rules = $this->validatorRules;

            $validator      = $this->validator( $requestFiltered, $rules, $fieldsAndRules['rules'] );
            if ( $validator->fails() ) {
                $this->result['status']             = 'error';
                $this->result['response']['errors'] = $validator->errors();
            } else {
                $partner = Partner::find($user->id);
                if($partnerSettings = $partner->settings) {

                    $product = new Model();
                    $product->user_id = $user->id;
                    $product->type = 'product';
                    $product->price = $request->price_origin;
                    $product->price_origin = $request->price_origin??0;
                    $product->price_discount  = $request->price_discount;
                    $product->weight = $request->weight??null;
                    $product->quantity = $request->quantity;
                    $product->vendor_code = $request->vendor_code??null;
                    $product->fields = $request->fields??null;
                    $product->save();

                    $product->categories()->sync($request->categories);

                    //Create product item locale(s)
                    foreach ($fieldsAndRules['fields'] as $code => $fields) {

                        $productLanguage = new CatalogProductLanguage();
                        $productLanguage->product_id = $product->id;
                        $productLanguage->language_code = $code;
                        $productLanguage->title = $fields['title'];
                        $productLanguage->preview_text = $fields['preview_text']??null;
                        $productLanguage->detail_text = $fields['detail_text']??null;
                        $productLanguage->slug = $fields['slug']??null;
                        $productLanguage->save();
                    }

                    $_arrProductFields = [];
                    foreach (LocaleHelper::languages() as $language) {
                        $code = $language->code;
                        $fieldCode = $code . "_fields";
                        if ($request->has($fieldCode)) {
                            $_arr = $request->get($fieldCode);
                            foreach ($_arr as $id => $value) {
                                if (!is_null($value))
                                    $_arrProductFields[$id][$code]['value'] = $value;
                            }
                        }
                    }

                    if (count($_arrProductFields) > 0) {
                        $product->fields = $_arrProductFields;
                        $product->save();
                    }

                    $params = [
                        'files' => $request->file(),
                        'element_id' => $product->id,
                        'model' => 'product',
                    ];
                    FileHelper::upload($params, [], true);

                    //Making preview

                    /*foreach ($product->images as $image) {
                        $previewName = 'preview_' . $image->name;
                        $storagePath = Storage::disk('local')->getAdapter()->getPathPrefix() . 'public/';
                        $previewPath = $storagePath . str_replace($image->name, $previewName, $image->path);
                        $preview = new ImageHelper($storagePath . $image->path);
                        $preview->resize($this->config['preview']['width'], $this->config['preview']['height']);
                        $preview->save($previewPath);
                    }*/


                    $this->result['status'] = 'success';
                    $this->message('success', __('billing/catalog.txt_created'));
                }else{
                    $this->result['status']           = 'error';
                    $this->result['response']['code'] = 403;
                    $this->message( 'danger', __( 'app.nds_not_fill' ) );
                }


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
     * @OA\Post(
     *      path="/catalog/products/modify",
     *      operationId="product-modify",
     *      tags={"Products action"},
     *      summary="Modify product partner",
     *      description="Return json",
     *      security={{"api_token_security":{}}},
     *
     *      @OA\Parameter(
     *          name="id",
     *          description="ID product",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="number",
     *              example="125"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="vendor_code",
     *          description="Vendor code (Article)",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="12534567F"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="price_origin",
     *          description="Price product",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="number",
     *              example="5999.99"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="quantity",
     *          description="Quantity product in storage",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer",
     *              example="99"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="weight",
     *          description="Weight product",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="number",
     *              example="125.15"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="weight",
     *          description="Weight product",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="number",
     *              example="125.15"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="categories[]",
     *          description="Categories product (use /catalog/categories/list)",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="array",
     *              @OA\Items(type="integer", example="12"),
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="uz_title",
     *          description="Title product (uz)",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="Title product (uz)"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="uz_slug",
     *          description="Slug product sef code (uz)",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="slug-item-product-uz"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="uz_preview_text",
     *          description="Preview text product (uz)",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="Preview text product (uz)"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="uz_detail_text",
     *          description="Detail text product (uz)",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="Detail product text (ru)"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="ru_title",
     *          description="Title product (ru)",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="Title prodcut item (ru)"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="ru_slug",
     *          description="Slug product sef code (ru)",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="slug-item-product-ru"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="ru_preview_text",
     *          description="Preview text product (ru)",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="Preview text lang (ru)"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="ru_detail_text",
     *          description="Detail text product (ru)",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="Detail text lang ru"
     *          )
     *      ),
     *     	@OA\RequestBody(
     *          required=true,
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="image[]",
     *                      description="Images product",
     *                      type="array",
     *                      @OA\Items(type="file", format="binary")
     *                   ),
     *               ),
     *           ),
     *       ),
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
     * Modify catalog
     *
     * @param Model $product
     * @param Request $request
     *
     * @return array|bool|false|string
     */
    public function modify(Request $request, Model $product = null ) {

        if ($product == null) {
            if (isset($request->product_id) && is_numeric($request->product_id))
                $product = Model::find($request->product_id);
        }

        $user = Auth::user();
        if ( $user->can( 'modify', $product ) ) {
            $requestFiltered = $request->all();

            foreach (LocaleHelper::languages() as $language){
                $code = $language->code . "_fields";
                unset($requestFiltered[$code]);
            }

            $fieldsAndRules = LocaleHelper::prepareFieldsAndRules( $requestFiltered, $this->localeValidatorRules, 'ru' );
            $validator      = $this->validator( $requestFiltered, $this->validatorRules, $fieldsAndRules['rules'] );
            if ( $validator->fails() ) {
                $this->result['status']             = 'error';
                $this->result['response']['errors'] = $validator->errors();
            } else {

                $partner = Partner::find($user->id);
                $partnerSettings = $partner->settings;

                $product->quantity      = $request->quantity;
                $product->price         = $request->price_origin;
                $product->price_origin  = $request->price_origin;
                $product->price_discount  = $request->price_discount;
                $product->weight        = $request->weight;
                $product->vendor_code   = $request->vendor_code;
                $product->save();
                $product->categories()->sync( $request->categories );

                foreach ($product->languages as $language){
                    $language->delete();
                }

                //Create product item locale(s)

                foreach ( $fieldsAndRules['fields'] as $code => $fields ) {

                    $productLanguage = $product->language($code)->first()??new CatalogProductLanguage();

                    $productLanguage->product_id    = $product->id;
                    $productLanguage->language_code = $code;
                    $productLanguage->title         = $fields['title'];
                    $productLanguage->preview_text  = $fields['preview_text']??null;;
                    $productLanguage->detail_text   = $fields['detail_text']??null;
                    $productLanguage->slug          = $fields['slug']??null;
                    $productLanguage->save();

                }

                $_arrProductFields = [];
                foreach (LocaleHelper::languages() as $language){
                    $code = $language->code;
                    $fieldCode = $code . "_fields";
                    if($request->has($fieldCode)){
                        $_arr = $request->get($fieldCode);
                        foreach ($_arr as $id => $value) {
                            if(!is_null($value))
                                $_arrProductFields[$id][$code]['value'] = $value;
                        }
                    }
                }


                if(count($_arrProductFields) > 0)
                    $product->fields = $_arrProductFields;
                else
                    $product->fields = null;

                $product->save();

                //Deleting old files
                $filesToDelete = ($request->files_to_delete != '') ? explode(',', $request->files_to_delete) : [];

                $params = [
                    'files'      => $request->file(),
                    'element_id' => $product->id,
                    'model'      => 'product',
                ];
                FileHelper::upload( $params, $filesToDelete, true );

                //Making preview

                foreach ( $product->images as $image ) {
                    $previewName = 'preview_' . $image->name;
                    $storagePath = Storage::disk( 'local' )->getAdapter()->getPathPrefix() . 'public/';
                    $previewPath = $storagePath . str_replace( $image->name, $previewName, $image->path );
                    $preview     = new ImageHelper( $storagePath . $image->path );
                    $preview->resize( $this->config['preview']['width'], $this->config['preview']['height'] );
                    $preview->save( $previewPath );
                }


                $this->result['status'] = 'success';
                $this->message( 'success', __( 'billing/catalog.txt_updated' ) );
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
     * @OA\Get(
     *      path="/catalog/products/delete",
     *      operationId="product-delete",
     *      tags={"Products action"},
     *      summary="Delete products by ID",
     *      description="Return json",
     *      security={{"api_token_security":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          description="ID deleted product",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="number",
     *              example="121211"
     *          )
     *      ),
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
     * Delete product
     *
     * @param Model $product
     *
     * @return array|bool|false|string
     * @throws Exception
     */
    public function delete( Model $product ) {

        $user = Auth::user();

        if ( $product ) {
            if ( $user->can( 'delete', $product ) ) {

                $images = $product->images()->pluck( 'id' )->toArray();
                FileHelper::delete( $images );
                $product->languages()->delete();
                $product->categories()->detach();
                $product->delete();

                $this->result['status'] = 'success';
                $this->message( 'success', __( 'billing/catalog.txt_deleted' ) );
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


    /**
     * @param int $id
     * @param string $operation
     * @param int $quantity
     */
    public static function quantity(int $id, string $operation, int $quantity = 1){
        $product = Model::find($id);
        if($product){
            switch($operation) {
                case 'increment':
                    $product->quantity -= $quantity;
                    break;
                case 'decrement':
                    $product->quantity += $quantity;
                    break;
            }
            $product->save();
        }
    }


    /**
     * @param Model $product
     * @return \Illuminate\Support\Collection
     */
    public function relatedProducts(Model $product){
        $products = collect([]);
        if($categories = $product->categories()->get()){
            foreach ($categories as $category){
                if($cProducts = $category->products()->get()){
                    foreach ($cProducts as $cProduct) {
                        if($product->id !== $cProduct->id){
                            $products->push($cProduct->id);
                        }
                    }
                }
            }
        }

        return $products;
    }

    // экспорт категорий
    public function categoryExport(){

        $catalog_categories = CatalogCategory::with('language')->get();

        $file_catalog = "id;parent_id;title;\n";
        $a = [];
        foreach ($catalog_categories as $cat){
            if(in_array($cat->id,$a)) continue;
            $a[] = $cat->id;
            $file_catalog .= $cat->id . ';' . $cat->parent_id . ';' . $cat->language->title . ";\n";
        }

        $filename = 'catalog.csv';
        $file_catalog = iconv('utf-8','windows-1251//TRANSLIT',$file_catalog);

        file_put_contents($filename,$file_catalog);

        if(file_exists($filename)) {
            header( 'Content-type: '. mime_content_type($filename));
            header( 'Content-Disposition: attachment; filename=' . $filename );
            readfile($filename);
            exit;
        }else{
            return redirect('404');
        }
    }

    // импорт товаров
    public function import(Request $request){

        Log::info('import products');

        $data = [];

        if($request->isMethod('POST')){



            $user_id = Auth::user()->id;

            Log::info('import file ' . $user_id);

            $file_path = storage_path('app/public/company/' . $user_id) .  '/products.tmp';
            foreach ($request->file() as $file) {
                $file->move(storage_path('app/public/company/' . $user_id),  'products.tmp'); //time().'_'.$file->getClientOriginalName());
            }

            $errors = [];

            $data = file_get_contents( $file_path );

            //$data = iconv( iconv_get_encoding($data),'utf-8',$data);
            $data = mb_convert_encoding( $data,'utf-8');

            $file_data = explode("\r\n",$data); // file()

            Log::info('file_data');
            Log::info($file_data);

            if(!is_array($file_data)) $errors[]= 'Неверный формат файла!';

            $sync = false; // синхронизация импортированных данных в БД

            if(is_array($file_data)) {
                $header = explode(';',$file_data[0]);
                if(is_array($header)) {
                    if (count($header) < 6 || count($header) > 8) $errors[] = 'Неверный формат файла! Неверное количеств столбцов! ' . "\n" . $file_data[0];
                    if($header[0]=='id') $sync = true; // передан id товара

                    $header = array_flip($header);

                    Log::info($header);

                    /* $columns = [];
                    foreach ($header as $id=>$head){
                        $columns[$head] = $id;
                    } */

                }else{
                    $errors[]= 'Неверный формат файла! Нет данных!';
                }
            }

            if(is_array($file_data) && count($file_data)<=1) $errors[]= 'Неверный формат файла! Нет данных!';

            Log::info('file_data');
            Log::info($file_data);

            if(count($errors)==0) {

                $partner = Partner::find($user_id);
                $partnerSettings = $partner->settings ?? false;

                unset($file_data[0]); // удаляем заголовок

                if($partnerSettings) {

                    $count = 0;

                    foreach ($file_data as $_product) {

                        $product = explode(';', $_product);

                        if( is_array($product) ) {

                            if(count($product)<=1) continue;

                            if (count($product) < 6 || count($product) > 8) {
                                $errors[] = __('billing/catalog.unknown_format') . ' [' . $_product . ']';
                                continue;
                            }

                        }else{
                            $errors[]= __('billing/catalog.product_not_found') . ' [' . $_product . ']';
                            continue;
                        }

                        $id = $sync ? $product[$header['id']] : null; //$product[0]; // sku
                        $vendor_code = $product[$header['sku']]; //$product[0]; // sku
                        $product_title = $product[$header['title']]; //$product[1];
                        $price = (double) $product[$header['price']]; //$product[2];
                        $price_discount = (double) $product[$header['discount']]; // $product[3];
                        $quantity = (int)$product[$header['quantity']]; //$product[4];
                        $category_id = (int)$product[$header['category_id']]; //$product[5];

                        $_errors = '';
                        if($product_title=='') $_errors .= 'Product title not found! ';
                        if($price<=0) $_errors .= 'Price not set! ';
                        if($quantity<=0) $_errors .= 'Quantity not set! ';
                        if($category_id<=0) $_errors .= 'Category_id not set! ';

                        if($_errors!=''){ // если есть ошибки пропускаем
                             $errors[] = $_errors . ' for: ' . $_product;
                             continue;
                        }

                        if($sync){ // обновляем существующий товар
                            // создание товара
                            if(!$product = CatalogProduct::where('id',$id)->where('user_id',$user_id)->first()){
                                // создание товара
                                $product = new Model();
                                $product->user_id = $user_id;
                                $errors[] = 'Товар не найден ' . $id;
                            }

                        }else{ // создаем товар
                            // создание товара
                            $product = new Model();
                            $product->user_id = $user_id;

                        }

                        $product->type = 'product';
                        $product->price = $price;
                        $product->price_origin = $price;
                        $product->quantity = $quantity;
                        $product->price_discount = $price_discount;
                        $product->vendor_code = $vendor_code;
                        $product->save();

                        if($sync){

                            // поиск связи товара с категорией по id товара, может измениться категория
                            if(!$CatalogCategoryProduct = CatalogCategoryProduct::/*where('catalog_category_id',$category_id)->*/where('catalog_product_id',$id)->first()){
                                $CatalogCategoryProduct = new CatalogCategoryProduct();
                            }

                        }else {

                            // связь товара с категорией
                            $CatalogCategoryProduct = new CatalogCategoryProduct();
                        }

                        $CatalogCategoryProduct->catalog_product_id = $product->id;
                        $CatalogCategoryProduct->catalog_category_id = $category_id;
                        $CatalogCategoryProduct->save();

                        foreach (['ru','uz'] as $code ) {
                            if($sync){
                                if(!$productLanguage = CatalogProductLanguage::where('product_id',$id)->where('language_code',$code)->first()){
                                    $productLanguage = new CatalogProductLanguage();
                                }
                            }else {
                                $productLanguage = new CatalogProductLanguage();
                            }
                            $productLanguage->product_id = $product->id;
                            $productLanguage->language_code = $code;
                            $productLanguage->title = $product_title;
                            $productLanguage->save();
                        }
                        $count++;
                    }

                    return ['status' => 'success', 'data'=>['count'=>$count], 'errors'=>$errors];

                }else { // $partnerSettings
                    $errors[] = 'PartnerSettings for user_id '. Auth::user()->id.' not found!';
                }

            }
            return ['status' => 'error','errors'=>$errors];

        }

        return view( 'billing.catalog.product.import',compact('data') );

    }

    // экспорт товаров
    public function export(){

        $user_id = Auth::user()->id;

        $products = CatalogProduct::with(['languages','category'])->where('user_id',$user_id )->where('type','product')->get();

        $file_catalog = "id;sku;title;price;discount;quantity;category_id;category;\n";

        $a = [];
        foreach ($products as $item){
            if(in_array($item->id,$a)) continue;
            $a[] = $item->id;
            $file_catalog .= $item->id . ';' . $item->vendor_code . ';';

            foreach ($item->languages as $k=>$lang){
                if( $lang->language_code=='ru' ) {
                    $file_catalog .= $lang->title . ';';
                    break;
                }
            }

            $file_catalog .= $item->price . ';' . $item->price_discount . ';' . $item->quantity . ";";
            foreach ($item->category as $k=>$cat){
                if( $cat->language_code=='ru' ) {
                    $file_catalog .= $cat->category_id . ';' . $cat->title . ';';
                    break;
                }
            }

            $file_catalog .=  "\n";

        }

        $filename = 'products.csv';
        //$file_catalog =  iconv('utf-8','windows-1251//TRANSLIT',$file_catalog);
        $file_catalog =  mb_convert_encoding( $file_catalog,'windows-1251');

        file_put_contents($filename,$file_catalog);

        if(file_exists($filename)) {
            header( 'Content-type: '. mime_content_type($filename));
            header( 'Content-Disposition: attachment; filename=' . $filename );
            readfile($filename);
            exit;
        }else{
            return redirect('404');
        }

    }


}
