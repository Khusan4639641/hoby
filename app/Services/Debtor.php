<?php

namespace App\Services;

use App\Models\Buyer;
use App\Models\Contract;
use App\Models\ContractPaymentsSchedule;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Debtor
{

    const PARTIAL_EXPIRED_DAYS = 'PARTIAL_EXPIRED_DAYS';
    const FULL_EXPIRED_DAYS = 'FULL_EXPIRED_DAYS';

    const IS_PARTIAL_DEBTOR = 0;
    const IS_FULL_DEBTOR = 1;

    private $partialExpiredDays;
    private $fullExpiredDays;

    public function __construct()
    {
        $this->partialExpiredDays = Setting::getParam(static::PARTIAL_EXPIRED_DAYS);
        $this->fullExpiredDays = Setting::getParam(static::FULL_EXPIRED_DAYS);
    }

    public function getPartialExpiredDays()
    {
        return $this->partialExpiredDays;
    }

    public function getFullExpiredDays()
    {
        return $this->fullExpiredDays;
    }

    private function isContractExpired(Contract $contract, int $overdueDays): bool
    {
        return $contract->expired_days > $overdueDays;
    }

    public function isContractPartialExpired(Contract $contract): bool
    {
        return $this->isContractExpired($contract, $this->partialExpiredDays);
    }

    public function isContractFullExpired(Contract $contract): bool
    {
        return $this->isContractExpired($contract, $this->fullExpiredDays);
    }

    public function updateContractsExpiredDays()
    {
        /* Статусы контракта по которым начисляются дни задолженности
         * 1 - активный
         * 3 - полная задолженность
         * 4 - частичная задолженность
         * */

        $curDate = Carbon::now()->format('Y-m-d H:i:s');
        $diffDaysSelect = 'DATEDIFF(STR_TO_DATE(\'' . $curDate . '\', \'%Y-%m-%d %H:%i:%s\'), MIN(payment_date))';
        $overdueContracts = Contract::query()
            ->select('id')
            ->selectSub(
                ContractPaymentsSchedule::query()
                    ->selectRaw('IF(' . $diffDaysSelect . ' IS NOT NULL, ' . $diffDaysSelect . ', 0)')
                    ->whereColumn('contract_id', 'contracts.id')
                    ->whereRaw('status = 0')
                    ->whereRaw('payment_date < STR_TO_DATE(\'' . $curDate . '\', \'%Y-%m-%d %H:%i:%s\')')
                    ->getQuery(),
                'calculated_expired_days'
            )
            ->whereRaw('contracts.status IN(' . implode(', ', [
                    Contract::STATUS_ACTIVE,
                    Contract::STATUS_OVERDUE_30_DAYS,
                    Contract::STATUS_OVERDUE_60_DAYS,
                ]) . ')');
        $update = DB::table('contracts')
            ->join(DB::raw("({$overdueContracts->toSql()}) AS c"),
                function ($join) {
                    $join->on('contracts.id', '=', 'c.id');
                })
            ->addBinding($overdueContracts->getBindings());
        $update->update(['contracts.expired_days' => DB::raw("`c`.`calculated_expired_days`")]);
        return $update;
    }

    private function updateOverdueContractsStatus($expiredDays, $status)
    {
        /* Статусы контрактов у которых меняется статус на указанный в $status
         * 1 - активный
         * 3 - полная задолженность
         * 4 - частичная задолженность
         * */

        $overdueContracts = Contract::query()
            ->select('id')
            ->whereRaw('contracts.expired_days >= ' . $expiredDays)
            ->whereRaw('contracts.status IN(' . implode(', ', [
                    Contract::STATUS_ACTIVE,
                    Contract::STATUS_OVERDUE_30_DAYS,
                    Contract::STATUS_OVERDUE_60_DAYS,
                ]) . ')');
        $update = DB::table('contracts')
            ->join(DB::raw("({$overdueContracts->toSql()}) AS c"),
                function ($join) {
                    $join->on('contracts.id', '=', 'c.id');
                })
            ->addBinding($overdueContracts->getBindings());
        $update->update(['contracts.status' => $status]);
        if ($status == Contract::STATUS_OVERDUE_30_DAYS){
            Contract::query()
                ->select('id')
                ->whereRaw('contracts.expired_days = ' . $expiredDays)
                ->whereRaw('contracts.status IN(' . implode(', ', [
                        Contract::STATUS_ACTIVE,
                        Contract::STATUS_OVERDUE_30_DAYS
                    ]) . ')')->update(['expired_at' => Carbon::now()]);
        }
        return $update;
    }

    public function updateClosedPaymentsContractsStatus()
    {
        /*
         * Контракты имеющие 0 дней задолженности и
         * статус 4 (частичная задолженность)
         * переходят в статус (активный)
         * */

        $overdueContracts = Contract::query()
            ->select('id')
            ->whereRaw('contracts.expired_days = 0')
            ->whereRaw('contracts.status = ' . Contract::STATUS_OVERDUE_30_DAYS);
        $update = DB::table('contracts')
            ->join(DB::raw("({$overdueContracts->toSql()}) AS c"),
                function ($join) {
                    $join->on('contracts.id', '=', 'c.id');
                })
            ->addBinding($overdueContracts->getBindings());
        $update->update(['contracts.status' => Contract::STATUS_ACTIVE]);
        return $update;
    }

    public function updatePartialOverdueContractsStatus()
    {
        return $this->updateOverdueContractsStatus($this->partialExpiredDays, Contract::STATUS_OVERDUE_30_DAYS);
    }

    public function updateFullOverdueContractsStatus()
    {
        return $this->updateOverdueContractsStatus($this->fullExpiredDays, Contract::STATUS_OVERDUE_60_DAYS);
    }

    public function addOverdueToBlackList()
    {
        /*
         * Помещение покупателей имеющих кортакты со статусом 3
         * (полная задолженность) в чёрный список
         * */

        $overdueContracts = Contract::query()
            ->select('user_id')
            ->whereRaw('contracts.status = ' . Contract::STATUS_OVERDUE_60_DAYS);
        $update = DB::table('users')
            ->join(DB::raw("({$overdueContracts->toSql()}) AS contracts"),
                function ($join) {
                    $join->on('contracts.user_id', '=', 'users.id');
                })
            ->addBinding($overdueContracts->getBindings());
        $update->update(['users.black_list' => Buyer::IN_BLACK_LIST]);
        return $update;
    }

    public function overdueContracts($expiredDays, $recovery, $type = null)
    {
        $query = Contract::query();

        // 22.04.2022 is_array conditions were added
        if(is_array($recovery))
            $query->whereIn('contracts.recovery', $recovery);
        else
            $query->where('contracts.recovery', $recovery);

        if ($recovery != 7 && !is_array($recovery)) {

            $query->where(function ($query) use ($type, $expiredDays) {
                if ($type == static::IS_PARTIAL_DEBTOR) {
                    $query->orWhere(function ($query) use ($expiredDays) {
                        $query->where('contracts.expired_days', '>', 0)
                            ->where('contracts.expired_days', '<=', $expiredDays);
                    });
                } else if ($type == static::IS_FULL_DEBTOR) {
                    $query->orWhere('contracts.expired_days', '>', $expiredDays);
                }
            })
                ->whereIn('contracts.status', [Contract::STATUS_ACTIVE, Contract::STATUS_OVERDUE_30_DAYS, Contract::STATUS_OVERDUE_60_DAYS]);
        } else {
            $query->where('contracts.status', Contract::STATUS_COMPLETED);
        }
        return $query;
    }

    public function debtorsByContractsExpiredDays($expiredDays)
    {
        return Buyer::query()
            ->whereHas('contracts', function ($query) use ($expiredDays) {
                $query->where('expired_days', '>', $expiredDays);
            });
    }

}
