<?php


namespace App\Http\Controllers\Core;


use App\Models\ContractPaymentsSchedule as Model;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;


class BuyerPaymentController extends CoreController {
    /**
     * Fields validator
     *
     * @param array $data
     *
     * @return Validator
     */
    private $validatorRules = [ ];


    /**
     * BuyerPaymentController constructor.
     */
    public function __construct() {
        parent::__construct();

        $this->model = app( Model::class );
        $this->config = Config::get( 'test.preview' );

        $this->loadWith = ['contract'];
    }


    /**
     * @param array $params
     * @return array
     */
    public function filter($params = []){

        $params['user_id'] = Auth::user()->id;

        return parent::filter($params);
    }


    /**
     * @param array $payments
     * @return int
     */
    public function totalPaymentInMonth($payments = []) {
        $total = 0;

        foreach ($payments as $payment){
            if(date('m') == date('m', strtotime($payment->payment_date))){
                $total += $payment->total;
            }
        }
        return $total;
    }
}
