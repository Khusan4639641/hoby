<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Support\Facades\Auth;

class Guest {
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param string|null $guard
     *
     * @return mixed
     */
    public function handle( $request, Closure $next, $guard = null ) {

        if ( Auth::guard( $guard )->check() ) {

            $user = Auth::user();

            if($user->hasRole('buyer'))
                return redirect("cabinet");
            elseif($user->hasRole('partner'))
                return redirect('billing');

            return redirect("panel");
        }

        return $next( $request );
    }
}
