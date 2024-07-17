<?php

namespace App\Models;

use App\Classes\Payments\Interfaces\IContract;
use App\Classes\Payments\Interfaces\IContractSchedule;
use App\Classes\Payments\PaymentException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use App\Models\NotarySetting;
use App\Models\CollectCost;

class Contract extends Model implements IContract
{
    const RECOVERY_TYPE_CALL = 0;
    const RECOVERY_TYPE_CALL_WAIT = 1;
    const RECOVERY_TYPE_LETTER = 2;
    const RECOVERY_TYPE_LETTER_WAIT = 3;
    const RECOVERY_TYPE_NOTARIUS = 4;
    const RECOVERY_TYPE_MIB = 5;
    const RECOVERY_TYPE_CONTROL = 6;
    const RECOVERY_TYPE_COMPLETE = 7;

//    договор на модерации (ожидание смс на подтверждение)
    const STATUS_AWAIT_SMS = 0;
//    оформлен
    const STATUS_ACTIVE = 1;
//    договор на модерации (ожидание подтверждение от вендора)
    const STATUS_AWAIT_VENDOR = 2;
//    просрочка больше 60 дней
    const STATUS_OVERDUE_60_DAYS = 3;
//    просрочка больше 30 дней
    const STATUS_OVERDUE_30_DAYS = 4;
//    отменён
    const STATUS_CANCELED = 5;
//    закрыт
    const STATUS_COMPLETED = 9;
//    договор на модерации  (подтверждение от сотрудников)
    const STATUS_MODERATION_1 = 10;
//    договор на модерации  (подтверждение от сотрудников)
    const STATUS_MODERATION_2 = 11;
//    договор на модерации  (подтверждение от сотрудников)
    const STATUS_MODERATION_3 = 12;
//    договор на модерации  (подтверждение от сотрудников)
    const STATUS_MODERATION_4 = 13;

    const CANCELLATION_STATUS_SENT = 1; // Запрос на отмену отправлен
    const CANCELLATION_STATUS_DENIED = 2; // Запрос на отмену отклонен
    const CANCELLATION_STATUS_ACCEPTED = 3; // Запрос на отмену принят, контракт отменен

    // Верификация товаров в заказе
    const VERIFIED = 1;
    const NOT_VERIFIED = 0;

    protected $fillable = [
        'user_id',
        'company_id',
        'partner_id',
        'order_id',
        'deposit',
        'total',
        'balance',
        'period',
        'status',
        'recovery',
        'cancel_act_status',
        'cancel_reason',
        'canceled_at',
        'act_status',
        'imei_status',
        'client_status',
        'prefix_act',
        'offer_preview',
        'confirmation_code',
        'confirmed_at',
        'date_recovery_start',
        'doc_path',
        'is_allowed_online_signature',
        'cancellation_status',
        'expired_days',
        'general_company_id',
        'verified',
    ];

    protected $appends = ['status_caption'];

    //public $incrementing = false;

    public function katmReceivedReport()
    {
        return $this->hasMany(KatmReceivedReport::class);
    }

    public function katmClaim()
    {
        return $this->hasOne(KatmClaim::class);
    }

    public function accountingEntries(): HasMany
    {
        return $this->hasMany(AccountingEntry::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function katmReport(): HasMany
    {
        return $this->hasMany(KatmReport::class);
    }

    public function getConfirmedAtAttribute()
    {
        return Carbon::parse($this->attributes['confirmed_at'])->format('d.m.Y');
    }

    public function getCreatedAtAttribute()
    {
        return Carbon::parse($this->attributes['created_at'])->format('d.m.Y H:i:s');
    }

    public function getCreatedAtDateAttribute()
    {
        return Carbon::parse($this->attributes['created_at'])->format('d.m.Y');
    }

    public function getStatusCaptionAttribute()
    {
        return __('contract.status_' . $this->status);
    }

    public function buyer()
    {
        return $this->belongsTo(Buyer::class, 'user_id');
    }

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }


    /*public function insurance() {
        return $this->hasOne(ContractInsurance::class);
    }*/

    public function lawsuit()
    {
        return $this->hasOne(ContractLawsuit::class);
    }

    // отмененный договор
    public function cancel()
    {
        return $this->hasOne(CancelContract::class); //->orderBy('canceled_at');
    }

    // задолженность - просрочка
    public function debts()
    {
        return $this->hasMany(ContractPaymentsSchedule::class)
            ->where('status', ContractPaymentsSchedule::STATUS_UNPAID)
            ->where('payment_date', '<', Carbon::now()->format("Y-m-d 23:59:59"))
            ->orderBy('payment_date');
    }

    // задолженность - просрочка
    public function debtsLast()
    {
        return $this->hasOne(ContractPaymentsSchedule::class)
            ->where('status', ContractPaymentsSchedule::STATUS_UNPAID)
            ->where('payment_date', '<', Carbon::now()->format("Y-m-d 23:59:59"))
            ->orderBy('id');
    }

    public function entries()
    {
        return $this->hasMany(ContractPaymentsSchedule::class)->where('status', 1);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function schedule()
    {
        return $this->hasMany(ContractPaymentsSchedule::class)->orderBy('payment_date');
    }

    public function schedulesOrderedByPaymentDateAndId()
    {
        return $this->hasMany(ContractPaymentsSchedule::class)->orderByRaw('payment_date, id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class)->whereIn('type', ['refund', 'auto', 'user_auto']);
    }

    public function autoDepositPayment()
    {
        return $this->hasOne(Payment::class)->where(['type' => Payment::PAYMENT_TYPE_AUTO, 'payment_system' => Payment::PAYMENT_SYSTEM_DEPOSIT]);
    }

    public function refundDepositToAccountPayment()
    {
        return $this->hasOne(Payment::class)->where(['type' => Payment::PAYMENT_TYPE_REFUND, 'payment_system' => Payment::PAYMENT_SYSTEM_ACCOUNT]);
    }

    public function activePayments()
    {
        return $this->hasMany(ContractPaymentsSchedule::class)
            ->where('status', ContractPaymentsSchedule::STATUS_UNPAID)
            ->orderBy('payment_date')
        ;
    }

    public function nextPayment()
    {
        return $this->hasOne(ContractPaymentsSchedule::class)->where('status', 0)->orderBy('payment_date');
    }

    public function price_plan()
    {
        return $this->belongsTo(AvailablePeriod::class, 'price_plan_id');
    }

    public function act()
    {
        return $this->hasOne(File::class, 'element_id', 'id')->where('type', 'act')->where('model', 'contract');
    }

    public function acts()
    {
        return $this->hasMany(File::class, 'element_id', 'id')->where('type', 'act')->where('model', 'contract');
    }

    public function imei()
    {
        return $this->hasOne(File::class, 'element_id', 'id')->where('type', 'imei')->where('model', 'contract');
    }

    public function imeis()
    {
        return $this->hasMany(File::class, 'element_id', 'id')->where('type', 'imei')->where('model', 'contract');
    }

    public function clientPhoto()
    {
        return $this->hasOne(File::class, 'element_id', 'id')->where('type', 'client_photo')->where('model', 'contract');
    }

    public function clientPhotos()
    {
        return $this->hasMany(File::class, 'element_id', 'id')->where('type', 'client_photo')->where('model', 'contract');
    }

    public function clientAct()
    {
        return $this->hasOne(File::class, 'element_id', 'id')->where('type', 'contract_pdf')->where('model', 'contract');
    }

    public function cancelAct()
    {
        return $this->hasOne(File::class, 'element_id', 'id')->where(function($query){
            $query->where('type','cancel_act')->orWhere('type',File::TYPE_IMAGE);
        })->where('model', 'contract')->orderBy('id', 'DESC');
    }

    public function signature()
    {
        return $this->hasOne(File::class, 'element_id', 'id')->where('type', File::TYPE_SIGNATURE)->where('model', 'contract')->orderBy('id', 'DESC');
    }

    public function signedContract()
    {
        return $this->hasOne(File::class, 'element_id', 'id')->where('type', File::TYPE_SIGNED_CONTRACT)->where('model', 'contract')->orderBy('id', 'DESC');
    }

    public function royxat()
    {
        return $this->hasOne(RoyxatContractsHistory::class, 'contract_id', 'id')->where('contract_status', '!=', RoyxatController::CREDIT_STATUS_CLOSED);
    }

    public function recoveries()
    {
        return $this->hasMany(ContractRecovery::class, 'contract_id', 'id');
    }

    public function recover()
    {
        return $this->hasOne(ContractRecovery::class, 'contract_id', 'id')->orderBy('created_at', 'DESC');
    }

    public function generalCompany()
    {
        return $this->belongsTo(GeneralCompany::class);
    }

    // Договор Расхода списания для текущего договора
    public function collcost()
    {
        return $this->hasOne(CollectCost::class, 'contract_id', 'id');
    }

    public function notarysetting() {
        // return $this->collcost->notary();
        return $this->hasOneThrough(
            NotarySetting::class,
            CollectCost::class,
            'contract_id', // Foreign key on CollectCost table...
            'id', // Foreign key on NotarySetting table...
            'id', // Local key on Contract table...
            'notary_id' // Local key on CollectCost table...
        );
    }

    public function contractCompanySetting()
    {
        return $this->belongsTo(PartnerSetting::class, 'company_id', 'company_id');
    }

    public function autopay_history()
    {
        return $this->hasMany(AutopayDebitHistory::class, 'contract_id');
    }

    public function not_paid_autopay_history()
    {
        return $this->hasMany(AutopayDebitHistory::class, 'contract_id')
            ->where('status', AutopayDebitHistory::STATUS_NOT_PAID)
        ;
    }

    public function letters()
    {
        return $this->hasMany(Letter::class, 'contract_id', 'id');
    }

    // если текущий договор является  Договором Расхода списания
    /*public function InCollcost(){
        return $this->hasOne(CollectCost::class, 'contract_cost_id', 'id');
    }*/

    //  dev_nurlan
    public function invoices() {
        return $this->hasMany(ContractInvoice::class, 'contract_id', 'id');
    }

    public function url(): HasOne
    {     // now deprecated because uzTaxUrl()
        return $this->hasOne(ContractUrl::class, 'contract_id', 'id');
    }

    public function uzTaxUrl(): HasOne
    {
        return $this->hasOne(UzTax::class, 'contract_id', 'id')
            ->where('payment_id', 0)
            ->where('status', UzTax::ACCEPT)
            ->where('type', UzTax::RECEIPT_TYPE_SELL);
    }

    public function contractMfoStatus() : HasOne
    {
        return $this->hasOne(ContractStatus::class,'contract_id','id')->where('type','=',ContractStatus::CONTRACT_TYPE_MFO);
    }

    public static $withoutAppends = false;

    protected function getArrayableAppends()
    {
        if (self::$withoutAppends) {
            return [];
        }
        return parent::getArrayableAppends();
    }

    public function getHasCollectorAttribute()
    {
        return count($this->collectors) > 0;
    }


    // $contract->delayDays,
    public function getDelayDaysAttribute()
    {
        $res = 0;
        $today = Carbon::now();
        if (!$this->debts) return 0;
        //return count($this->debts);
        // сумма всех месяцев просрочки
        foreach ($this->debts as $debt) {
            //  return $debt->payment_date;
            $paymentDay = new Carbon($debt->payment_date);
            if ($paymentDay < $today) {
                $res = $paymentDay->diffInDays($today);
                return $res;
            }
            //break; // учесть все месяцы задолженности, не только предстоящий

        }
        return $res;
    }

    public function getDelaySumAttribute()
    {
        $res = 0;
        foreach ($this->debts as $debt) {
            $res += $debt->balance;
        }
        return $res;
    }

    public function getRecoverySumAttribute()
    {
        $total_collect_cost = 0;
        $collect_costs = $this->collcost()->where('status', 0)->get();

        foreach ($collect_costs as $collect_cost) {
            $total_collect_cost += $collect_cost->amount;
        }
        return $this->delay_sum + $total_collect_cost;
    }

    public function getDebtSumAttribute() {
        return $this->debts()->sum('balance')
            + $this->collcost()->where('status', 0)->sum('balance')
            + $this->autopay_history()->where('status', 0)->sum('balance');
    }

    public function getPaymentSumAttribute()
    {
        $res = 0;
        foreach ($this->payments as $item) {
            if ($item->payment_system == 'DEPOSIT') continue;
            if ($item->type == 'refund' && !in_array($item->payment_system, ['UZCARD', 'HUMO'])) continue;
            $res += $item->amount;
        }
        return $res;
    }

    public function debt(): float
    {
        $debt = 0;
        $schedules = $this->schedule;
        foreach ($schedules as $schedule) {
            $debt += $schedule->balance;
        }
        return $debt;
    }

    public function getID(): int
    {
        return $this->id;
    }

    /**
     * @throws PaymentException
     */
    public function pay(float $amount)
    {
        if ($amount <= $this->balance) {
            $this->balance -= $amount;
            $this->balance = round($this->balance, 2);
            if ($this->balance == 0) {
                $this->status = Contract::STATUS_COMPLETED;
            }
            $this->save();
        } else {
            throw new PaymentException("Оплачиваемая сумма больше долговой суммы контракта",
                [
                    'ID Контракта' => $this->id,
                    'Оплата' => $amount,
                    'Долг' => $this->balance
                ]);
        }
    }

    public function unpaidSchedule(): IContractSchedule
    {
        $contractSchedule = null;
        $schedules = $this->schedule;
        foreach ($schedules as $schedule) {
            if ($schedule->status == ContractPaymentsSchedule::STATUS_UNPAID) {
                $contractSchedule = $schedule;
                break;
            }
        }
        return $contractSchedule;
    }

    public function unpaidScheduleCount(): int
    {
        return $this->schedule()
            ->where('status', ContractPaymentsSchedule::STATUS_UNPAID)
            ->count();
    }

    /**
     * @throws PaymentException
     */
    public function getOrderID(): int
    {
        if ($this->order) {
            $orderID = $this->order->id;
        } else {
            throw new PaymentException("У контракта отсутствует договорs",
                [
                    'ID Контракта' => $this->id,
                ]);
        }
        return $orderID;
    }

    public function isValid(): bool
    {
        $validStatuses = [Contract::STATUS_ACTIVE, Contract::STATUS_OVERDUE_30_DAYS, Contract::STATUS_OVERDUE_60_DAYS];
        return in_array($this->status, $validStatuses);
    }

    public function orderProducts()
    {
        return $this->hasMany(OrderProduct::class, 'order_id', 'order_id')->with(['category']);
    }
    public function uzTaxError()
    {
        return $this->hasOne(UzTaxError::class, 'contract_id');
    }

    public function uzTaxSellError()
    {
        return $this->hasOne(UzTaxError::class, 'contract_id')->where('json_data->ReceiptType', UzTax::RECEIPT_TYPE_SELL);
    }
    public function uzTaxPrepaidError()
    {
        return $this->hasOne(UzTaxError::class, 'contract_id')->where('json_data->ReceiptType', UzTax::RECEIPT_TYPE_PREPAID);
    }
    public function uzTaxCredit()
    {
        return $this->hasOne(UzTax::class, 'contract_id')->where('type', UzTax::RECEIPT_TYPE_CREDIT);
    }
}
