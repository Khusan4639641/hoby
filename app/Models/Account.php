<?php

namespace App\Models;

use App\Services\MFO\AccountInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model implements AccountInterface
{
    protected $table = 'accounts';

    const STATUS_OPEN = 1;
    const STATUS_CLOSE = 0;

    protected $fillable = [
        'status',
        'user_id',
        'contract_id',
        'name',
        'number',
        'balance',
        'mask',
        'currency',
        'closed_at',
    ];

    protected $dates = ['closed_at'];

    public function contract() : BelongsTo
    {
        return $this->belongsTo(Contract::class,'contract_id');
    }

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function accountBalanceHistory() : HasMany
    {
        return $this->hasMany(AccountBalanceHistory::class,'account_id');
    }

    public function updateBalance(float $balance): void
    {
        $this->balance = $balance;
        $this->save();
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function getMask(): string
    {
        return $this->mask;
    }

    public function getContractId(): int
    {
        return $this->contract_id;
    }

    public function getBalance(): float
    {
        return $this->balance;
    }
}
