<?php

namespace App\Policies;


use App\Models\Partner;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PartnerPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function detail(User $user, Partner $partner) {
        if(
            $user->hasPermission('detail-partner') ||
            $user->id == $partner->id
        )
            return true;
        return false;
    }


    public function modify(User $user, Partner $partner) {
        if(
            $user->hasPermission('modify-partner') ||
            $user->id == $partner->id
        )
            return true;
        return false;
    }
}
