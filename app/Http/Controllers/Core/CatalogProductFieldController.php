<?php

namespace App\Http\Controllers\Core;

use App\Helpers\LocaleHelper;
use App\Models\CatalogProductField;
use App\Models\CatalogProductField as Model;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class CatalogProductFieldController extends CoreController {

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
    ];

    public function __construct() {
        parent::__construct();
        $this->model = app( Model::class );
        $this->config = Config::get( 'test.preview' );
    }


    /**
     * Fields list
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
            if($user)
                $item->permissions = $this->permissions($item, $user);
        }

        //Collect data
        $this->result['response']['total'] = $filter['total'];
        $this->result['status'] = 'success';

        if (isset($params['list_type']) && $params['list_type'] == 'data_tables') {
            $filter['result'] = $this->formatDataTables($filter['result']);
        }


        $this->result['data'] = $filter['result'];


        //Return data
        return $this->result();
    }

    /**
     * @param $id
     * @param array $with
     * @return Builder|\Illuminate\Database\Eloquent\Model|object
     */
    public function single( $id, $with = [] ) {
        $single = parent::single( $id, array_merge($this->loadWith, $with) );

        $json = $single->name;
        $_arrName = json_decode($json,true);

        //Locales
        $locales = [];
        $languages = LocaleHelper::languages();
        foreach ($languages as $language) {
            if(isset($_arrName[$language->code])) {
                $locales[$language->code]['title'] = $_arrName[$language->code];
            }
        }
        $single->locales = $locales;
        return $single;
    }



    /**
     * Detail field
     *
     * @param $id
     *
     * @return array|bool|false|string
     */
    public function detail($id) {
        $field = $this->single($id, []);

        if ( $field ) {
            $this->result['status']          = 'success';
            $this->result['data'] = $field;
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
            $title = "name->" . app()->getLocale();
            $fieldID = CatalogProductField::where($title, 'like', '%'.$params['title__like'].'%')->pluck('id')->toArray();
            $params['id'] = $fieldID??[];
        }
        return parent::filter( $params );
    }

    /**
     * Add field
     *
     * @param Request $request
     *
     * @return array|bool|false|string
     */
    public function add( Request $request ) {
        $user = Auth::user();
        if ( $user->can( 'add', Model::class ) ) {
            $fieldsAndRules = LocaleHelper::prepareFieldsAndRules( $request->all(), $this->localeValidatorRules, Config::get('app.fallback_locale') );
            $rules = array_merge($this->validatorRules, []);

            $validator      = $this->validator( $request->all(), $rules, $fieldsAndRules['rules'] );

            if ( $validator->fails() ) {
                $this->result['status']             = 'error';
                $this->result['response']['errors'] = $validator->errors();
            } else {

                $field           = new Model();
                $_arr = [];

                foreach ($fieldsAndRules['fields'] as $code => $fields) {
                    foreach ($fields as $column => $value){
                        $_arr[$column][$code] = $value;
                    }
                }

                $field->name            = json_encode($_arr['title']);
                $field->save();


                $this->result['status'] = 'success';
                $this->message( 'success', __( 'panel/catalog.field.txt_created' ) );
            }

            //Success: field item created

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
     * @param Model $field
     * @return array|bool|false|string
     */
    public function modify(Request $request, Model $field ) {

        $user = Auth::user();
        if ( $user->can( 'modify', $field ) ) {
            $fieldsAndRules = LocaleHelper::prepareFieldsAndRules( $request->all(), $this->localeValidatorRules, Config::get('app.fallback_locale') );
            $validator      = $this->validator( $request->all(), $this->validatorRules, $fieldsAndRules['rules'] );

            if ( $validator->fails() ) {
                $this->result['status']             = 'error';
                $this->result['response']['errors'] = $validator->errors();
            } else {

                $_arr = [];

                foreach ($fieldsAndRules['fields'] as $code => $fields) {
                    foreach ($fields as $column => $value){
                        $_arr[$column][$code] = $value;
                    }
                }

                $field->name            = json_encode($_arr['title']);
                $field->save();

                $this->result['status'] = 'success';
                $this->message( 'success', __( 'panel/catalog.field.txt_updated' ) );
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
     * @param Model $field
     * @return array|bool|false|string
     */
    public function delete( Model $field ) {

        $user = Auth::user();

        if ( $field ) {
            if ( $user->can( 'delete', $field ) ) {

                $field->categories()->detach();
                $field->delete();

                $this->result['status'] = 'success';
                $this->message( 'success', __( 'panel/catalog.field.txt_deleted' ) );

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

}
