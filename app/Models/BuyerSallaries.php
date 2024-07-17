<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuyerSallaries extends Model
{

    public function scoringLog(){
        return $this->hasOne(CardScoringLog::class, 'user_id','user_id');
    }
    public function settings(){
        return $this->hasOne(BuyerSetting::class, 'user_id','user_id');
    }

}
