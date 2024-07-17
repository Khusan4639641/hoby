<?php

namespace App\Policies;


use App\Models\User;
use App\Models\Saller;
use Illuminate\Auth\Access\HandlesAuthorization;

class SallerPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    public function detail(User $user, Saller $saller) {
        if(
            $user->hasRole('admin')
            /*$user->hasPermission('detail-saller') ||
            $user->id == $saller->id */
        )
            return true;
        return false;
    }


    public function modify(User $user, Saller $saller) {
        if(
            $user->hasRole('admin')
            /*$user->hasPermission('modify-saller') ||
            $user->id == $saller->id */
        )
            return true;


        return false;
    }


    public function add(User $user) {
        if(  $user->hasRole('admin') || $user->hasPermission('add-saller') )
            return true;
        return false;
    }

}
