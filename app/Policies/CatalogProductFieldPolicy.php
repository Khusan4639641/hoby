<?php


namespace App\Policies;


use App\Models\CatalogProduct;
use App\Models\CatalogProductField;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Route;

class CatalogProductFieldPolicy {

    use HandlesAuthorization;

    private $section;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct() {

    }

    public function add( User $user ) {
        if ( $user->hasPermission( 'add-product-field' ) ) {
            return true;
        }

        return false;
    }

    public function modify( User $user, CatalogProductField $field ) {
        if ( $user->hasPermission( 'modify-product-field' ) ) {
            return true;
        }

        return false;
    }

    public function delete( User $user, CatalogProductField $field ) {

        if ( $user->hasPermission( 'delete-product-field' ) ) {
            return true;
        }

        return false;
    }

}
