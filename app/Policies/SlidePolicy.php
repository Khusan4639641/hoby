<?php

namespace App\Policies;

use App\Models\Slide;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SlidePolicy
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
        if($user->hasPermission('add-slider') )
            return true;
        return false;
    }

    public function delete(User $user, Slide $slide) {
        if($user->hasPermission('delete-slider') )
            return true;
        return false;
    }

    public function modify(User $user, Slide $slide) {
        if($user->hasPermission('modify-slider') )
            return true;
        return false;
    }

    public function detail(User $user, Slide $slide) {
        return true;
    }
}
