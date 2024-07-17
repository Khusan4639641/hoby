<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class CollectCost extends Model {

    public $table = 'collect_cost';

    protected $fillable = ['user_id', 'contract_id', 'fix', 'persent', 'amount', 'balance',
                          'total_amount', 'notary_id', 'status', 'contract_cost_id', 'exp_days'];

    public function getCreatedAtAttribute() {
        return Carbon::parse( $this->attributes['created_at'] )->format( 'd.m.Y' );
    }

    // связывающий контракт и Расходы взыскания
    public function costContract() {
        return $this->hasOne(Contract::class, 'id', 'contract_cost_id');
    }

    // контракт родитель
    public function contract() {
        return $this->belongsTo(Contract::class, 'contract_id', 'id');
    }

    // график платежей договора Расходы взыскания
    public function schedule() {
        return $this->hasMany(ContractPaymentsSchedule::class,'contract_id', 'contract_cost_id');
    }

    public function notary() {
        return $this->hasOne(NotarySetting::class,'id', 'notary_id');
    }

}
