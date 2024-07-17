<?php

namespace Tests\Unit;

use App\Classes\Payments\Account;
use App\Models\Buyer;
use App\Models\BuyerSetting;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RefillBuyerAccountTest extends TestCase
{

    public function setUp(): void
    {
        parent::setUp();

    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testRefillByAutopay()
    {
        $accountAmount = 12000000;

        $buyer = factory(Buyer::class, 1)
            ->create()
            ->each(function ($user) {
                $user->settings()->save(factory(BuyerSetting::class)->make());
            })->first();

        $buyer->settings->personal_account = 0;
        $buyer->save();
        $userAccount = new Account($buyer);
        $this->assertEquals(0, $buyer->settings->personal_account);
        $userAccount->refillByAutopay($accountAmount);
        $this->assertEquals($accountAmount, $buyer->settings->personal_account);
        $payments = $buyer->pay;
        $this->assertEquals(1, $payments->count());
        $payment = $payments->first();
        $this->assertEquals($accountAmount, $payment->amount);
        $this->assertEquals('user', $payment->type);
        $this->assertEquals('Autopay', $payment->payment_system);
    }

}
