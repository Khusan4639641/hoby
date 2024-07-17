<?php

namespace App\Models;

use App\Services\MFO\AccountInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, HasOne};
use Illuminate\Support\Carbon;

class AccountCBU extends Model implements AccountInterface
{
    protected $table = 'accounts_cbu';

    const STATUS_OPEN  = 1;
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

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
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

    public function balanceHistory(): HasMany
    {
        return $this->hasMany(AccountBalanceHistoryCBU::class, 'account_id');
    }

    public function accountParameters(): HasOne
    {
        return $this->hasOne(AccountParameter::class, 'mask', 'mask');
    }

    public function debitAccountInfo(): HasMany
    {
        return $this->hasMany(AccountingEntryCBU::class, 'debit_account', 'number');
    }

    public function creditAccountInfo(): HasMany
    {
        return $this->hasMany(AccountingEntryCBU::class, 'credit_account', 'number');
    }

    /**
     * ONLY FOR REPORTS
     *
     * @return int
     */
    public function getDebitAccountSumAttribute(): int
    {
        $query = $this->debitAccountInfo
            ->whereBetween('operation_date',
                           [Carbon::parse(request()->from),
                            Carbon::parse(request()->to)->endOfDay()]);
        if (config('test.MKO_DIRTY_REPORT')) {
            $query = $query->where('destination_code', '!=', '0000');
        }

        return (int)(100 * $query->sum('amount'));
    }

    /**
     * ONLY FOR REPORTS
     *
     * @return int
     */
    public function getCreditAccountSumAttribute(): int
    {
        $query = $this->creditAccountInfo
            ->whereBetween('operation_date',
                           [Carbon::parse(request()->from),
                            Carbon::parse(request()->to)->endOfDay()]);
        if (config('test.MKO_DIRTY_REPORT')) {
            $query = $query->where('destination_code', '!=', '0000');
        }

        return (int)(100 * $query->sum('amount'));
    }

    /**
     * ONLY FOR REPORTS
     *
     * @return mixed
     */
    public function getBalanceHistoryFromAttribute(): int
    {
        if (config('test.MKO_DIRTY_REPORT')) {
            $data = 0;
        } else {
            $day  = Carbon::parse(request()->from)->subDay();
            $bH   = $this->balanceHistory()->whereDate('operation_date', $day)->first();
            $data = 0;
            if ($bH) {
                $data = (int)(100 * $bH->balance);
            }
        }

        return $data;
    }

    /**
     * ONLY FOR REPORTS
     *
     * @return mixed
     */
    public function getBalanceHistoryToAttribute()
    {
        if (config('test.MKO_DIRTY_REPORT')) {
            if ($this->accountParameters->type === 1) {
                $data = $this->balance_history_from + $this->debit_account_sum - $this->credit_account_sum;
            } else {
                $data = $this->balance_history_from - $this->debit_account_sum + $this->credit_account_sum;
            }
        } else {
            // Old logic
            $day  = Carbon::parse(request()->to)->subDay();
            $bH   = $this->balanceHistory()->whereDate('operation_date', $day)->first();
            $data = 0;
            if ($bH) {
                $data = (int)(100 * $bH->balance);
            }
        }

        return $data;
    }
}
