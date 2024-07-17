<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentTerms extends Model
{
    protected $table  = "payment_terms";

    protected $fillable = [
                "period_id",
                "urgency_type",
                "urgency_interval"
            ];
}
