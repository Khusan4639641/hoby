<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Letter extends Model
{
    protected $table = 'letters';
    public const LETTER_TYPE_RESIDENCY = 'letter-to-residency';
    public const LETTER_TYPE_RESIDENCY_2 = 'letter-to-residency-2';

    protected $fillable = [
        'contract_id',
        'buyer_id',
        'receiver',
        'address',
        'region',
        'area',
        'response'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'amounts' => 'array',
        'response' => 'array',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function sender()
    {
        return $this->belongsTo(Employee::class, 'sender_id');
    }

    public function debtor()
    {
        return $this->belongsTo(Buyer::class, 'buyer_id');
    }

    public function region()
    {
        return $this->belongsTo(PostalRegion::class, 'region', 'external_id');
    }

    public function area()
    {
        return $this->belongsTo(PostalArea::class, 'area', 'external_id');
    }
}
