<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CompanyPolicy
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
        if($user->hasPermission('add-partner') )
            return true;
        return false;
    }

    /**
     * @param User $user
     * @param Company $company
     * @return bool
     */
    public function delete(User $user, Company $company) {
        if($user->hasPermission('delete-partner') &&
            (
                $user->hasRole('admin') ||
                $user->hasRole('sales') ||
                ($company->parent_id == $user->company_id && $company->status === 0)
            )
        )
            return true;
        return false;
    }

    /**
     * @param User $user
     * @param Company $company
     * @return bool
     */
    public function modify(User $user, Company $company) {
        if($user->hasPermission('modify-partner') &&
            (
                $user->hasRole('admin') ||
                $user->hasRole('sales') ||
                $company->id == $user->company_id ||
                ($company->parent_id == $user->company_id && $company->status === 0)
            )
        )
            return true;
        return false;
    }

    /**
     * @param User $user
     * @param Company $company
     * @return bool
     */
    public function detail(User $user, Company $company) {
        if(
            $user && $user->hasPermission('detail-partner') &&
            (
                $user->hasRole('admin') ||
                $user->hasRole('sales') ||
                $company->id == $user->company_id ||
                $company->parent_id == $user->company_id
            )
            ||$company->id === 1
        )
            return true;
        return false;
    }
}
