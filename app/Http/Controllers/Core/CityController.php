<?php


namespace App\Http\Controllers\Core;

use App\Models\City as Model;

class CityController extends CoreController {

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


        if($params['areaid'] && $params['regionid']) {
            $filter = $this->filter($params);
            //Collect data
            $this->result['response']['total']  = $filter['total'];
            $this->result['status']             = 'success';
            $this->result['data'] = $filter['result'];
        } else {
            $this->result['response']['total']  = 0;
            $this->result['status']             = 'success';
            $this->result['data'] = collect([]);
        }

        //Return data
        return $this->result();
    }

}
