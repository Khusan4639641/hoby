<?php

namespace App\Models\DebtCollect;

use App\Models\User;
use App\Scopes\DebtCollectAccountingScope;

class DebtCollectAccounting extends User
{
    protected static function booted()
    {
        static::addGlobalScope(new DebtCollectAccountingScope());
    }
}
