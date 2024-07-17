<?php


namespace App\Policies;


use App\Models\CatalogProduct;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Route;

class CatalogProductPolicy {

    use HandlesAuthorization;

    private $section;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct() {

        $route = Route::currentRouteName();

        $arr = explode(".", $route);

        $this->section = $arr[0];


    }

    public function add( User $user ) {
        if ( $user->hasPermission( 'add-product' ) ) {
            return true;
        }

        return false;
    }

    public function list( User $user, CatalogProduct $product ) {

        if ( $user->hasPermission( 'detail-product' ) ) {
            return true;
        }

        return false;
    }

    public function detail( User $user, CatalogProduct $product ) {

        if($user) {
            if($this->section == 'billing'){
                if ( $user->hasPermission( 'detail-product' ) && $user->id == $product->user_id ) {
                    return true;
                } else {
                    return false;
                }
            }
            if ( $user->hasPermission( 'detail-product' ) ) {
                return true;
            }

            return false;
        }

        return true;
    }

    public function modify( User $user, CatalogProduct $product ) {

        if($this->section == 'billing'){
            if ( $user->hasPermission( 'modify-product' ) && $user->id == $product->user_id ) {
                return true;
            } else {
                return false;
            }
        }
        if ( $user->hasPermission( 'modify-product' ) ) {
            return true;
        }

        return false;
    }

    public function delete( User $user, CatalogProduct $product ) {
        if($this->section == 'billing'){
            if ( $user->hasPermission( 'delete-product' ) && $user->id == $product->user_id ) {
                return true;
            } else {
                return false;
            }
        }
        if ( $user->hasPermission( 'delete-product' ) ) {
            return true;
        }

        return false;
    }

}
