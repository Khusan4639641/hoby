<?php

namespace Tests\Unit;

use App\Classes\Payments\Account;
use App\Models\Buyer;
use App\Models\BuyerSetting;
use App\Models\Contract;
use App\Models\ContractPaymentsSchedule;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PayToContractFromAccountInPenniesTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
//    public function testExample()
//    {
//        $response = $this->get('/');
//
//        $response->assertStatus(200);
//    }

    public function setUp(): void
    {
        parent::setUp();
    }

    private function initBuyer(float $accountAmount): Buyer
    {
        return factory(Buyer::class, 1)
            ->create()
            ->each(function ($user) use ($accountAmount) {
                $user->settings()->save(factory(BuyerSetting::class)->make([
                    'personal_account' => $accountAmount,
                ]));
            })->first();
    }

    private function initContract(Buyer $buyer,
                                  float $scheduleAmount,
                                  float $period,
                                  float $payedAmount = 0): Contract
    {
        $contractAmount = $scheduleAmount * $period;
        $order = factory(Order::class)->create([
            'user_id' => $buyer->id,
        ]);
        $paymentDate = Carbon::parse(Carbon::now()->format("Y-m-") . "15")->subMonth(6);
        return factory(Contract::class, 1)
            ->create([
                'user_id' => $buyer->id,
                'order_id' => $order->id,
                'total' => $contractAmount,
                'balance' => $contractAmount - $payedAmount,
            ])
            ->each(function ($contract) use (
                $buyer,
                $period,
                $scheduleAmount,
                $paymentDate,
                $payedAmount
            ) {
                for ($i = 0; $i < $period; $i++) {
                    $status = 0;
                    $balance = $scheduleAmount;
                    $curSchedulePayedAmount = $payedAmount - ($scheduleAmount * $i);
                    if (($scheduleAmount * $i) < $payedAmount) {
                        $status = 1;
                        $balance = 0;
                    }
                    if ($curSchedulePayedAmount < $scheduleAmount && $curSchedulePayedAmount > 0) {
                        $status = 0;
                        $balance = $scheduleAmount - $curSchedulePayedAmount;
                    }
                    $contract->schedule()->save(factory(ContractPaymentsSchedule::class)
                        ->make([
                            'user_id' => $buyer->id,
                            'payment_date' => $paymentDate->format("Y-m-d H:i:s"),
                            'total' => $scheduleAmount,
                            'balance' => $balance,
                            'status' => $status,
                        ]));
                    $paymentDate->addMonth();
                }
            })->first();
    }

    // Payed amount 0.12
    // Payment from account 0.5
    public function testBalanceAfterPaymentInPennies() {

        $buyer = $this->initBuyer(12000000);
        $contract = $this->initContract($buyer, 1000000, 4, 0.12);

        $this->assertEquals(12000000, $buyer->settings->personal_account);

        $userAccount = new Account($buyer);
        $userAccount->payContractFromAccount($contract, 0.5);

        $this->assertEquals(11999999.5, $buyer->settings->personal_account);
        $this->assertEquals(3999999.38, $contract->balance);
        $this->assertEquals(1, $contract->status);

        $payments = $buyer->pay;
        $this->assertEquals(1, $payments->count( ));

        $schedules = $contract->schedule;

        $schedule = $schedules[0];
        $this->assertEquals(999999.38, $schedule->balance);
        $this->assertEquals(0, $schedule->status);

        $schedule = $schedules[1];
        $this->assertEquals(1000000, $schedule->balance);
        $this->assertEquals(0, $schedule->status);

        $schedule = $schedules[2];
        $this->assertEquals(1000000, $schedule->balance);
        $this->assertEquals(0, $schedule->status);

        $schedule = $schedules[3];
        $this->assertEquals(1000000, $schedule->balance);
        $this->assertEquals(0, $schedule->status);
    }

    // Payed amount 0
    // Payment from account 0.66
    public function testBalanceAfterPaymentInPennies1() {

        $buyer = $this->initBuyer(12000000);
        $contract = $this->initContract($buyer, 1000000, 4);

        $this->assertEquals(12000000, $buyer->settings->personal_account);

        $userAccount = new Account($buyer);
        $userAccount->payContractFromAccount($contract, 0.66);

        $this->assertEquals(11999999.34, $buyer->settings->personal_account);
        $this->assertEquals(3999999.34, $contract->balance);

        $payments = $buyer->pay;
        $this->assertEquals(1, $payments->count( ));

        $schedules = $contract->schedule;

        $schedule = $schedules[0];
        $this->assertEquals(999999.34, $schedule->balance);
        $this->assertEquals(0, $schedule->status);

        $schedule = $schedules[1];
        $this->assertEquals(1000000, $schedule->balance);
        $this->assertEquals(0, $schedule->status);

        $schedule = $schedules[2];
        $this->assertEquals(1000000, $schedule->balance);
        $this->assertEquals(0, $schedule->status);

        $schedule = $schedules[3];
        $this->assertEquals(1000000, $schedule->balance);
        $this->assertEquals(0, $schedule->status);

    }

    // Payed amount 0
    // Payment from account 1.16
    public function testBalanceAfterPaymentInPennies2() {

        $buyer = $this->initBuyer(12000000);
        $contract = $this->initContract($buyer, 1000000, 4);

        $this->assertEquals(12000000, $buyer->settings->personal_account);

        $userAccount = new Account($buyer);
        $userAccount->payContractFromAccount($contract, 1.16);

        $this->assertEquals(11999998.84, $buyer->settings->personal_account);
        $this->assertEquals(3999998.84, $contract->balance);

        $payments = $buyer->pay;
        $this->assertEquals(1, $payments->count( ));

        $schedules = $contract->schedule;

        $schedule = $schedules[0];
        $this->assertEquals(999998.84, $schedule->balance);
        $this->assertEquals(0, $schedule->status);

        $schedule = $schedules[1];
        $this->assertEquals(1000000, $schedule->balance);
        $this->assertEquals(0, $schedule->status);

        $schedule = $schedules[2];
        $this->assertEquals(1000000, $schedule->balance);
        $this->assertEquals(0, $schedule->status);

        $schedule = $schedules[3];
        $this->assertEquals(1000000, $schedule->balance);
        $this->assertEquals(0, $schedule->status);

    }

    // Payed amount 0.32
    // Payment from account 1.16
    public function testBalanceAfterPaymentInPennies3() {

        $buyer = $this->initBuyer(12000000);
        $contract = $this->initContract($buyer, 1000000, 4, 0.32);

        $this->assertEquals(12000000, $buyer->settings->personal_account);

        $userAccount = new Account($buyer);
        $userAccount->payContractFromAccount($contract, 1.16);

        $this->assertEquals(11999998.84, $buyer->settings->personal_account);
        $this->assertEquals(3999998.52, $contract->balance);

        $payments = $buyer->pay;
        $this->assertEquals(1, $payments->count( ));

        $schedules = $contract->schedule;

        $schedule = $schedules[0];
        $this->assertEquals(999998.52, $schedule->balance);
        $this->assertEquals(0, $schedule->status);

        $schedule = $schedules[1];
        $this->assertEquals(1000000, $schedule->balance);
        $this->assertEquals(0, $schedule->status);

        $schedule = $schedules[2];
        $this->assertEquals(1000000, $schedule->balance);
        $this->assertEquals(0, $schedule->status);

        $schedule = $schedules[3];
        $this->assertEquals(1000000, $schedule->balance);
        $this->assertEquals(0, $schedule->status);

    }

    // Payed amount 0.18
    // Payment from account 1.01
    public function testBalanceAfterPaymentInPennies4() {

        $buyer = $this->initBuyer(12000000);
        $contract = $this->initContract($buyer, 1000000, 4, 0.18);

        $this->assertEquals(12000000, $buyer->settings->personal_account);

        $userAccount = new Account($buyer);
        $userAccount->payContractFromAccount($contract, 1.01);

        $this->assertEquals(11999998.99, $buyer->settings->personal_account);
        $this->assertEquals(3999998.81, $contract->balance);

        $payments = $buyer->pay;
        $this->assertEquals(1, $payments->count( ));

        $schedules = $contract->schedule;

        $schedule = $schedules[0];
        $this->assertEquals(999998.81, $schedule->balance);
        $this->assertEquals(0, $schedule->status);

        $schedule = $schedules[1];
        $this->assertEquals(1000000, $schedule->balance);
        $this->assertEquals(0, $schedule->status);

        $schedule = $schedules[2];
        $this->assertEquals(1000000, $schedule->balance);
        $this->assertEquals(0, $schedule->status);

        $schedule = $schedules[3];
        $this->assertEquals(1000000, $schedule->balance);
        $this->assertEquals(0, $schedule->status);

    }

    // Payed amount 0.44
    // Payment from account 2.08
    public function testBalanceAfterPaymentInPennies5() {

        $buyer = $this->initBuyer(12000000);
        $contract = $this->initContract($buyer, 1000000, 4, 0.44);

        $this->assertEquals(12000000, $buyer->settings->personal_account);

        $userAccount = new Account($buyer);
        $userAccount->payContractFromAccount($contract, 2.08);

        $this->assertEquals(11999997.92, $buyer->settings->personal_account);
        $this->assertEquals(3999997.48, $contract->balance);

        $payments = $buyer->pay;
        $this->assertEquals(1, $payments->count( ));

        $schedules = $contract->schedule;

        $schedule = $schedules[0];
        $this->assertEquals(999997.48, $schedule->balance);
        $this->assertEquals(0, $schedule->status);

        $schedule = $schedules[1];
        $this->assertEquals(1000000, $schedule->balance);
        $this->assertEquals(0, $schedule->status);

        $schedule = $schedules[2];
        $this->assertEquals(1000000, $schedule->balance);
        $this->assertEquals(0, $schedule->status);

        $schedule = $schedules[3];
        $this->assertEquals(1000000, $schedule->balance);
        $this->assertEquals(0, $schedule->status);

    }

    // Payed amount 0.54
    // Payment from account 0.46
    public function testBalanceAfterPaymentInPennies6() {

        $buyer = $this->initBuyer(12000000);
        $contract = $this->initContract($buyer, 1000000, 4, 0.54);

        $this->assertEquals(12000000, $buyer->settings->personal_account);

        $userAccount = new Account($buyer);
        $userAccount->payContractFromAccount($contract, 0.46);

        $this->assertEquals(11999999.54, $buyer->settings->personal_account);
        $this->assertEquals(3999999, $contract->balance);

        $payments = $buyer->pay;
        $this->assertEquals(1, $payments->count( ));

        $schedules = $contract->schedule;

        $schedule = $schedules[0];
        $this->assertEquals(999999, $schedule->balance);
        $this->assertEquals(0, $schedule->status);

        $schedule = $schedules[1];
        $this->assertEquals(1000000, $schedule->balance);
        $this->assertEquals(0, $schedule->status);

        $schedule = $schedules[2];
        $this->assertEquals(1000000, $schedule->balance);
        $this->assertEquals(0, $schedule->status);

        $schedule = $schedules[3];
        $this->assertEquals(1000000, $schedule->balance);
        $this->assertEquals(0, $schedule->status);

    }

    public function testIfSwitchedToNextSchedule() {
        $buyer = $this->initBuyer(12000000);
        $contract = $this->initContract($buyer, 10000, 4, 9999.5);

        $userAccount = new Account($buyer);
        $userAccount->payContractFromAccount($contract, 1);

        $this->assertEquals(11999999, $buyer->settings->personal_account);
        $this->assertEquals(29999.5, $contract->balance);

        $payments = $buyer->pay;
        $this->assertEquals(2, $payments->count( ));

        $schedules = $contract->schedule;

        $schedule = $schedules[0];
        $this->assertEquals(0, $schedule->balance);
        $this->assertEquals(1, $schedule->status);

        $schedule = $schedules[1];
        $this->assertEquals(9999.5, $schedule->balance);
        $this->assertEquals(0, $schedule->status);

        $schedule = $schedules[2];
        $this->assertEquals(10000, $schedule->balance);
        $this->assertEquals(0, $schedule->status);

        $schedule = $schedules[3];
        $this->assertEquals(10000, $schedule->balance);
        $this->assertEquals(0, $schedule->status);
    }
}
