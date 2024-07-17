<?php

namespace App\Http\Middleware;

use Closure;

class MLAccess
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
        $token = $request->bearerToken();
        if ($token === config('test.ml.token')) {
            return $next($request);
        }
        return response([], 403);
    }
}
