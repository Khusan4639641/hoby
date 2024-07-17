<?php

namespace Tests\Feature;

use App\Models\Buyer;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use DB;
use Illuminate\Http\UploadedFile;

class PartnersV3ApiTest extends TestCase
{
    private $phone;
    private $partner_id;
    private $buyer_id;
    private $password;
    private $endpoint;

    public function setUp() :void
    {
        parent::setUp();
        $this->phone = '998900626204';
        $this->partner_id = 215357;
        $this->buyer_id = 372711;
        $this->password = "zJzjU4vAIhIQ";
        $this->endpoint = "/api/v3/";
    }

    private function getBadResponseExample():array
    {
        return [
            'status',
            'error' => [
                '*' => [
                    'text'
                ]
            ],
            'data'
        ];
    }

    //get token
    private function getToken()
    {
        $response = $this->post($this->endpoint.'auth',[
            'password' => $this->password,
            'partner_id' => $this->partner_id,
        ]);
        $data = json_decode($response->getContent(),true);
        return $data['data']['api_token'];
    }

    /**
     @test
     */
    public function partner_auth()
    {
        $response = $this->post($this->endpoint.'auth',[
            'password' => $this->password,
            'partner_id' => $this->partner_id,
        ]);
        $response->assertStatus(200)->assertJson(['status' => 'success']);
    }
    
    /**
     @test
     * Метод отправка четырехзначного кода смс на номер телефона для авторизации (SMS code user by phone)
     */
    public function partner_send_sms_to_buyer_phone_to_login()
    {
        $token = $this->getToken();
        $response = $this->withHeaders([
                        "Authorization" => "Bearer {$token}"
                    ])
                    ->post($this->endpoint.'partner/buyers/send-sms-code',[
                        'phone' => $this->phone
                    ]);
        $response->assertStatus(200)->assertJson(['status' => 'success']);
    }
    
    /**
     @todo
     * Метод подтверждение четырехзначного кода смс для авторизации
     */
    public function partner_check_sms_code_to_login()
    {
        $token = $this->getToken();
        $otp = DB::table('otp_enter_code_attempts')->where('phone',$this->phone)->first();
        $response = $this->withHeaders([
                        "Authorization" => "Bearer {$token}"
                    ])
                    ->post($this->endpoint.'partner/buyers/check-sms-code',[
                        'phone' => $this->phone,
                        'code' => $otp->code
                    ]);
        $response->assertStatus(200)->assertJson(['status' => 'success']);
    }
    
    /**
     @test
     * Метод для проверки статус пользователя
     */
    public function get_buyer_status_with_valid_credentials()
    {
        $token = $this->getToken();
        $response = $this->withHeaders([
                        "Authorization" => "Bearer {$token}"
                    ])
                    ->get($this->endpoint.'buyers/list?phone='.$this->phone);
        $response->assertStatus(200)->assertJsonStructure([
            'status',
            'error',
            'data' => [
                'id',
                'status',
            ]
        ]);
    }
    
    /**
     @test
     * Метод для проверки статус пользователя с недействительными учетными данными
     */
    public function get_buyer_status_with_invalid_credentials()
    {
        $token = $this->getToken();
        $response = $this->withHeaders([
                        "Authorization" => "Bearer {$token}"
                    ])
                    ->get($this->endpoint.'buyers/list?phone=111112222222');
        $response->assertStatus(400)->assertJsonStructure($this->getBadResponseExample());
    }
    
    /**
     @test
     * Метод проверяет наличие телефона
     */
    public function get_buyer_phones_count()
    {
        $token = $this->getToken();
        $response = $this->withHeaders([
                        "Authorization" => "Bearer {$token}"
                    ])
                    ->get($this->endpoint.'buyer/phones-count?buyer_id='.$this->buyer_id);
        $response->assertStatus(200)->assertJsonStructure([
            'status',
            'error',
            'data' => ['phones_count']
        ]);
    }
    
    /**
     @test
     * Метод проверяет наличие телефона с недействительными учетными данными
     */
    public function get_buyer_phones_count_with_invalid_credentials()
    {
        $token = $this->getToken();
        $response = $this->withHeaders([
                        "Authorization" => "Bearer {$token}"
                    ])
                    ->get($this->endpoint.'buyer/phones-count?buyer_id=111112222222');
        $response->assertStatus(400)->assertJsonStructure($this->getBadResponseExample());
    }
    
    /**
     @test
     * Проверка статус випа 
     */
    public function buyer_check_vip()
    {
        $token = $this->getToken();
        $response = $this->withHeaders([
                        "Authorization" => "Bearer {$token}"
                    ])
                    ->post($this->endpoint.'partner/buyers/check-vip',[
                        'phone' => $this->phone
                    ]);
        $response->assertStatus(200)->assertJsonStructure([
            'status',
            'error',
            'data' => ['vip']
        ]);
    }
    
    /**
     @test
     * Проверка статус випа с недействительными учетными данными
     */
    public function buyer_check_vip_with_invalid_credentials()
    {
        $token = $this->getToken();
        $response = $this->withHeaders([
                        "Authorization" => "Bearer {$token}"
                    ])
                    ->post($this->endpoint.'partner/buyers/check-vip',[
                        'phone' => '111112222222'
                    ]);
        $response->assertStatus(400)->assertJsonStructure($this->getBadResponseExample());
    }

    /**
     @test
     * Метод добавление доверительных лиц для покупателя (Add guarant for buyer) 
     */
    public function add_guarant()
    {
        $this->withoutExceptionHandling();
        $token = $this->getToken();
        $buyer = Buyer::where('status',User::KYC_STATUS_GUARANT)->first();
        $response = $this->withHeaders([
                        "Authorization" => "Bearer {$token}"
                    ])
                    ->post($this->endpoint.'partner/buyer/add-guarant',[
                        'buyer_id' => $buyer ? $buyer->id : $this->buyer_id,
                        'data' => [
                            [
                                'phone' => '998901234567',
                                'name' => 'First guarant',
                            ],
                            [
                                'phone' => '998901234568',
                                'name' => 'Second guarant',
                            ]
                        ]
                    ]);
        $response->assertStatus(200)->assertJsonStructure(['status','error','data']);
    }
    
    /**
     @test
     * Метод добавление доверительных лиц для покупателя (Add guarant for buyer) с недействительными учетными данными
     */
    public function add_guarant_with_invalid_credentials()
    {
        $this->withoutExceptionHandling();
        $token = $this->getToken();
        $response = $this->withHeaders([
                        "Authorization" => "Bearer {$token}"
                    ])
                    ->post($this->endpoint.'partner/buyer/add-guarant',[
                        'buyer_id' => $this->buyer_id,
                        'data' => [
                            [
                                'phone' => '123',
                                'name' => 'First guarant',
                            ],
                            [
                                'phone' => '321',
                                'name' => 'Second guarant',
                            ]
                        ]
                    ]);
        $response->assertStatus(400)->assertJsonStructure($this->getBadResponseExample());
    }

    /**
     @test
     * Метод отправка четырехзначного кода смс на номер телефона для добавление карты платежей (Send SMS code for verification)
     */
    public function send_sms_code_to_buyer_phone_to_add_card()
    {
        $this->withoutExceptionHandling();
        $token = $this->getToken();
        $response = $this->withHeaders([
                        "Authorization" => "Bearer {$token}"
                    ])
                    ->post($this->endpoint.'buyer/send-sms-code-uz',[
                        'phone' => $this->phone,
                        'card' => '9860122112211221',
                        'exp' => '1226',
                    ]);
        $response->assertStatus(200)->assertJsonStructure(['status','error','data']);
    }
    
    /**
     @todo
     * Метод подтверждение четырехзначного кода смс для добавление карты платежей (Send sms code for confirm verification)
     */
    public function check_sms_code_to_add_card()
    {
        $this->withoutExceptionHandling();
        $token = $this->getToken();
        $response = $this->withHeaders([
                        "Authorization" => "Bearer {$token}"
                    ])
                    ->post($this->endpoint.'buyer/check-sms-code-uz',[
                        'phone' => $this->phone,
                        'card_number' => '9860122112211221',
                        'card_valid_date' => '1226',
                        'code' => '1111',
                    ]);
        $response->assertStatus(200)->assertJsonStructure(['status','error','data']);
    }
    
    /**
     @test
     * Список платежных услуг (list of pay services)
     */
    public function get_list_of_pay_services()
    {
        $this->withoutExceptionHandling();
        $token = $this->getToken();
        $response = $this->withHeaders([
                        "Authorization" => "Bearer {$token}"
                    ])
                    ->get($this->endpoint.'buyer/pay-services/list');
        $response->assertStatus(200)->assertJsonStructure(['status','error','data' => ['*' => ['title','items']]]);
    }
    
    /**
     @test
     * Бонусный баланс (Bonus balance)
     */
    public function get_bonus_balance_of_buyer()
    {
        $this->withoutExceptionHandling();
        $token = $this->getToken();
        $response = $this->withHeaders([
                        "Authorization" => "Bearer {$token}"
                    ])
                    ->get($this->endpoint.'buyer/bonus-balance');
        $response->assertStatus(200)->assertJsonStructure(['status','error','data' => ['bonus']]);
    }
    
    /**
     @test
     * Метод перевод бонусных сумм на карту продавца
     */
    public function bonus_to_card()
    {
        $this->withoutExceptionHandling();
        $token = $this->getToken();
        $response = $this->withHeaders([
                        "Authorization" => "Bearer {$token}"
                    ])
                    ->post($this->endpoint.'buyer/bonus-to-card',[
                        'bonus_sum_request' => 1000,
                        'card_id' => 10,
                    ]);
        $response->assertStatus(200)->assertJsonStructure(['status','error','data']);
    }
    
    /**
     @todo
     * Метод перевод бонусных сумм на карту продавца
     */
    public function bonus_to_card_confirm()
    {
        $this->withoutExceptionHandling();
        $token = $this->getToken();
        $response = $this->withHeaders([
                        "Authorization" => "Bearer {$token}"
                    ])
                    ->post($this->endpoint.'buyer/bonus-to-card-confirm',[
                        'amount' => 1000,
                        'card_id' => 10,
                        'sms_code' => '1111',
                    ]);
        $response->assertStatus(200)->assertJsonStructure(['status','error','data']);
    }
    
    /**
     @todo
     * Метод оплата за выбранные платежные услуги. (Payment for selected pay services) (В том числе оплата с бонусных сумм)
     */
    public function payment_for_specific_service()
    {
        $this->withoutExceptionHandling();
        $token = $this->getToken();
        $response = $this->withHeaders([
                        "Authorization" => "Bearer {$token}"
                    ])
                    ->post($this->endpoint.'buyer/pay-services/pay',[
                        'amount' => 1000,
                        'account' => '998974708222',
                        'id' => 2,
                    ]);
        $response->assertStatus(200)->assertJsonStructure(['status','error','data']);
    }
    
    /**
     @test
     * Метод отправляет otp код для отмены договора
     */
    public function send_sms_to_cancel_contract()
    {
        $this->withoutExceptionHandling();
        $token = $this->getToken();
        $contract = Contract::where('status',Contract::STATUS_ACTIVE)->where('partner_id',$this->partner_id)->first();
        if($contract){
            $response = $this->withHeaders(["Authorization" => "Bearer {$token}"])
                        ->post($this->endpoint.'contracts/send-cancel-sms',[
                            'contract_id' => $contract->id
                        ]);
            $response->assertStatus(200)->assertJsonStructure(['status','error','data']);
        }
        $this->assertTrue(true);
    }
    
    /**
     @todo
     * Метод подтверждение otp кода для отмены договора
     */
    public function check_sms_to_cancel_contract()
    {
        $this->withoutExceptionHandling();
        $token = $this->getToken();
        $contract = Contract::where('status',Contract::STATUS_ACTIVE)->where('company_id',$this->partner_id)->first();
        if($contract){
            $response = $this->withHeaders(["Authorization" => "Bearer {$token}"])
                        ->post($this->endpoint.'contracts/check-cancel-sms',[
                            'contract_id' => $contract->id,
                            'code' => '1111'
                        ]);
            $response->assertStatus(200)->assertJsonStructure(['status','error','data']);
        }
        $this->assertTrue(true);
    }
    
    /**
     @test
     * Метод для загрузки акта
     */
    public function upload_contract_act()
    {
        $this->withoutExceptionHandling();
        $token = $this->getToken();
        $contract = Contract::where('company_id',$this->partner_id)->first();
        if($contract){
            $filename = public_path('docs/test-instruction.pdf');
            $file = new UploadedFile($filename,'test-instruction.pdf','application/pdf',null,true);
            $response = $this->withHeaders(["Authorization" => "Bearer {$token}"])
                        ->post($this->endpoint.'contracts/upload-act',[
                            'id' => $contract->id,
                            'act' => $file
                        ]);
            $response->assertStatus(200)->assertJsonStructure(['status','error','data' => ['path']]);
        }
        $this->assertTrue(true);
    }
    
    /**
     @test
     * Метод для загрузки фото IMEI
     */
    public function upload_contract_imei()
    {
        $this->withoutExceptionHandling();
        $token = $this->getToken();
        $contract = Contract::where('company_id',$this->partner_id)->first();
        if($contract){
            $filename = public_path('docs/test-instruction.pdf');
            $file = new UploadedFile($filename,'test-instruction.pdf','application/pdf',null,true);
            $response = $this->withHeaders(["Authorization" => "Bearer {$token}"])
                        ->post($this->endpoint.'contracts/upload-imei',[
                            'id' => $contract->id,
                            'imei' => $file
                        ]);
            $response->assertStatus(200)->assertJsonStructure(['status','error','data' => ['path']]);
        }
        $this->assertTrue(true);
    }

    /**
     @test
     * Метод для загрузки фото клиента с товаром
     */
    public function upload_client_photo_with_product_to_contract()
    {
        $this->withoutExceptionHandling();
        $token = $this->getToken();
        $contract = Contract::where('company_id',$this->partner_id)->first();
        if($contract){
            $filename = public_path('docs/test-instruction.pdf');
            $file = new UploadedFile($filename,'test-instruction.pdf','application/pdf',null,true);
            $response = $this->withHeaders(["Authorization" => "Bearer {$token}"])
                        ->post($this->endpoint.'contracts/upload-client-photo',[
                            'id' => $contract->id,
                            'client_photo' => $file
                        ]);
            $response->assertStatus(200)->assertJsonStructure(['status','error','data' => ['path']]);
        }
        $this->assertTrue(true);
    }
    
    /**
     @test
     * Метод для онлайн подписи (buyer’s online signature)
     */
    public function contract_sign()
    {
        $this->withoutExceptionHandling();
        $token = $this->getToken();
        $contract = Contract::where('company_id',$this->partner_id)->where('is_allowed_online_signature',1)->first();
        if($contract){
            $filename = public_path('docs/test-instruction.pdf');
            $file = new UploadedFile($filename,'test-instruction.pdf','application/pdf',null,true);
            $response = $this->withHeaders(["Authorization" => "Bearer {$token}"])
                        ->post($this->endpoint.'contracts/sign',[
                            'id' => $contract->id,
                            'sign' => $file
                        ]);
            $response->assertStatus(200)->assertJsonStructure(['status','error','data' => ['link']]);
        }
        $this->assertTrue(true);
    }
    
    /**
     @test
     * Метод который будет возвращать список договоров
     */
    public function get_orders_list()
    {
        $this->withoutExceptionHandling();
        $token = $this->getToken();
        $response = $this->withHeaders(["Authorization" => "Bearer {$token}"])
                    ->get($this->endpoint.'order/list');
        $response->assertStatus(200)->assertJsonStructure(['status','error','data']);
    }
    
    /**
     @test
     * Калькулятор который считает стоимость товара с наценкой
     */
    public function order_calculate()
    {
        $this->withoutExceptionHandling();
        $token = $this->getToken();
        $response = $this->withHeaders(["Authorization" => "Bearer {$token}"])
                    ->post($this->endpoint.'order/calculate',[
                        'user_id' => $this->buyer_id,
                        'period' => 6,
                        'products' => [
                            [
                                'amount' => 1,
                                'name' => 'Product 1',
                                'imei' => '',
                                'price' => 100000
                            ],
                            [
                                'amount' => 1,
                                'name' => 'Product 2',
                                'imei' => '',
                                'price' => 50000
                            ]
                        ],
                    ]);
        $response->assertStatus(200)->assertJsonStructure(['status','error','data' => ['total','origin','month','partner','deposit','products','amount','contract']]);
    }
    
    /**
     @test
     * Метод который считает бонусов продавца от стоимости товара
     */
    public function order_calculate_bonus()
    {
        $this->withoutExceptionHandling();
        $token = $this->getToken();
        $response = $this->withHeaders(["Authorization" => "Bearer {$token}"])
                    ->post($this->endpoint.'order/calculate-bonus',[
                        'user_id' => $this->buyer_id,
                        'period' => 6,
                        'products' => [
                            [
                                'amount' => 1,
                                'name' => 'Product 1',
                                'imei' => '',
                                'price' => 100000
                            ],
                            [
                                'amount' => 1,
                                'name' => 'Product 2',
                                'imei' => '',
                                'price' => 50000
                            ]
                        ],
                    ]);
        $response->assertStatus(200)->assertJsonStructure(['status','error','data' => ['bonus_amount']]);
    }
    
    /**
     @todo
     * Метод для создания договора
     */
    public function order_add()
    {
        $this->withoutExceptionHandling();
        $token = $this->getToken();
        $_SERVER['SERVER_NAME'] = 'https://test.uz';
        $response = $this->withHeaders(["Authorization" => "Bearer {$token}"])
                    ->post($this->endpoint.'orders/add',[
                        'user_id' => 372698,
                        'period' => 6,
                        'products' => [
                            [
                                'amount' => 1,
                                'name' => 'Product 1',
                                'imei' => '',
                                'price' => 100000
                            ],
                            [
                                'amount' => 1,
                                'name' => 'Product 2',
                                'imei' => '',
                                'price' => 50000
                            ]
                        ],
                    ]);
        $response->assertStatus(200)->assertJsonStructure(['status','error','data' => ['order']]);
    }
    
    /**
     @test
     * Метод возвращает лист регионов
     */
    public function get_region_list()
    {
        $this->withoutExceptionHandling();
        $token = $this->getToken();
        $response = $this->withHeaders(["Authorization" => "Bearer {$token}"])
                    ->get($this->endpoint.'regions/list');
        $response->assertStatus(200)->assertJsonStructure(['status','error','data' => ['*' => ['regionid','regioncode','nameRu','nameUz']]]);
    }
    
    /**
     @test
     * Лист home пейджа
     */
    public function get_categories_list()
    {
        $this->withoutExceptionHandling();
        $token = $this->getToken();
        $response = $this->withHeaders(["Authorization" => "Bearer {$token}"])
                    ->get($this->endpoint.'categories/list');
        $response->assertStatus(200)->assertJsonStructure(['status','error','data' => ['*' => ['id','sort','parent_id','psic_code','psic_text','status','child']]]);
    }
    
    /**
     @test
     * Показывает item по выбранной категории
     */
    public function get_category_by_id()
    {
        $this->withoutExceptionHandling();
        $token = $this->getToken();
        $response = $this->withHeaders(["Authorization" => "Bearer {$token}"])
                    ->get($this->endpoint.'categories/detail/1245');
        $response->assertStatus(200)->assertJsonStructure(['status','error','data' => ['*' => ['id','sort','parent_id','psic_code','psic_text','status','child']]]);
    }
}
