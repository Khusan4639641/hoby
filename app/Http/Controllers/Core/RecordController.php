<?php


namespace App\Http\Controllers\Core;

use App\Models\Record as Model;
use App\Models\User;
use App\Models\Buyer;
use App\Models\BuyerSetting;
use App\Models\Record;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class RecordController extends CoreController{




    /**
     * Controller constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->model = app(Model::class);

        //Eager load
        /*$this->loadWith = [
            'buyer',

        ];*/

    }


    /**
     * Списok комментариев от kyc по договорам клиента
     * @param array $params
     * @return mixed
     */
    public function list($params = [])
    {
        $user = Auth::user();
        $request = request()->all();
        if (isset($request['api_token']))
            $params = $request;
        unset($params['api_token']);

         //Filter elements
        $filter = $this->filter($params);

        //Collect data
        $this->result['response']['total'] = $filter['total'];
        $this->result['status'] = 'success';

        //Format data
        if (isset($params['list_type']) && $params['list_type'] == 'data_tables') {
            $filter['result'] = $this->formatDataTables($filter['result']);
        }
        //Collect data

        $this->result['data'] = $filter['result'];

        //Return data
        return $this->result();

    }


    /**
     * @param array $params
     * @return array
     */
    public function filter($params = []){


        if(isset($params['params']))
            return parent::multiFilter($params);

        return parent::filter($params);

    }

    /**
     * @param array $params
     * @return array
     */
    public function add(Request $request){

        if($user = Auth::user()){
            $record = new Record();
            $record->user_id = $request->user_id;
            $record->contract_id = $request->contract_id;
            $record->kyc_id = $user->id;
            $record->text = $request->text;

            if($record->save()){
                $result['status'] = 'success';
            }else{
                $result['status'] = 'error';
            }

        }else{
            $result['status'] = 'error';
        }

        return $result;

    }





}
