<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ContractUrl extends Model
{
    protected $fillable = [
        'contract_id',
        'url',
        'contract_summa',
        'contract_created_at'
    ];

    public function getContractCreatedAtAttribute(): string {
        return Carbon::parse( $this->attributes['contract_created_at'] )->format( 'd.m.Y H:i:s' );
    }

    public function getCreatedAtAttribute(): string {
        return Carbon::parse( $this->attributes['created_at'] )->format( 'd.m.Y H:i:s' );
    }

    public function getUpdatedAtAttribute(): string {
        return Carbon::parse( $this->attributes['updated_at'] )->format( 'd.m.Y H:i:s' );
    }



    public function contract() {
        return $this->belongsTo(Contract::class, 'id', 'contract_id');
    }
}
