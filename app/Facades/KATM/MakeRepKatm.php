<?php

namespace App\Facades\KATM;

use App\Models\Contract;
use App\Models\KatmReport;
use Illuminate\Support\Facades\Facade;

/**
 * @see App\Services\KATM\MakeReportToKatmService::generatePaymentType
 * @method static KatmReport generatePaymentType($date)
 * @see App\Services\KATM\MakeReportToKatmService::report001
 * @method static KatmReport report001(Contract $contract, $reportType = KatmReport::TYPE_PRE_REGISTRATION)
 * @see App\Services\KATM\MakeReportToKatmService::reportStart
 * @method static KatmReport reportStart(Contract $contract, $reportType = KatmReport::TYPE_PRE_REGISTRATION)
 * @see App\Services\KATM\MakeReportToKatmService::report003
 * @method static KatmReport report003(Contract $contract, $reportType = KatmReport::TYPE_CANCEL)
 * @see App\Services\KATM\MakeReportToKatmService::report004
 * @method static KatmReport report004(Contract $contract, $date, $reportType = KatmReport::TYPE_REGISTRATION)
 * @see App\Services\KATM\MakeReportToKatmService::report005
 * @method static KatmReport report005(Contract $contract, $date, $reportType = KatmReport::TYPE_REGISTRATION)
 * @see App\Services\KATM\MakeReportToKatmService::report015
 * @method static KatmReport report015(Contract $contract, $date, bool $allAccounts = false, $reportType = KatmReport::TYPE_PAYMENT)
 * @see App\Services\KATM\MakeReportToKatmService::report016
 * @method static KatmReport report016(Contract $contract, $date, $reportType = KatmReport::TYPE_PAYMENT)
 * @see App\Services\KATM\MakeReportToKatmService::report018
 * @method static KatmReport report018(Contract $contract, $date, $reportType = KatmReport::TYPE_COMPLETE)
 */
class MakeRepKatm extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'makeRepKatm';
    }
}
