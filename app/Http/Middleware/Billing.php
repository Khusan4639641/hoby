<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use MongoDB\Driver\Session;

class Billing
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = Auth::user();

        if($user->hasRole('employee'))
            return redirect("panel");
        elseif($user->hasRole('buyer'))
            return redirect('cabinet');

        return $next( $request );
    }
}
