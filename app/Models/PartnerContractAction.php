<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartnerContractAction extends Model
{
    protected $fillable = [
        'company_id',
        'contract_id',
        'partner_id',
        'amount',
        'date',
        'content'
    ];

    public function contract(){
        return $this->belongsTo(Contract::class);
    }

    public function company(){
        return $this->belongsTo(Company::class);
    }
}
