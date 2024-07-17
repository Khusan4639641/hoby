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

class PayToValidContractTest extends TestCase
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
                                  int   $status,
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
                'status' => $status,
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
     * Check paid to active contract
     *
     * @return void
     */
    public function testActiveContractPaid()
    {
        $buyer = $this->initBuyer(12000000);
        $contract = $this->initContract($buyer, Contract::STATUS_ACTIVE, 1000000, 4);

        $this->assertEquals(12000000, $buyer->settings->personal_account);

        $userAccount = new Account($buyer);
        $userAccount->payContractFromAccount($contract, 4000000);

        $this->assertEquals(8000000, $buyer->settings->personal_account);

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

    }

    /**
     * Check paid to partial overdue contract
     *
     * @return void
     */
    public function testPartialOverdueContractPaid()
    {
        $buyer = $this->initBuyer(12000000);
        $contract = $this->initContract($buyer, Contract::STATUS_OVERDUE_30_DAYS, 1000000, 4);

        $this->assertEquals(12000000, $buyer->settings->personal_account);

        $userAccount = new Account($buyer);
        $userAccount->payContractFromAccount($contract, 4000000);

        $this->assertEquals(8000000, $buyer->settings->personal_account);

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
    }

    /**
     * Check paid to full overdue contract
     *
     * @return void
     */
    public function testFullOverdueContractPaid()
    {
        $buyer = $this->initBuyer(12000000);
        $contract = $this->initContract($buyer, Contract::STATUS_OVERDUE_60_DAYS, 1000000, 4);

        $this->assertEquals(12000000, $buyer->settings->personal_account);

        $userAccount = new Account($buyer);
        $userAccount->payContractFromAccount($contract, 4000000);

        $this->assertEquals(8000000, $buyer->settings->personal_account);

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
    }

    /**
     * Check paid to not confirmed contract
     *
     * @return void
     */
    public function testNotConfirmedContractPaid()
    {
        $buyer = $this->initBuyer(12000000);
        $contract = $this->initContract($buyer, Contract::STATUS_AWAIT_SMS, 1000000, 4);

        $this->assertEquals(12000000, $buyer->settings->personal_account);

        $userAccount = new Account($buyer);
        $userAccount->payContractFromAccount($contract, 4000000);

        $this->assertEquals(12000000, $buyer->settings->personal_account);

        $schedules = $contract->schedule;

        $schedule = $schedules[0];
        $this->assertEquals(1000000, $schedule->balance);
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
        $this->assertEquals(0, $payments->count());
    }

    /**
     * Check paid to moderation contract
     *
     * @return void
     */
    public function testModerationContractPaid()
    {
        $buyer = $this->initBuyer(12000000);
        $contract = $this->initContract($buyer, Contract::STATUS_AWAIT_VENDOR, 1000000, 4);

        $this->assertEquals(12000000, $buyer->settings->personal_account);

        $userAccount = new Account($buyer);
        $userAccount->payContractFromAccount($contract, 4000000);

        $this->assertEquals(12000000, $buyer->settings->personal_account);

        $schedules = $contract->schedule;

        $schedule = $schedules[0];
        $this->assertEquals(1000000, $schedule->balance);
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
        $this->assertEquals(0, $payments->count());
    }

    /**
     * Check paid to canceled contract
     *
     * @return void
     */
    public function testCanceledContractPaid()
    {
        $buyer = $this->initBuyer(12000000);
        $contract = $this->initContract($buyer, Contract::STATUS_CANCELED, 1000000, 4);

        $this->assertEquals(12000000, $buyer->settings->personal_account);

        $userAccount = new Account($buyer);
        $userAccount->payContractFromAccount($contract, 4000000);

        $this->assertEquals(12000000, $buyer->settings->personal_account);

        $schedules = $contract->schedule;

        $schedule = $schedules[0];
        $this->assertEquals(1000000, $schedule->balance);
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
        $this->assertEquals(0, $payments->count());
    }

    /**
     * Check paid to completed contract
     *
     * @return void
     */
    public function testCompletedContractPaid()
    {
        $buyer = $this->initBuyer(12000000);
        $contract = $this->initContract($buyer, Contract::STATUS_COMPLETED, 1000000, 4);

        $this->assertEquals(12000000, $buyer->settings->personal_account);

        $userAccount = new Account($buyer);
        $userAccount->payContractFromAccount($contract, 4000000);

        $this->assertEquals(12000000, $buyer->settings->personal_account);

        $schedules = $contract->schedule;

        $schedule = $schedules[0];
        $this->assertEquals(1000000, $schedule->balance);
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
        $this->assertEquals(0, $payments->count());
    }

}
