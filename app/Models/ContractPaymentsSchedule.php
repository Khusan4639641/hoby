<?php

namespace App\Models;

use App\Classes\Payments\Interfaces\IContractSchedule;
use App\Classes\Payments\PaymentException;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ContractPaymentsSchedule extends Model implements IContractSchedule
{

    public const STATUS_PAID = 1;
    public const STATUS_UNPAID = 0;

    protected $table = 'contract_payments_schedule';

    public function setPaymentDateAttribute($value){
        if (is_numeric($value)) {
            $value = date("Y-m-d H:i:s", $value);
        }
        $this->attributes['payment_date'] = $value;
    }

    public function setPaidAtAttribute($value){
        $this->attributes['paid_at'] = date("Y-m-d H:i:s", $value);
    }

    public function getDateAttribute(){
        return Carbon::parse( $this->attributes['payment_date'] )->format( 'd.m.Y' );
    }

    public function getPaymentDateAttribute(){
        return Carbon::parse( $this->attributes['payment_date'] )->format( 'd.m.Y H:i:s' );
    }

    public function getStatusDateAttribute(){
        return Carbon::parse( $this->attributes['paid_at'] )->format( 'd.m.Y | H:i' );
    }

    public function contract() {
        return $this->belongsTo(Contract::class);
    }

    public function buyer() {
        return $this->belongsTo(Buyer::class, 'user_id');
    }

    public function getID(): int
    {
        return $this->id;
    }

    public function debt(): float
    {
        return $this->balance;
    }

    public function pay(float $amount)
    {
        if ($amount <= $this->balance) {
            $this->paid_at = time();
            $this->balance -= $amount;
            $this->balance = round($this->balance, 2);
            if ($this->balance == 0) {
                $this->status = 1;
            }
            $this->save();
        } else {
            throw new PaymentException("Оплачиваемая сумма больше долговой суммы месячного плана контракта",
                [
                    'ID Контракта' => $this->contract->id,
                    'ID Месячного плана контракта' => $this->id,
                    'Оплата' => $amount,
                    'Долг' => $this->balance
                ]);
        }
    }

}
