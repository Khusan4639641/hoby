<?php

namespace App\Policies;

use App\Models\Discount;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DiscountPolicy
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


    public function add(User $user) {
        if($user->hasPermission('add-discount') )
            return true;
        return false;
    }

    public function delete(User $user, Discount $discount) {
        if($user->hasPermission('delete-discount') )
            return true;
        return false;
    }

    public function modify(User $user, Discount $discount) {
        if($user->hasPermission('modify-discount') )
            return true;
        return false;
    }

    public function detail(User $user, Discount $discount) {
        return true;
    }
}
