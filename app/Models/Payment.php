<?php

namespace App\Models;

use App\Classes\Payments\Interfaces\ITransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Payment extends Model implements ITransaction
{
    const PAYMENT_SYSTEM_PAYCOIN = 'Paycoin';
    const PAYMENT_SYSTEM_CLICK = 'OCLICK';
    const PAYMENT_SYSTEM_PAYME = 'PAYME';
    const PAYMENT_SYSTEM_APELSIN = 'APELSIN';
    const PAYMENT_SYSTEM_PAYNET = 'PAYNET';
    const PAYMENT_SYSTEM_DEPOSIT = 'DEPOSIT';
    const PAYMENT_SYSTEM_ACCOUNT = 'ACCOUNT';
    const PAYMENT_REQUEST_TYPE_ACCOUNT = 'account';

    const PAYMENT_TYPE_FILL_ACCOUNT = 'fill';
    const PAYMENT_TYPE_PAY = 'upay';
    const PAYMENT_TYPE_REFUND = 'refund';
    const PAYMENT_TYPE_AUTO = 'auto';
    const PAYMENT_TYPE_USER_AUTO = 'user_auto';

    const PAYMENT_STATUS_ACTIVE = 1;

    protected $appends = ['receipt_type'];

    protected $fillable = [
        'uuid',
        'amount',
        'type',
        'payment_system',
        'status',
        'state',
        'reason',
        'created_at',
        'create_at',
        'perform_time',
        'updated_at',
        'cancel_at',
        'perform_at',
    ];

    public function getCreatedAtAttribute() {
        return Carbon::parse( $this->attributes['created_at'] )->format( 'Y-m-d H:i:s' );
    }

    public function buyer() {
        return $this->belongsTo(Buyer::class, 'user_id');
    }

    // для отчета payments платежки
    public function paymentSystem() {
        return $this->hasMany(Payment::class, 'user_id','user_id')->where('type','user');
    }

    public function contract() {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function order() {
        return $this->belongsTo(Order::class, 'order_id');
    }
    public function schedule() {
        return $this->hasOne(ContractPaymentsSchedule::class, 'id','schedule_id');
    }

    public function getReceiptTypeAttribute() {
        $str = "";
        switch ($this->attributes['type']){
            case "user":
                $str = __('panel/finance.buyer');
                break;

            case "insurance":
                $str = __('panel/finance.insurance');
                break;

            case "supplier":
                $str = __('panel/finance.partner');
                break;

            case "auto":
                $str = __('panel/finance.auto');
                break;
        }
        return $str;
    }

    public function log(){
        return $this->hasOne(PaymentLog::class, 'payment_id', 'id');
    }

    public function createTransaction(int    $userID,
                             float  $amount,
                             string $paymentType,
                             string $paymentSystem,
                             int    $status,
                             int    $contractID = null,
                             int    $orderID = null,
                             int    $scheduleID = null,
                             int    $cardID = null,
                             string $transactionID = null,
                             string $uuid = null,
                             int    $state = null,
                             int    $reason = null)
    {
        $this->contract_id = $contractID;
        $this->order_id = $orderID;
        $this->user_id = $userID;
        $this->schedule_id = $scheduleID;
        $this->card_id = $cardID;
        $this->transaction_id = $transactionID;
        $this->uuid = $uuid;
        $this->amount = $amount;
        $this->type = $paymentType;
        $this->payment_system = $paymentSystem;
        $this->status = $status;
        $this->state = $state;
        $this->reason = $reason;
    }

    public function executeTransaction()
    {
        $this->save();
    }

}
