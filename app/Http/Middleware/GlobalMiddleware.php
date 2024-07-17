<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class GlobalMiddleware {
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     *
     * @return mixed
     */
    public function handle( $request, Closure $next ) {
        $user = Auth::user();
        setcookie("api_token", $user->api_token, time()+config( 'session.lifetime' )*60, '/');
        return $next( $request );
    }
}
