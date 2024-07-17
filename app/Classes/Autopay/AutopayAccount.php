<?php

namespace App\Classes\Autopay;

use App\Classes\Payments\Account;
use App\Facades\UniversalAutoPayment;
use App\Models\Buyer;
use App\Models\BuyerPersonal;
use App\Models\Universal\UniversalAutoPaymentsTransaction;
use App\Models\Universal\UniversalDebtor;
use App\Traits\LogTrait;

class AutopayAccount
{

    use LogTrait;

    /*
     * @todo deprecated
     */
    const LOG_CHANNEL = "universal";

    private $buyer;
    private $debtor;

    static public function findBuyerByPassport(string $number, string $pinfl): ?Buyer
    {
        $passportMD5 = md5($number);
        $pinflMD5 = md5($pinfl);
        $buyerPersonal = BuyerPersonal::query()
            ->where('pinfl_hash', $pinflMD5)
            ->where('passport_number_hash', $passportMD5)
            ->has('buyer')
            ->with('buyer')
            ->first();
        if (!$buyerPersonal) {
            AutopayAccount::sLogError('Покупатель не найден по паспортнвм данным', compact('number', 'pinfl'));
            return null;
        }
        return $buyerPersonal->buyer;
    }

    static public function findByBuyerID(int $id): ?UniversalDebtor
    {
        $debtor = UniversalDebtor::query()
            ->where('user_id', $id)
            ->first();
        if (!$debtor) {
            AutopayAccount::sLogError('Должник не найден по userID', compact('id'));
            return null;
        }
        return $debtor;
    }

    static public function findByExtContractID(string $id): ?UniversalDebtor
    {
        $debtor = UniversalDebtor::query()
            ->where('external_contract_id', $id)
            ->first();
        if (!$debtor) {
            AutopayAccount::sLogError('Должник не найден по external_contract_id', compact('id'));
            return null;
        }
        return $debtor;
    }

    static public function makeDebtor(int $buyerID, float $amount): ?UniversalDebtor
    {
        $debtor = new UniversalDebtor();
        $debtor->user_id = $buyerID;
        $debtor->current_debit = $amount;
        $debtor->total_debit = $amount;
        $debtor->save();
        return $debtor;
    }

    static public function isExistTransaction(string $transactionID): bool
    {
        $count = UniversalAutoPaymentsTransaction::where('universal_transaction_id', $transactionID)
            ->count();
        return $count > 0;
    }

    public function __construct(Buyer $buyer, UniversalDebtor $uDebtor)
    {
        $this->channel = 'universal';
        $this->buyer = $buyer;
        $this->debtor = $uDebtor;
    }

    public function addPaymentTransaction(string $transactionID, string $debitID, float $amount)
    {
        $this->logInfo('Поиск транзакции', compact('transactionID', 'amount'));
        if (!self::isExistTransaction($transactionID)) {
            $this->logInfo('Транзакция не найдена', compact('transactionID'));
            $buyerID = $this->buyer->id;
            $this->logInfo('Добавление транзакции покупателю', compact('buyerID', 'transactionID', 'amount'));
            $account = new Account($this->buyer);
            $payment = $account->refillByAutopay($amount);
            $transaction = new UniversalAutoPaymentsTransaction();
            $transaction->payment_id = $payment->id();
            $transaction->universal_transaction_id = $transactionID;
            $transaction->universal_debit_id = $debitID;
            $transaction->amount = $amount;
            $transaction->debtor_id = $this->debtor->id;
            $transaction->save();
            $this->debtor->current_debit -= $amount;
            $this->debtor->current_debit = round($this->debtor->current_debit, 2);
            $this->debtor->save();
        }
    }

}
