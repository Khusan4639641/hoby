<?php

namespace App\Http\Controllers\Core\Shipping;

use Illuminate\Http\Request;

class CourierEmuController
{
    protected $tariff;

    public function __construct()
    {
        //Тариф
        $this->tariff['city'] = [
            3 => [
                'price' => 15000,
                'price_nds' => 17250,
            ],
            6 => [
                'price' => 20000,
                'price_nds' => 23000,
            ],
            9 => [
                'price' => 25000,
                'price_nds' => 28750,
            ],
            12 => [
                'price' => 30000,
                'price_nds' => 34500,
            ],
            15 => [
                'price' => 35000,
                'price_nds' => 40250,
            ],
            18 => [
                'price' => 40000,
                'price_nds' => 46000,
            ],
            21 => [
                'price' => 45000,
                'price_nds' => 51750,
            ]
        ];
        $this->tariff['region'] = [
            1 => [
                'price' => 30000,
                'price_nds' => 34500,
            ],
            3 => [
                'price' => 35000,
                'price_nds' => 40250,
            ],
            6 => [
                'price' => 40000,
                'price_nds' => 46000,
            ],
            9 => [
                'price' => 45000,
                'price_nds' => 51750,
            ],
            12 => [
                'price' => 50000,
                'price_nds' => 57500,
            ],
            15 => [
                'price' => 55000,
                'price_nds' => 63250,
            ],
            18 => [
                'price' => 60000,
                'price_nds' => 69000,
            ],
            21 => [
                'price' => 65000,
                'price_nds' => 74750,
            ]
        ];
        $this->tariff['over']['price'] = 5750;
        $this->tariff['over']['weight'] = 3000;
    }


    /**
     * @param array $params
     * @return float[]|int[]
     */
    public function calculate($params = []){

        //Тип тарифа
        $type = ($params['address']['region'] == 1726?'city': 'region');

        //Вес
        $weight = 0;
        foreach($params['products'] as $product)
            $weight += $product['weight'];
        if($weight === 0) $weight = 1000;
        $weight = $weight;


        $total = 0;
        foreach ($this->tariff[$type] as $w => $price)
            if($weight/1000 <= $w && $total === 0) $total = $price['price_nds'];

        //Если перевес
        if($total ===0) {
            $total = $this->tariff[$type][21];
            $delta = ceil(($weight/1000 - 21)/$this->tariff['over']['weight']);

            $total += $delta * $this->tariff['over']['price'];
        }


        $result = [
            'total' => $total
        ];

        return $result;
    }


    /**
     * @param array $params
     * @return array
     */
    public function check($params = []){
        $result = [];

        if(isset($params['address']) && $params['address']['region'] != "" && $params['address']['area'] != "" && $params['address']['address'] != "" )
            $result['status'] = 'success';
        else {
            $result['status'] = 'error';
            $result['response']['message'][] = [
                'type'  => 'danger',
                'text'  => __('shipping/courieremu.error_address_null')
            ];
        }

        return $result;
    }
}
