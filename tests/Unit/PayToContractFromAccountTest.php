<?php

namespace Tests\Unit;

use App\Classes\Payments\Account;
use App\Models\Buyer;
use App\Models\BuyerSetting;
use App\Models\Contract;
use App\Models\ContractPaymentsSchedule;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PayToContractFromAccountTest extends TestCase
{

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

    /**
     * Check account balance before and after pay test
     *
     * @return void
     */
    public function testAccountBalance()
    {
        $buyer = $this->initBuyer(12000000);
        $contract = $this->initContract($buyer, 1000000, 4);

        $this->assertEquals(12000000, $buyer->settings->personal_account);

        $userAccount = new Account($buyer);
        $userAccount->payContractFromAccount($contract, 4000000);

        $this->assertEquals(8000000, $buyer->settings->personal_account);
    }

    /**
     * Contract full payment test
     *
     * @return void
     */
    public function testPayFullContract()
    {
        $buyer = $this->initBuyer(12000000);
        $contract = $this->initContract($buyer, 1000000, 12);

        $this->assertEquals(12000000, $buyer->settings->personal_account);

        $userAccount = new Account($buyer);
        $userAccount->payContractFromAccount($contract, 12000000);

        $this->assertEquals(0, $buyer->settings->personal_account);

        $this->assertEquals(0, $contract->balance);
        $this->assertEquals(9, $contract->status);

        foreach ($contract->schedule as $schedule) {
            $this->assertEquals(0, $schedule->balance);
            $this->assertEquals(1, $schedule->status);
        }

        $payments = $buyer->pay;
        $this->assertEquals(12, $payments->count());

        foreach ($payments as $payment) {
            $this->assertEquals(1000000, $payment->amount);
            $this->assertEquals('auto', $payment->type);
            $this->assertEquals('ACCOUNT', $payment->payment_system);
        }

    }

    /**
     * Contract half payment test
     *
     * @return void
     */
    public function testPayHalfContract()
    {
        $buyer = $this->initBuyer(12000000);
        $contract = $this->initContract($buyer, 1000000, 12);

        $this->assertEquals(12000000, $buyer->settings->personal_account);

        $userAccount = new Account($buyer);
        $userAccount->payContractFromAccount($contract, 6000000);

        $this->assertEquals(6000000, $buyer->settings->personal_account);

        $this->assertEquals(6000000, $contract->balance);
        $this->assertEquals(1, $contract->status);

        $i = 0;
        foreach ($contract->schedule as $schedule) {
            if ($i < 6) {
                $this->assertEquals(0, $schedule->balance);
                $this->assertEquals(1, $schedule->status);
            } else {
                $this->assertEquals(1000000, $schedule->balance);
                $this->assertEquals(0, $schedule->status);
            }
            $i++;
        }

        $payments = $buyer->pay;
        $this->assertEquals(6, $payments->count());

        foreach ($payments as $payment) {
            $this->assertEquals(1000000, $payment->amount);
            $this->assertEquals('auto', $payment->type);
            $this->assertEquals('ACCOUNT', $payment->payment_system);
        }

    }

    /**
     * Contract half payment test
     *
     * @return void
     */
    public function testPayFromSchedule1Amount0ToSchedule1Amount05()
    {
        $buyer = $this->initBuyer(12000000);
        $contract = $this->initContract($buyer, 1000000, 4);

        $this->assertEquals(12000000, $buyer->settings->personal_account);

        $userAccount = new Account($buyer);
        $userAccount->payContractFromAccount($contract, 500000);

        $this->assertEquals(11500000, $buyer->settings->personal_account);

        $this->assertEquals(3500000, $contract->balance);
        $this->assertEquals(1, $contract->status);

        $schedules = $contract->schedule;

        $schedule = $schedules[0];
        $this->assertEquals(500000, $schedule->balance);
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

        $payments = $buyer->pay;
        $this->assertEquals(1, $payments->count());

        $payment = $payments[0];
        $this->assertEquals(500000, $payment->amount);
        $this->assertEquals('auto', $payment->type);
        $this->assertEquals('ACCOUNT', $payment->payment_system);

    }

    /**
     * Contract half payment test
     *
     * @return void
     */
    public function testPayFromSchedule1Amount05ToSchedule2Amount05()
    {
        $buyer = $this->initBuyer(12000000);
        $contract = $this->initContract($buyer, 1000000, 4, 500000);

        $this->assertEquals(12000000, $buyer->settings->personal_account);

        $userAccount = new Account($buyer);
        $userAccount->payContractFromAccount($contract, 1000000);

        $this->assertEquals(11000000, $buyer->settings->personal_account);

        $this->assertEquals(2500000, $contract->balance);
        $this->assertEquals(1, $contract->status);

        $schedules = $contract->schedule;

        $schedule = $schedules[0];
        $this->assertEquals(0, $schedule->balance);
        $this->assertEquals(1, $schedule->status);

        $schedule = $schedules[1];
        $this->assertEquals(500000, $schedule->balance);
        $this->assertEquals(0, $schedule->status);

        $schedule = $schedules[2];
        $this->assertEquals(1000000, $schedule->balance);
        $this->assertEquals(0, $schedule->status);

        $schedule = $schedules[3];
        $this->assertEquals(1000000, $schedule->balance);
        $this->assertEquals(0, $schedule->status);

        $payments = $buyer->pay;
        $this->assertEquals(2, $payments->count());

        $payment = $payments[0];
        $this->assertEquals(500000, $payment->amount);
        $this->assertEquals('auto', $payment->type);
        $this->assertEquals('ACCOUNT', $payment->payment_system);

        $payment = $payments[1];
        $this->assertEquals(500000, $payment->amount);
        $this->assertEquals('auto', $payment->type);
        $this->assertEquals('ACCOUNT', $payment->payment_system);

    }

    /**
     * Contract half payment test
     *
     * @return void
     */
    public function testPayFromSchedule1Amount05ToSchedule1Amount1()
    {
        $buyer = $this->initBuyer(12000000);
        $contract = $this->initContract($buyer, 1000000, 4, 500000);

        $this->assertEquals(12000000, $buyer->settings->personal_account);

        $userAccount = new Account($buyer);
        $userAccount->payContractFromAccount($contract, 500000);

        $this->assertEquals(11500000, $buyer->settings->personal_account);

        $this->assertEquals(3000000, $contract->balance);
        $this->assertEquals(1, $contract->status);

        $schedules = $contract->schedule;

        $schedule = $schedules[0];
        $this->assertEquals(0, $schedule->balance);
        $this->assertEquals(1, $schedule->status);

        $schedule = $schedules[1];
        $this->assertEquals(1000000, $schedule->balance);
        $this->assertEquals(0, $schedule->status);

        $schedule = $schedules[2];
        $this->assertEquals(1000000, $schedule->balance);
        $this->assertEquals(0, $schedule->status);

        $schedule = $schedules[3];
        $this->assertEquals(1000000, $schedule->balance);
        $this->assertEquals(0, $schedule->status);

        $payments = $buyer->pay;
        $this->assertEquals(1, $payments->count());

        $payment = $payments[0];
        $this->assertEquals(500000, $payment->amount);
        $this->assertEquals('auto', $payment->type);
        $this->assertEquals('ACCOUNT', $payment->payment_system);

    }

    /**
     * Contract half payment test
     *
     * @return void
     */
    public function testPayFromSchedule1Amount05ToSchedule3Amount05()
    {
        $buyer = $this->initBuyer(12000000);
        $contract = $this->initContract($buyer, 1000000, 4, 500000);

        $this->assertEquals(12000000, $buyer->settings->personal_account);

        $userAccount = new Account($buyer);
        $userAccount->payContractFromAccount($contract, 3000000);

        $this->assertEquals(9000000, $buyer->settings->personal_account);

        $this->assertEquals(500000, $contract->balance);
        $this->assertEquals(1, $contract->status);

        $schedules = $contract->schedule;

        $schedule = $schedules[0];
        $this->assertEquals(0, $schedule->balance);
        $this->assertEquals(1, $schedule->status);

        $schedule = $schedules[1];
        $this->assertEquals(0, $schedule->balance);
        $this->assertEquals(1, $schedule->status);

        $schedule = $schedules[2];
        $this->assertEquals(0, $schedule->balance);
        $this->assertEquals(1, $schedule->status);

        $schedule = $schedules[3];
        $this->assertEquals(500000, $schedule->balance);
        $this->assertEquals(0, $schedule->status);

        $payments = $buyer->pay;
        $this->assertEquals(4, $payments->count());

        $payment = $payments[0];
        $this->assertEquals(500000, $payment->amount);
        $this->assertEquals('auto', $payment->type);
        $this->assertEquals('ACCOUNT', $payment->payment_system);

        $payment = $payments[1];
        $this->assertEquals(1000000, $payment->amount);
        $this->assertEquals('auto', $payment->type);
        $this->assertEquals('ACCOUNT', $payment->payment_system);

        $payment = $payments[2];
        $this->assertEquals(1000000, $payment->amount);
        $this->assertEquals('auto', $payment->type);
        $this->assertEquals('ACCOUNT', $payment->payment_system);

        $payment = $payments[3];
        $this->assertEquals(500000, $payment->amount);
        $this->assertEquals('auto', $payment->type);
        $this->assertEquals('ACCOUNT', $payment->payment_system);

    }

    /**
     * Contract half payment test
     *
     * @return void
     */
    public function testPayFromSchedule1Amount05ToSchedule3Amount1()
    {
        $buyer = $this->initBuyer(12000000);
        $contract = $this->initContract($buyer, 1000000, 4, 500000);

        $this->assertEquals(12000000, $buyer->settings->personal_account);

        $userAccount = new Account($buyer);
        $userAccount->payContractFromAccount($contract, 3500000);

        $this->assertEquals(8500000, $buyer->settings->personal_account);

        $this->assertEquals(0, $contract->balance);
        $this->assertEquals(9, $contract->status);

        $schedules = $contract->schedule;

        $schedule = $schedules[0];
        $this->assertEquals(0, $schedule->balance);
        $this->assertEquals(1, $schedule->status);

        $schedule = $schedules[1];
        $this->assertEquals(0, $schedule->balance);
        $this->assertEquals(1, $schedule->status);

        $schedule = $schedules[2];
        $this->assertEquals(0, $schedule->balance);
        $this->assertEquals(1, $schedule->status);

        $schedule = $schedules[3];
        $this->assertEquals(0, $schedule->balance);
        $this->assertEquals(1, $schedule->status);

        $payments = $buyer->pay;
        $this->assertEquals(4, $payments->count());

        $payment = $payments[0];
        $this->assertEquals(500000, $payment->amount);
        $this->assertEquals('auto', $payment->type);
        $this->assertEquals('ACCOUNT', $payment->payment_system);

        $payment = $payments[1];
        $this->assertEquals(1000000, $payment->amount);
        $this->assertEquals('auto', $payment->type);
        $this->assertEquals('ACCOUNT', $payment->payment_system);

        $payment = $payments[2];
        $this->assertEquals(1000000, $payment->amount);
        $this->assertEquals('auto', $payment->type);
        $this->assertEquals('ACCOUNT', $payment->payment_system);

        $payment = $payments[3];
        $this->assertEquals(1000000, $payment->amount);
        $this->assertEquals('auto', $payment->type);
        $this->assertEquals('ACCOUNT', $payment->payment_system);

    }

    /**
     * Contract half payment test
     *
     * @return void
     */
    public function testPayFromSchedule1Amount02ToSchedule1Amount08()
    {
        $buyer = $this->initBuyer(12000000);
        $contract = $this->initContract($buyer, 1000000, 4, 200000);

        $this->assertEquals(12000000, $buyer->settings->personal_account);

        $userAccount = new Account($buyer);
        $userAccount->payContractFromAccount($contract, 600000);

        $this->assertEquals(11400000, $buyer->settings->personal_account);

        $this->assertEquals(3200000, $contract->balance);
        $this->assertEquals(1, $contract->status);

        $schedules = $contract->schedule;

        $schedule = $schedules[0];
        $this->assertEquals(200000, $schedule->balance);
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

        $payments = $buyer->pay;
        $this->assertEquals(1, $payments->count());

        $payment = $payments[0];
        $this->assertEquals(600000, $payment->amount);
        $this->assertEquals('auto', $payment->type);
        $this->assertEquals('ACCOUNT', $payment->payment_system);

    }

    /**
     * Contract half payment test
     *
     * @return void
     */
    public function testPayFromSchedule2Amount02ToSchedule2Amount08()
    {
        $buyer = $this->initBuyer(12000000);
        $contract = $this->initContract($buyer, 1000000, 4, 1200000);

        $this->assertEquals(12000000, $buyer->settings->personal_account);

        $userAccount = new Account($buyer);
        $userAccount->payContractFromAccount($contract, 600000);

        $this->assertEquals(11400000, $buyer->settings->personal_account);

        $this->assertEquals(2200000, $contract->balance);
        $this->assertEquals(1, $contract->status);

        $schedules = $contract->schedule;

        $schedule = $schedules[0];
        $this->assertEquals(0, $schedule->balance);
        $this->assertEquals(1, $schedule->status);

        $schedule = $schedules[1];
        $this->assertEquals(200000, $schedule->balance);
        $this->assertEquals(0, $schedule->status);

        $schedule = $schedules[2];
        $this->assertEquals(1000000, $schedule->balance);
        $this->assertEquals(0, $schedule->status);

        $schedule = $schedules[3];
        $this->assertEquals(1000000, $schedule->balance);
        $this->assertEquals(0, $schedule->status);

        $payments = $buyer->pay;
        $this->assertEquals(1, $payments->count());

        $payment = $payments[0];
        $this->assertEquals(600000, $payment->amount);
        $this->assertEquals('auto', $payment->type);
        $this->assertEquals('ACCOUNT', $payment->payment_system);

    }

    /**
     * Contract overpayment test
     *
     * @return void
     */
    public function testOverpayment()
    {
        $buyer = $this->initBuyer(12000000);
        $contract = $this->initContract($buyer, 1000000, 4);

        $this->assertEquals(12000000, $buyer->settings->personal_account);

        $userAccount = new Account($buyer);
        $userAccount->payContractFromAccount($contract, 11000000);

        $this->assertEquals(8000000, $buyer->settings->personal_account);

        $this->assertEquals(0, $contract->balance);
        $this->assertEquals(9, $contract->status);

        $schedules = $contract->schedule;

        $schedule = $schedules[0];
        $this->assertEquals(0, $schedule->balance);
        $this->assertEquals(1, $schedule->status);

        $schedule = $schedules[1];
        $this->assertEquals(0, $schedule->balance);
        $this->assertEquals(1, $schedule->status);

        $schedule = $schedules[2];
        $this->assertEquals(0, $schedule->balance);
        $this->assertEquals(1, $schedule->status);

        $schedule = $schedules[3];
        $this->assertEquals(0, $schedule->balance);
        $this->assertEquals(1, $schedule->status);

        $payments = $buyer->pay;
        $this->assertEquals(4, $payments->count());

        $payment = $payments[0];
        $this->assertEquals(1000000, $payment->amount);
        $this->assertEquals('auto', $payment->type);
        $this->assertEquals('ACCOUNT', $payment->payment_system);

        $payment = $payments[1];
        $this->assertEquals(1000000, $payment->amount);
        $this->assertEquals('auto', $payment->type);
        $this->assertEquals('ACCOUNT', $payment->payment_system);

        $payment = $payments[2];
        $this->assertEquals(1000000, $payment->amount);
        $this->assertEquals('auto', $payment->type);
        $this->assertEquals('ACCOUNT', $payment->payment_system);

        $payment = $payments[3];
        $this->assertEquals(1000000, $payment->amount);
        $this->assertEquals('auto', $payment->type);
        $this->assertEquals('ACCOUNT', $payment->payment_system);

    }

}
