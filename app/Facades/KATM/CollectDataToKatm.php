<?php

namespace App\Facades\KATM;

use App\Models\Contract;
use App\Models\MfoSettings;
use Illuminate\Support\Facades\Facade;

/**
 * @see App\Services\KATM\CollectDataToKatmService::getAccountsTypes
 * @method static array getAccountsTypes()
 * @see App\Services\KATM\CollectDataToKatmService::getAccountBalance
 * @method static int getAccountBalance(Contract $contract, $accountMask)
 * @see App\Services\KATM\CollectDataToKatmService::getAccountBalanceToDate
 * @method static int getAccountBalanceToDate(Contract $contract, $accountMask, $date, bool $isBeg = true)
 * @see App\Services\KATM\CollectDataToKatmService::getSettings
 * @method static MfoSettings getSettings()
 * @see App\Services\KATM\CollectDataToKatmService::collectAccountsBalances
 * @method static array collectAccountsBalances(Contract $contract, $date, bool $allAccounts = false)
 * @see App\Services\KATM\CollectDataToKatmService::collectPayments
 * @method static array collectPayments(Contract $contract, $date)
 * @see App\Services\KATM\CollectDataToKatmService::collectAccounts
 * @method static array collectAccounts(Contract $contract)
 */
class CollectDataToKatm extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'collectDataToKatm';
    }
}
