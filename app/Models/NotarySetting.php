<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Contract;
use App\Models\CollectCost;

class NotarySetting extends Model
{
    protected $fillable = [
        'name',
        'surname',
        'patronymic',
        'region',
        'address',
        'fee',
        'letter_base_unique_number',
        'template_number',
        'tax'
    ];


    public function collcost()
    {
        return $this->belongsTo(CollectCost::class, 'id', 'notary_id');
    }

    // контракт родитель (notarysetting->collcost->contract)
    public function contract() {
        // return $this->collcost->contract();
        return $this->hasOneThrough(
            Contract::class,
            CollectCost::class,
            'notary_id', // Foreign key on CollectCost table...
            'id', // Foreign key on Contract table...
            'id', // Local key on NotarySetting table...
            'contract_id' // Local key on CollectCost table...
        );
    }
}
