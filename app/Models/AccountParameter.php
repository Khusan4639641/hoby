<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountParameter extends Model
{
    const TYPE_ACTIVE = 1; //Активный
    const TYPE_INACTIVE = 0; //Пассивный

    const BALANCE_TYPE_ON = 1; //Балансовый
    const BALANCE_TYPE_OFF = 0; //Внебалансовый

    protected $fillable = [
        'name',
        'mask',
        'type',
        'balance_type',
        'contract_bind',
    ];
}
