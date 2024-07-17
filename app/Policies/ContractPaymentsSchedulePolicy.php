<?php

namespace App\Policies;

use App\Models\ContractPaymentsSchedule;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContractPaymentsSchedulePolicy {
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct() {
        //
    }

    public function detail( User $user, ContractPaymentsSchedule $contractPaymentsSchedule ) {
        if(
            $user->hasRole('employee') ||
            ($user->hasRole('buyer') && $user->owns($contractPaymentsSchedule))
        )
            return true;
        return false;
    }
}
