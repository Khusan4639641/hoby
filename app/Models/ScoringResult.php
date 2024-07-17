<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScoringResult extends Model
{

    public const TYPE_ORIGINAL = 1;
    public const TYPE_MINI = 2;

    protected const ACTIVE_TIME_IN_SECOND = 300; // 5 minute

    public const STATE_NONE = NULL;
    public const STATE_USER_INFO_SUCCESS = 1;
    public const STATE_USER_INFO_NOT_SUCCESS = 2;
    public const STATE_AWAIT_RESPONSE = 3;
    public const STATE_FAILED_RESPONSE = 4;

    protected $table = 'scoring_results';

    protected $attributes = [
        'type' => ScoringResult::TYPE_ORIGINAL,
        'is_ml2_scoring' => 1
    ];

    protected $fillable = [
        'scoring_state',
        'scoring_by_tax_state',
        'debts_by_royxat_state',
        'overdue_by_infoscore_state',
        'debts_by_mib_state',
        'debts_by_katm_state',
        'write_off_check_state',
        'check_approve_state',
        'total_state',
        'final_state',
        'scoring_limit',
        'scoring_by_tax_limit',
        'expected_limit',
        'final_limit',
        'user_id',
        'katm_claim',
        'mrz',
        'pinfl',
        'passport',
        'tries_count',
        'initiator_id',
        'type',
        'attempts_limit_reached',
        'is_ml2_scoring'
    ];

    protected static function booted()
    {
        static::addGlobalScope('type', function (Builder $builder) {
            $builder->where('type', ScoringResult::TYPE_ORIGINAL);
        });
    }

    public function scopeTyped($query)
    {
        return $query->where('type', self::TYPE_ORIGINAL);
    }

    public function resetFails(): void
    {
        /*if ($this->check_approve_state === self::STATE_FAILED_RESPONSE
            || $this->check_approve_state === self::STATE_USER_INFO_NOT_SUCCESS) {
            $this->check_approve_state = self::STATE_NONE;
        }*/
        if ($this->final_state === self::STATE_FAILED_RESPONSE
            || $this->final_state === self::STATE_USER_INFO_NOT_SUCCESS) {
            $this->final_state = self::STATE_NONE;
        }
        $this->total_state = self::STATE_NONE;
        $this->tries_count = 0;
        $this->save();
    }

    public function increaseTry()
    {
        if ($this->tries_count >= config('test.max_scoring_tries')) {
            $this->totalFailed();
            return;
        }
        $this->tries_count++;
        $this->save();
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class, 'user_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiator_id');
    }

    public function data(): ScoringResult
    {
        return $this;
    }

    public function isSuccess(): bool
    {
        return ($this->total_state === self::STATE_USER_INFO_SUCCESS);
    }

    public function isResponseFailed(): bool
    {
        return ($this->total_state === self::STATE_FAILED_RESPONSE);
    }

    public function isResponseAwait(): bool
    {
        return ($this->total_state === self::STATE_AWAIT_RESPONSE);
    }

    public function isNotSuccess(): bool
    {
        return ($this->total_state === self::STATE_USER_INFO_NOT_SUCCESS);
    }

    public function isTotalExecuted(): bool
    {
        return $this->total_state === self::STATE_USER_INFO_SUCCESS ||
            $this->total_state === self::STATE_FAILED_RESPONSE ||
            $this->total_state === self::STATE_USER_INFO_NOT_SUCCESS;
    }

    public function isExecuted(): bool
    {
        return /*$this->check_approve_state === self::STATE_USER_INFO_SUCCESS
            && */$this->final_state === self::STATE_USER_INFO_SUCCESS;
    }

    public function totalSuccess(): void
    {
        $this->total_state = self::STATE_USER_INFO_SUCCESS;
        $this->save();
    }

    public function totalAwait(): void
    {
        $this->total_state = self::STATE_AWAIT_RESPONSE;
        $this->save();
    }

    public function totalFailed(): void
    {
        $this->total_state = self::STATE_FAILED_RESPONSE;
        $this->save();
    }

    public function totalNotSuccess(): void
    {
        $this->total_state = self::STATE_USER_INFO_NOT_SUCCESS;
        $this->save();
    }

    public function isFinalExecuted(): bool
    {
        return $this->final_state === self::STATE_USER_INFO_SUCCESS ||
            $this->final_state === self::STATE_FAILED_RESPONSE ||
            $this->final_state === self::STATE_USER_INFO_NOT_SUCCESS;
    }

    public function finalAwait(): void
    {
        $this->final_state = self::STATE_AWAIT_RESPONSE;
        $this->save();
        $this->totalAwait();
    }

    public function finalFailed(): void
    {
        $this->final_state = self::STATE_FAILED_RESPONSE;
        $this->save();
        $this->totalFailed();
    }

    public function finalSuccess(): void
    {
        $this->final_state = self::STATE_USER_INFO_SUCCESS;
        $this->save();
        if ($this->isExecuted()) {
            $this->totalSuccess();
        }
    }

    public function finalNotSuccess(): void
    {
        $this->final_state = self::STATE_USER_INFO_NOT_SUCCESS;
        $this->save();
        $this->totalNotSuccess();
    }

    public function dropErrorMessage()
    {
        $this->error_message = null;
        $this->save();
    }

    public function errorMessage(string $message)
    {
        $this->error_message = $message;
        $this->save();
    }

    public function issetInitiator(): bool
    {
        return $this->initiator_id != null;
    }

    public function dropInitiator()
    {
        $this->initiator_id = null;
        $this->save();
    }

    /*public function isCheckApproveExecuted(): bool
    {
        return $this->check_approve_state === self::STATE_USER_INFO_SUCCESS ||
            $this->check_approve_state === self::STATE_FAILED_RESPONSE ||
            $this->check_approve_state === self::STATE_USER_INFO_NOT_SUCCESS;
    }*/

    public function checkApproveAwait(): void
    {
        /*$this->check_approve_state = self::STATE_AWAIT_RESPONSE;
        $this->save();*/
        $this->totalAwait();
    }

    public function checkApproveFailed(): void
    {
        /*$this->check_approve_state = self::STATE_FAILED_RESPONSE;
        $this->save();*/
        $this->totalFailed();
    }

    public function checkApproveSuccess(): void
    {
        /*$this->check_approve_state = self::STATE_USER_INFO_SUCCESS;
        $this->save();*/
        if ($this->isExecuted()) {
            $this->totalSuccess();
        }
    }

    public function checkApproveNotSuccess(): void
    {
        /*$this->check_approve_state = self::STATE_USER_INFO_NOT_SUCCESS;
        $this->save();*/
        $this->totalNotSuccess();
    }

    public function isTimeOver(): bool
    {
        return Carbon::now()->subSeconds(self::ACTIVE_TIME_IN_SECOND) > Carbon::create($this->created_at);
    }

}
