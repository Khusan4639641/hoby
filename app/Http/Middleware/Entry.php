<?php

namespace App\Http\Middleware;

use App\Models\Partner;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class Entry {
    /**
     * Handle an incoming request.
     *
     * @param $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user    = Auth::user();
        $partner = Partner::find($user->id);
        $path    = $request->path();

        if(Session::get('role') == 'buyer' && $user->hasRole('buyer') && preg_match('/(billing|panel)/',$path))
        {
            return redirect("cabinet");
        }
        elseif(Session::get('role') == 'partner' && $user->hasRole('partner') && preg_match('/(cabinet|panel)/',$path))
        {
            return redirect('billing');
        }
        elseif(Session::get('role') == 'partner' && $user->hasRole('partner') && $partner->company->status == 0)
        {
            Auth::logout();
            setcookie("api_token", null, 0, '/');
            return redirect(localeRoute('home'));
        }
        elseif(Session::get('role') == 'employee' && $user->hasRole('employee') && preg_match('/(billing|cabinet)/',$path))
        {
            return redirect('panel');
        }
        return $next( $request );
    }
}
