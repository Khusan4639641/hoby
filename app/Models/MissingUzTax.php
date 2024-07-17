<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MissingUzTax extends Model
{
    protected $table = 'missing_uz_taxes';

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }
}
