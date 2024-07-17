<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OverdueLoanQuality extends Model
{
    protected $table = "overdue_loan_quality_class";

    protected $fillable = [
                            "name",
                            "possible_losses",
                            "expiry_days_from",
                            "expiry_days_to"
                        ];
}
