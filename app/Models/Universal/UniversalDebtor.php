<?php

namespace App\Models\Universal;

use App\Models\Buyer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UniversalDebtor extends Model
{

    protected $fillable = [
        'universal_debit_id',
        'current_debit',
        'total_debit',
    ];

    public function autoPaymentsTransactions(): HasMany
    {
        return $this->hasMany(UniversalAutoPaymentsTransaction::class, 'debtor_id', 'id');
    }

    public function user(): HasOne
    {
        return $this->hasOne(Buyer::class, 'id', 'user_id');
    }

}
