<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Record extends Model {

    protected $table = 'records';


    public function kyc(){
        return $this->hasOne(User::class, 'id','kyc_id');
    }

    public function buyer(){
        return $this->hasOne(User::class, 'id','user_id');
    }

    public function contract(){
        return $this->hasOne(Contract::class, 'id','contract_id');
    }

}
