<?php


namespace App\Policies;


use App\Models\CatalogCategory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CatalogCategoryPolicy {

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
        if ( $user->hasPermission( 'add-category' ) ) {
            return true;
        }

        return false;
    }

    public function detail( User $user, CatalogCategory $category ) {
        if ( $user->hasPermission( 'detail-category' ) ) {
            return true;
        }

        return false;
    }

    public function modify( User $user, CatalogCategory $category ) {
        if ( $user->hasPermission( 'modify-category' ) ) {
            return true;
        }

        return false;
    }

    public function delete( User $user, CatalogCategory $category ) {
        if ( $user->hasPermission( 'delete-category' ) ) {
            return true;
        }

        return false;
    }

}
