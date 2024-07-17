<?php

namespace App\Http\Controllers\Core;

use Exception;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

use App\Models\ReportFile as Model;

class ReportFileController extends CoreController
{
    private $config;

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
    /*public function filter($params = []) {

        if (isset($params['search'])) {
            $id = Model::where('name', 'like', '%'.$params['search'].'%')->pluck('id')->toArray();
            $params['id'] = $id ?? [];
        }

        return parent::filter($params);
    }*/

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
     * Delete region
     *
     * @param Model $report_file
     * @return array|bool|false|string
     * @throws Exception
     */
    public function delete(Model $report_file){

        $user = Auth::user();

        if ($report_file) {

            //if ($user->can('delete', $region)) {
            if (true) {

                $report_file->delete();

                $this->result['status'] = 'success';
                $this->message('success', __('panel/report_files.txt_deleted'));

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
