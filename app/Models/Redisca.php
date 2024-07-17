<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Redisca extends Model {

    protected $table = 'redis';

    public static function set($hash,$value) {
        $redis = new Redisca();
        $redis->hash = $hash;
        $redis->value= $value;
        $redis-save();
    }

    public static function get($hash) {
        if($res = Redisca::where('hash',$hash)->first()){
            return $res->value;
        }
    }

}
