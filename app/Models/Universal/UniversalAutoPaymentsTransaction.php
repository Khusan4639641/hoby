<?php

namespace App\Models\Universal;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UniversalAutoPaymentsTransaction extends Model
{

    protected $fillable = [
        'universal_transaction_id',
        'universal_debit_id',
        'amount',
    ];

    public function debtor(): BelongsTo
    {
        return $this->belongsTo(UniversalDebtor::class, 'debtor_id', 'id');
    }

}
