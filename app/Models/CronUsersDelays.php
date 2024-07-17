<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CronUsersDelays extends Model
{
    //public $timestamps = false;

    public function cards(){
        return $this->hasMany(Card::class,'user_id','user_id'); //->where('status',1);
    }

    public function personalAccount(){
        return $this->hasOne(BuyerSetting::class,'user_id','user_id')->select('id','user_id','personal_account');
    }


}
