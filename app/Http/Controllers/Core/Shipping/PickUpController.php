<?php

namespace App\Http\Controllers\Core\Shipping;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PickUpController extends Controller
{
    //
    public function __construct(){

    }

    /**
     * @param array $params
     * @return int
     */
    public function calculate($params = []){

        $result = [
            'total' => 0
        ];

        return $result;
    }


    public function check(){
        $result = [
            'status' => 'success'
        ];


        return $result;
    }
}
