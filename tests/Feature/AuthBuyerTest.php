<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class AuthBuyerTest extends TestCase
{
    /**
     @test
     */
    public function user_can_send_request_to_login()
    {
        $response = $this->post('/api/v3/login/send-sms-code',[
            'phone' => '998900626204',
        ]);
        $response->assertStatus(200)->assertJson(['status' => 'success']);
    }
    
    /**
     @test
     */
    public function user_can_not_send_request_to_login_with_invalid_credentials()
    {
        $this->withoutExceptionHandling();
        $response = $this->post('/api/v3/login/send-sms-code',[
            'phone' => '9989006262df',
        ]);
        $response->assertStatus(400)->assertJson(['status' => 'error']);
    }

    /**
     @test
     */
    public function user_can_not_auth_with_invalid_credentials()
    {
        //Send sms code
        $this->post('/api/v3/login/send-sms-code',[
            'phone' => '998900626204',
        ])->getContent();
        //try with invalid code
        $response = $this->post('api/v3/login/auth',[
            'phone' => '998900626204',
            'code' => '1234',
        ]);
        $response->assertStatus(400);
    }
}
