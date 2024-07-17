<?php

namespace App\Policies;

use App\Models\Card;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CardPolicy
{
    use HandlesAuthorization;

    public function detail(User $user, Card $card) {
        if(
            $user->hasRole('employee') ||
            $user->id == $card->user_id
        )
            return true;
        return false;
    }
}
