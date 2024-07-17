<?php

namespace App\Services;

use App\Models\EdTransaction;
use Carbon\Carbon;

class EdTransactionService
{
    public static function balanceBetweenPeriod(
        Carbon $date_from,
        Carbon $date_to
    ): int
    {
        $query = EdTransaction::query()
            ->selectRaw("SUM(CASE WHEN type = 'CREDIT' THEN amount WHEN type = 'DEBIT' THEN -amount END) AS total")
            ->whereBetween('doc_time', [
                $date_from->getTimestampMs(),
                $date_to->getTimestampMs(),
            ])->first();

        return $query->total ?? 0;
    }
}
