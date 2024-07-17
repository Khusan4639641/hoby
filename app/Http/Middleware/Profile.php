<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

class Profile
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
        return $next( $request );

        /*$user = Auth::user();

       // Log::info(Route::currentRouteName());

         if( $user->hasRole('buyer') ) {
            if( $user->status!=4 ) {
                if ( Route::currentRouteName() != 'cabinet.profile.verify' ){
                    $lang = session('locale');
                    Log::info(Route::currentRouteName());
                 //   return redirect($lang . '/cabinet/profile/verify');
                }
            }
        } */


    }

}
