<?php

namespace App\Models\DebtCollect;

use App\Scopes\DebtCollectCuratorExtendedScope;

class DebtCollectCuratorExtended extends BaseDebtCollectCurator
{
    protected static function booted()
    {
        static::addGlobalScope(new DebtCollectCuratorExtendedScope());
    }

    // TODO: if there are any New Extended privileges/permissions for only DebtCollectCuratorExtended
}
