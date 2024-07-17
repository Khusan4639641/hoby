<?php

namespace App\Models\DebtCollect;

use App\Models\User;
use App\Scopes\DebtCollectLeaderScope;

class DebtCollectLeader extends User
{
    protected static function booted()
    {
        static::addGlobalScope(new DebtCollectLeaderScope());
    }
}
