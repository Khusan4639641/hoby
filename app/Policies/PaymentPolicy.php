<?php

namespace App\Policies;

use App\Models\payment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PaymentPolicy {
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
        if ( $user->hasPermission( 'add-payment' ) ) {
            return true;
        }

        return false;
    }

    public function delete( User $user, Payment $payment ) {
        if ( $user->hasPermission( 'delete-payment' ) ) {
            return true;
        }

        return false;
    }

    public function modify( User $user, Payment $payment ) {
        if ( $user->hasPermission( 'modify-payment' ) ) {
            return true;
        }

        return false;
    }

    public function detail( User $user, Payment $payment ) {
        if ( $user->hasPermission( 'detail-payment' ) ) {
            return true;
        }

        return false;
    }
}
