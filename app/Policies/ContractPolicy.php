<?php

namespace App\Policies;

use App\Models\Contract;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContractPolicy {
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct() {
        //
    }


    public function add( User $user ) {
        if ( $user->hasPermission( 'add-contract' ) ) {
            return true;
        }

        return false;
    }

    public function delete( User $user, Contract $contract ) {
        if ( $user->hasPermission( 'delete-contract' ) ) {
            return true;
        }

        return false;
    }

    public function modify( User $user, Contract $contract ) {
        if ( $user->hasPermission( 'modify-contract' ) ) {
            return true;
        }

        return false;
    }

    public function detail( User $user, Contract $contract ) {
        if ( $user->hasPermission( 'detail-contract' ) ) {
            return true;
        }

        return false;
    }
}
