<?php

namespace App\Facades\KATM;

use App\Models\Contract;
use Illuminate\Support\Facades\Facade;

/**
 * @see App\Services\KATM\CollectReportsToKatmService::regLoanAppAndSend
 * @method static void regLoanAppAndSend(Contract $contract)
 * @see App\Services\KATM\CollectReportsToKatmService::regStartReport
 * @method static void regStartReport(Contract $contract)
 * @see App\Services\KATM\CollectReportsToKatmService::regStartStatusReport
 * @method static void regStartStatusReport(Contract $contract, string $token)
 * @see App\Services\KATM\CollectReportsToKatmService::process
 * @method static void process($date = null)
 * @see App\Services\KATM\CollectReportsToKatmService::processSingle
 * @method static void processSingle(Contract $contract, $date)
 * @see App\Services\KATM\CollectReportsToKatmService::cancelContract
 * @method static void cancelContract(Contract $contract)
 * @see App\Services\KATM\CollectReportsToKatmService::makeDailyContractReports
 * @method static void makeDailyContractReports(Contract $contract, $date)
 */
class RepKatm extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'repKatm';
    }
}
