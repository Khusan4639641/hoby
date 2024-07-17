<?php

namespace App\Policies;

use App\Models\File;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FilePolicy
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


    public function add( User $user) {

        if (
            $user->hasPermission( 'add-file' )
        ) {
            return true;
        }

        return false;
    }


    public function detail( User $user, File $file ) {

        if (
            (
                $user->owns( $file, 'user_id' ) &&
                $user->hasPermission( 'detail-file' )
            ) ||
            $user->hasRole( 'employee' )
            ||
            $user->hasRole( 'admin' )
        ) {
            return true;
        }

        return false;
    }

    //TODO: Если КУС заливает файлы за покупателя, то покупатель уже не сможет их удалить

    public function delete( User $user, File $file ) {
        if (
            (
                $user->hasRole('buyer') &&
                $user->hasPermission( 'delete-file' )
            ) ||
            (
                $user->owns( $file, 'user_id' ) &&
                $user->hasPermission( 'delete-file' )
            ) ||
            $user->hasRole( 'employee' )
            ||
            $user->hasRole( 'admin' )
        ) {
            return true;
        }

        return false;
    }
}
