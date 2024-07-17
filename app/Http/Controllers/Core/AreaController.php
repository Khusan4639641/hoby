<?php


namespace App\Http\Controllers\Core;

use App\Models\Area as Model;

class AreaController extends CoreController {

    public function __construct()
    {
        parent::__construct();
        $this->model = app(Model::class);

        //Eager load
        $this->loadWith = [];
    }

    /**
     * Список регионов
     * @param array $params
     * @return mixed
     */
    public function list( array $params = []) {
        //Get data from REQUEST if api_token is set
        $request = request()->all();
        if ( isset( $request['api_token'] ))
            $params = $request;

        //Filter elements
        $filter = $this->filter($params);

        //Collect data
        $this->result['response']['total']  = $filter['total'];
        $this->result['status']             = 'success';
        $this->result['data'] = $filter['result'];

        //Return data
        return $this->result();
    }

}
