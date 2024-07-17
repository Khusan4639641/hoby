<?php

namespace App\Http\Middleware;

use Closure;
use http\Env\Request;

class ModifyRequest
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
        $input = $request->all();

        foreach ($input as $key => $value) {
            if (preg_match('/phone/', $key)) {
               $input[$key] = str_replace(['+','(',')',' ','-'],'',$value);
               $request->replace($input);
            }
        }

        $locale = $request->route()->parameter('locale');
        $request->route()->forgetParameter('locale');
        app()->setLocale($locale);

        return $next($request);
    }

}
