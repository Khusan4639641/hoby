<?php

namespace App\Policies;

use App\Models\Faq;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FaqPolicy
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
        if($user->hasPermission('add-faq') )
            return true;
        return false;
    }

    public function delete(User $user, Faq $faq) {
        if($user->hasPermission('delete-faq') )
            return true;
        return false;
    }

    public function modify(User $user, Faq $faq) {
        if($user->hasPermission('modify-faq') )
            return true;
        return false;
    }

    public function detail(User $user, Faq $faq) {
        return true;
    }

}
