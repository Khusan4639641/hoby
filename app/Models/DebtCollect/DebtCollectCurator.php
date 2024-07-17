<?php

namespace App\Models\DebtCollect;

use App\Scopes\DebtCollectCuratorScope;


class DebtCollectCurator extends BaseDebtCollectCurator
{
    // 27.04.2023 Nurlan:все поля и методы из этого класса были вынесены в новый отдельный родительский класс: BaseDebtCollectCurator
    // кроме скоупа, родительский скоуп перезаписываем
    protected static function booted()
    {
        static::addGlobalScope(new DebtCollectCuratorScope());
    }
}
