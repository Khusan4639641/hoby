<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Order;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderPolicy
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
        if($user->hasPermission('add-order'))
            return true;
        return false;
    }

    public function delete(User $user, Order $order) {
        if($user->hasPermission('delete-order') )
            return true;
        return false;
    }

    public function modify(User $user, Order $order) {
        if(
            $user->hasPermission('modify-order') &&
            $user->id == $order->partner_id &&
            $order->status != 5 // if not canceled
        )
            return true;
        return false;
    }

    public function detail(User $user, Order $order) {
        if(
        $user->hasPermission('detail-order') ||
            $order->partner_id == $user->id ||
            $order->user_id == $user->id
        )
            return true;
        return false;
    }

    public function detailFinance(User $user, Order $order) {
        return true;
    }
}
