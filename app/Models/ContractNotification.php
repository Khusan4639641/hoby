<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin  Builder
 */

class ContractNotification extends Model
{
    const STATUS_SEND = 1;
    const STATUS_PENDING = 0;

    const TYPE_FEATURED = 'featured';
    const TYPE_EXPIRED = 'expired';
    const TYPE_CLOSED = 'closed';

    protected $fillable = [
        'id',
        'user_id',
        'contract_id',
        'title_ru',
        'title_uz',
        'message_ru',
        'message_uz',
        'status',
        'priority',
        'type',
    ];

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class,'contract_id');
    }
}
