<?php

namespace App\Policies;

use App\Models\Buyer;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BuyerPolicy {

    use HandlesAuthorization;

    /**
     * @param User $user
     * @return bool
     */
    public function add( User $user ) {
        if ( $user->hasPermission( 'add-buyer' ) )
            return true;

        return false;
    }

    /**
     * @param User $user
     * @param Buyer $buyer
     * @return bool
     */
    public function delete( User $user, Buyer $buyer ) {
        if ( $user->hasPermission( 'delete-buyer' ) )
            return true;

        return false;
    }


    /**
     * @param User $user
     * @param Buyer $buyer
     * @return bool
     */
    public function modify( User $user, Buyer $buyer ) {
        if ( $user->hasPermission( 'modify-buyer' ) ||
            $user->id === $buyer->id
        )
            return true;

        return false;
    }


    /**
     * @param User $user
     * @param Buyer $buyer
     * @return bool
     */
    public function detail( User $user, Buyer $buyer ) {
        if (
            (
                $user->hasPermission( 'detail-buyer' ) &&
                (
                    $buyer->status == 4 || $buyer->created_by == $user->id
                )
            ) ||
            $user->hasPermission( 'modify-buyer' ) ||
            $user->id = $buyer->id
        )
            return true;

        return false;
    }
}
