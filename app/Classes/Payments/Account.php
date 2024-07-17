<?php

namespace App\Classes\Payments;

use App\Classes\Payments\Interfaces\IContract;
use App\Classes\Payments\Interfaces\IContractSchedule;
use App\Classes\Payments\Interfaces\IUser;
use \App\Models\Payment as PaymentModel;
use App\Traits\LogTrait;

class Account
{

    use LogTrait;

    private $user;

    public function __construct(IUser $user)
    {
        $this->channel = 'payments';
        $this->user = $user;
    }

    private function refill(float $amount)
    {
        $userID = $this->userID();
        $this->logInfo('Пополнение личного счёта покупателя', compact('userID', 'amount'));
        $this->user->refill($amount);
    }

    private function debit(float $amount): bool
    {
        $userID = $this->userID();
        $this->logInfo('Списание с личного счёта покупателя', compact('userID', 'amount'));
        return $this->user->debit($amount);
    }

    public function balance(): float
    {
        $userID = $this->userID();
        $balance = $this->user->balance();
        $this->logInfo('Баланс личного счёта покупателя', compact('userID', 'balance'));
        return $balance;
    }

    public function userID(): int
    {
        return $this->user->userID();
    }

    public function refillByAutopay(float $amount): BasePayment
    {
        $this->refill($amount);
        return new AutopayPayment(new PaymentModel(), $this->user->userID(), $amount);
    }

    public function refillByUzcard(float $amount, int $cardID, string $transactionID, string $uuid): BasePayment
    {
        $this->refill($amount);
        return new UzcardToAccountPayment(new PaymentModel(), $this->user->userID(), $amount, $cardID, $transactionID, $uuid);
    }

    public function refillByHumo(float $amount, int $cardID, string $transactionID, string $uuid): BasePayment
    {
        $this->refill($amount);
        return new HumoToAccountPayment(new PaymentModel(), $this->user->userID(), $amount, $cardID, $transactionID, $uuid);
    }

    public function payContractFromAccount(IContract $contract, float $amount)
    {
        $contractID = $contract->getID();
        $this->logInfo('Оплата контракта', compact('contractID', 'amount'));

        if (!$contract->isValid()) {
            $this->logInfo('Не удалось оплатить контракт. Контракт не валиден', compact('contractID', 'amount'));
            return;
        }

        // Получить сумму которую можно оплатить со счёта
        // Получить долг который нужно закрыть
        // Сравнить и получиь наименьшую сумму
        // Полученную сумму отправить на погашение долга

        $accountBalance = $this->user->balance();
        $contractDebt = $contract->debt();

        $realAmount = $accountBalance <= $contractDebt ? $accountBalance : $contractDebt;

        $realAmount = $realAmount <= $amount ? $realAmount : $amount;

        if ($this->debit($realAmount)) {
            $contract->pay($realAmount);
            $writeOffAmount = $realAmount;
            while ($contract->unpaidScheduleCount() > 0 && $writeOffAmount > 0) {
                $schedule = $contract->unpaidSchedule();
                $debt = $schedule->debt();
                $realWriteOffAmount = $writeOffAmount;
                if ($writeOffAmount > $debt) {
                    $realWriteOffAmount = $debt;
                }
                $writeOffAmount -= $realWriteOffAmount;
                $this->payContractSchedule($contract, $schedule, $realWriteOffAmount);
            }
        }

    }

    public function payContractSchedule(IContract $contract, IContractSchedule $schedule, float $amount)
    {
        $contractID = $contract->getID();
        $scheduleID = $schedule->getID();
        $this->logInfo('Оплата месячного плана контракта', compact('contractID', 'scheduleID', 'amount'));

        $schedule->pay($amount);
        new AccountPayment(new PaymentModel(),
            $this->user->userID(),
            $amount,
            $contract->getID(),
            $contract->getOrderID(),
            $schedule->getID());
    }

}
