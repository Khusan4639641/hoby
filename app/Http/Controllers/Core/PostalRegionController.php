<?php

namespace App\Http\Controllers\Core;

use Exception;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

use App\Models\PostalRegion as Model;

class PostalRegionController extends CoreController
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
        'external_id' => ['required', 'integer'],
        'katm_region' => ['nullable', 'integer']
    ];

    /**
     * PostalRegionController constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->model = app(Model::class);

        //Config
        $this->config =  Config::get('test.preview');
    }


    /**
     * @param array $params
     * @return array
     */
    public function filter($params = []) {

        if (isset($params['search'])) {
            $id = Model::where('name', 'like', '%'.$params['search'].'%')->pluck('id')->toArray();
            $params['id'] = $id ?? [];
        }

        return parent::filter($params);
    }

    /**
     * Get items ids list
     *
     * @param array $params
     * @return array|bool|false|string
     */
    public function list(array $params = []) {

        $user = Auth::user();

        $request = request()->all();

        if (isset($request['api_token'])) {
            $params = $request;
        }

        unset($params['api_token']);

        // Filter elements
        $filter = $this->filter($params);

        // Collect data
        $this->result['response']['total']  = $filter['total'];
        $this->result['status']             = 'success';

        // Format data
        if ( isset( $params['list_type'] ) && $params['list_type'] == 'data_tables' ) {
            $filter['result'] = $this->formatDataTables($filter['result']);
        }

        $this->result['data'] = $filter['result'];

        return $this->result();
    }

    /**
     * Add region
     *
     * @param Request $request
     * @return array|bool|false|string
     */
    public function add(Request $request) {

        $user = Auth::user();

        //if ($user->can('add', Model::class)) {
        if (true) {

            $validator = $this->validator($request->all(), $this->validatorRules);

            if ($validator->fails()) {

                // Error: validation error
                $this->result['status'] = 'error';
                $this->result['response']['errors'] = $validator->errors();

            } else {

                // Create region
                $region = new Model();
                $region->name = $request->name;
                $region->external_id = $request->external_id;
                $region->katm_region = $request->katm_region;
                $region->save();

                // Success: Region created
                $this->result['status'] = 'success';
                $this->message('success', __('panel/postal_regions.txt_created'));
            }

        } else {

            // Error: Access denied
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 403;
            $this->message( 'danger', __('app.err_access_denied'));
        }

        return $this->result();
    }

    /**
     * Detail region
     *
     * @param int $id
     * @return array|bool|false|string
     */
    public function detail(int $id) {

        $region = $this->single($id);

        $user = Auth::user();

        if ($region) {

            $this->result['status'] = 'success';
            $this->result['data'] = $region;

        } else {

            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message( 'danger', __( 'app.err_not_found' ) );
        }

        return $this->result();
    }

    /**
     * Modify region
     *
     * @param Request $request
     * @return array|bool|false|string
     */
    public function modify(Request $request) {

        $user = Auth::user();
        $region = Model::find($request->id);

        if ($region) {

            //if ($user->can('modify', $region)) {
            if (true) {

                $validator = $this->validator($request->all(), $this->validatorRules);

                if ($validator->fails()) {

                    //Error: validation error
                    $this->result['status'] = 'error';
                    $this->result['response']['errors'] = $validator->errors();

                } else {

                    // Update region
                    $region->name = $request->name;
                    $region->external_id = $request->external_id;
                    $region->katm_region = $request->katm_region;
                    $region->save();

                    // Success: Region updated
                    $this->result['status'] = 'success';
                    $this->message('success', __('panel/postal_regions.txt_updated'));
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
            $this->message( 'danger', __( 'app.err_not_found' ) );
        }

        return $this->result();
    }

    /**
     * Delete region
     *
     * @param Model $region
     * @return array|bool|false|string
     * @throws Exception
     */
    public function delete(Model $region){

        $user = Auth::user();

        if ($region) {

            //if ($user->can('delete', $region)) {
            if (true) {

                $region->delete();

                $this->result['status'] = 'success';
                $this->message('success', __('panel/postal_regions.txt_deleted'));

            } else {

                $this->result['status'] = 'error';
                $this->message('danger', __('app.err_access_denied'));
            }

        } else {

            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message( 'danger', __( 'app.err_not_found' ) );
        }

        return $this->result();
    }
}
