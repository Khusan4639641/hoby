<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class Cabinet {
    /**
     * Handle an incoming request.
     *
     * @param $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next) {
        $user = Auth::user();

        if($user->hasRole(['employee']))
            return redirect("panel");
        elseif($user->hasRole(['partner']))
            return redirect('billing');

        return $next( $request );

    }
}
