<?php

namespace Mobile\V3\Partners;

use Tests\TestCase;

class ContractsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
//        $token = "898acbf3de56bda4e2afe2be41b3add3"; //simple partner token
        $token = "fc79cad0e8385334dab0d756731d4ed0"; //general partner token
        $this->withHeaders([
            "Content-Type" => "application/json",
            "Authorization" => "Bearer {$token}"
        ]);
    }


    public function test_auth()
    {
        $result = $this->postJson('api/v3/auth', [
            'partner_id' => '215351',
            'password' => 'GKbpD4fddxBV',
        ]);

        $result->dump();
    }


    public function test_get_order_list()
    {
        $result = $this->get('api/v3/order/list?cancellation_status=1&status[]=0&status[]=5&status[]=1');
        $result->dump();

        $result->assertStatus(200);
    }

    public function test_order_calculate() {
        $result = $this->postJson('api/v3/order/calculate', [
            "user_id" => 237745,
            "period" => 12,
            "products" => [
                [
                    "category" => 3,
                    "amount" => "1",
                    "name" => "Product 1",
//                    "imei" => "213421341234134",
                    "price" => 1000
                ],
                [
                    "category" => 2,
                    "amount" => "1",
                    "name" => "Product 2",
//                    "imei" => "213421341234134",
                    "price" => 1000
                ]
            ]
        ]);
        $result->dump();
    }

    public function test_order_add()
    {
        $result = $this->postJson('api/v3/orders/add', [
            "user_id" => 237745,
            "period" => 12,
            "products" => [
                [
                    "unit_id" => 1,
                    "category" => 3,
                    "amount" => "1",
                    "name" => "Product 1",
                    "imei" => "213421341234134",
                    "price" => 10000
                ],
                [
                    "unit_id" => 1,
                    "category" => 2,
                    "amount" => "1",
                    "name" => "Product 2",
//                    "imei" => "213421341234134",
                    "price" => 50000
                ]
            ]
        ]);
        $result->dump();
    }

    public function test_send_cancellation_request()
    {

        $result = $this->postJson('api/v3/contracts/create-cancel-request', [
            'contract_id' => '1235547',
            'reason' => 'Вот так вот захотелось'
        ]);

        $result->dump();

        $result->assertStatus(200);
    }

    public function test_reject_cancellation_request()
    {

        $result = $this->postJson('api/v3/contracts/reject-cancel-request', [
            'contract_id' => '1235547',
        ]);

        $result->dump();
    }

    public function test_send_sms_code_from_cancellation_request()
    {

        $result = $this->postJson('api/v3/contracts/send-cancel-sms', [
            'contract_id' => '1235547',
        ]);

        $result->dump();
    }

    public function test_get_phones_count()
    {
        $result = $this->getJson('api/v3/buyer/phones-count?buyer_id=256069&category_id=1331');
        $result->dump();

        $result->assertStatus(200);
    }

    public function test_get_partner_detail_info()
    {
        $result = $this->getJson('api/v3/partner/detail');
        $result->dump();

        $result->assertStatus(200);
    }
}
