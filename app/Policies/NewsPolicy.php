<?php

namespace App\Policies;

use App\Models\News;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NewsPolicy
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
        if($user->hasPermission('add-news') )
            return true;
        return false;
    }

    public function delete(User $user, News $news) {
        if($user->hasPermission('delete-news') )
            return true;
        return false;
    }

    public function modify(User $user, News $news) {
        if($user->hasPermission('modify-news') )
            return true;
        return false;
    }

    public function detail(User $user, News $news) {
        return true;
    }

}
