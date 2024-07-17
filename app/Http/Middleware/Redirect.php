<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

class Redirect
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

        if (Auth::check()) {
            $user = Auth::user();

            if ($user->hasRole('admin')) {
                return redirect(localeRoute('panel.employees.index'));
            } elseif ($user->hasRole('kyc')) {
                return redirect(localeRoute('panel.buyers.index'));
            } elseif ($user->hasRole('sales')) {
                return redirect(localeRoute('panel.partners.index'));
            } elseif ($user->hasRole('finance')) {
                return redirect(localeRoute('panel.finances.index'));
            } elseif ($user->hasRole('call-center')) {
                return redirect(localeRoute('tickets.index'));
            } elseif (!empty($user->company_id)) {
                return redirect(localeRoute('billing.index'));
            }

        }
            return $next($request);
    }
}
