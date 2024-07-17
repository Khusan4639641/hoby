<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Exceptions\HttpResponseException;

class DeltaAuthMiddleware
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
        $AUTH_USER = config('test.delta_login');
        $AUTH_PASS = config('test.delta_password');

        if ($request->getUser() !== $AUTH_USER || $request->getPassword() !== $AUTH_PASS){
            return response()->json([
                'message' => 'Access denied'
            ],403);
        }

        return $next($request);
    }
}
