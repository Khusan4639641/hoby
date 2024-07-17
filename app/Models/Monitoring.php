<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Monitoring extends Model
{

    const ACCOUNTS_DEBIT_AND_CREDIT_DIFFERENT = 'accounts_debit_and_credit_different';
    const CONTRACTS_DEBIT_DIFFERENT = 'contracts_debit_different';
    const BONUS_ACCOUNTS_DEBIT_AND_CREDIT_DIFFERENT = 'bonus_accounts_debit_and_credit_different';

    const CONTRACTS_ACTIVE_EXPIRED_DAYS = 'contracts_active_expired_days';
    const CONTRACTS_PARTIAL_DEBTORS_EXPIRED_DAYS = 'contracts_partial_debtors_expired_days';
    const CONTRACTS_FULL_DEBTORS_EXPIRED_DAYS = 'contracts_full_debtors_expired_days';
    const CONTRACTS_ALL_EXPIRED_DAYS = 'contracts_all_expired_days';

    const CONTRACTS_STATUS_ACTIVE = 'contracts_status_active';
    const CONTRACTS_STATUS_PARTIAL_DEBTOR = 'contracts_status_partial_debtor';
    const CONTRACTS_STATUS_FULL_DEBTOR = 'contracts_status_full_debtor';

    protected $table = 'monitoring';

    protected $fillable = [
        'title',
        'value',
    ];

    static public function addParam(string $key, string $value)
    {
        return Monitoring::create([
            'title' => $key,
            'value' => $value,
        ]);
    }

    static public function getParam(string $key)
    {
        $monitoring = Monitoring::where('title', $key)->latest()->first();
        if (!$monitoring) {
            return 0;
        }
        return $monitoring->value;
    }

    static public function getDailyParam(string $key, $date)
    {
        $monitoring = Monitoring::where('title', $key)
            ->whereRaw('DATE(created_at) <= \'' . $date . '\'')
            ->latest()
            ->first();
        if (!$monitoring) {
            return 0;
        }
        return $monitoring->value;
    }


}
