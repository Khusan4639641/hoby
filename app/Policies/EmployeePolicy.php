<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployeePolicy
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
        if($user->hasPermission('add-employee') )
            return true;
        return false;
    }

    public function delete(User $user, Employee $employee) {
        if($user->hasPermission('delete-employee') )
            return true;
        return false;
    }

    public function modify(User $user, Employee $employee) {
        if($user->hasPermission('modify-employee') )
            return true;
        return false;
    }

    public function detail(User $user, Employee $employee) {
        if(
            $user->hasPermission('detail-employee') ||
            $user->id == $employee->id
        )
            return true;
        return false;
    }
}
