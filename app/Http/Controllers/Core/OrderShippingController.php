<?php

namespace App\Http\Controllers\Core;

use App\Models\Shipping as Model;
use Illuminate\Support\Facades\Request;

class OrderShippingController extends CoreController
{
    private $shippingMethod;


    /**
     * PartnerController constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->model = app( Model::class );
    }

    /**
     * @param $methodName
     */
    private function set($methodName){
        $methodName = 'App\Http\Controllers\Core\Shipping\\'.$methodName.'Controller';
        $this->shippingMethod = (class_exists($methodName)?new $methodName():null);
    }

    /**
     * @param null $method
     * @param array $params
     * @return array|false|string
     */
    public function check($method = null, $params = []){
        $this->set($method);

        if($this->shippingMethod && $this->shippingMethod != '') {
            $this->result = $this->shippingMethod->check($params);
        }else{
            $this->result['status'] = 'error';
            $this->message('danger',__('shipping.error_shipping_method_not_found'));
        }

        return $this->result();

    }


    /**
     * @param null $method
     * @param array $params
     * @return array|false|string
     */
    public function calculate($method = null, $params = []){

        $this->set($method);

        $this->result['data'] = [
            'total' => 0
        ];


        if($this->shippingMethod && $this->shippingMethod != '') {
            $this->result['status'] = 'success';
            $this->result['data'] = $this->shippingMethod->calculate($params);
        }else{
            $this->result['status'] = 'error';
            $this->message('danger',__('shipping.error_shipping_method_not_found'));
        }

        return $this->result();
    }

}
