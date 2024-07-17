<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Crypt;

class LocaleApi
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $local = ($request->hasHeader("Content-Language")) ? $request->header("Content-Language") : config("app.fallback_locale");

        if (!in_array($local, config('app.locales'))) {
            $local = config('app.fallback_locale');
        }
        app()->setLocale($local);

        return $next($request);
    }
}
