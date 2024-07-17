<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Laratrust\Traits\LaratrustUserTrait;

class Employee extends User {

    public function user() {
        return $this->hasOne(User::class, 'id', 'id');
    }
    public function kycinfo() {
        return $this->hasOne(KycInfo::class, 'user_id', 'id');
    }

}
