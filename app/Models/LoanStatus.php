<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanStatus extends Model
{
    protected $table = "loan_status";

    protected $fillable = ["type"];

    protected $hidden   = ['created_at', 'updated_at'];
}
