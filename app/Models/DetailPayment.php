<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailPayment extends Model
{
    const CREATED_STATUS = '00';
    const SUCCESS_STATUS = '01';
    const ERROR_STATUS = '02';
    const CANCELED_STATUS = '03';
    protected $guarded = [];
}
