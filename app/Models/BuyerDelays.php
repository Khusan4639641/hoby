<?php

namespace App\Models;

use App\Helpers\EncryptHelper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;



class BuyerDelays extends Model {

    public $table = 'users_delays';

    public function contract() {
        return $this->hasOne(Contract::class, 'contract_id');
    }

    public function user() {
        return $this->hasOne(User::class, 'id', 'id');
    }


}
