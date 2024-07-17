<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractRecovery extends Model {

    public $table = 'contracts_recovery';

    public function contract(){
        return $this->hasOne(Contract::class,'id','contract_id');
    }

}
