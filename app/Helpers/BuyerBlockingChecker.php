<?php

namespace App\Helpers;

use App\Models\BannedUsers;
use App\Models\BlackList;

class BuyerBlockingChecker
{

    static public function isUserInBlackList(string $pinfl): bool
    {
        return BlackList::query()->where('token', $pinfl)->exists();
    }

    static public function isUserBanned(string $serial, string $number): bool
    {
        return BannedUsers::query()
            ->where('passport_series', $serial)
            ->where('passport_number', $number)
            ->exists();
    }

}
