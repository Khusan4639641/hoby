<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MyID extends Model
{

    const MY_ID_ACTIVATE_CONTRACT_USER_STATUSES = [
      User::KYC_STATUS_EDIT,
      User::KYC_STATUS_UPDATE,
      User::KYC_STATUS_VERIFY,
      User::KYC_SHOULD_ADD_GUARANT
    ];

    protected $table = 'my_id';

    protected $fillable = [
        'access_token',
        'expires_in',
        'token_type',
        'scope',
        'refresh_token',
    ];
}
