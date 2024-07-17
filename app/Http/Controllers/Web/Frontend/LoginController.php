<?php

namespace App\Http\Controllers\Web\Frontend;

use App\Models\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;


class LoginController extends FrontendController {

    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View|RedirectResponse
     */
    public function index() {


        if(Auth::check()){
            return redirect(localeRoute('home'));
        }
        return view( 'frontend.login.index');
    }



    public function logout(){
        Auth::logout();
        setcookie("api_token", null, time()-config( 'session.lifetime' )*60, '/');
        Cookie::queue(Cookie::forget('cart'));
        request()->session()->flush();
        Log::info(redirect(localeRoute('home')));
        return redirect(localeRoute('home'));
    }


    public function panelLogin(){
        return view('frontend.panel.login');
    }

    // 05.05.2021 logme simple
    public function logme(Request $request){
        $user = false;
        if(!empty($request->id)){
            $user = User::find($request->id);

        }
        if(!empty($request->phone)){
            $user = User::where('phone',$request->phone)->firstOrFail();
        }
        if($user){
            Auth::login($user);
            return redirect('/cabinet');
        }
        return redirect(localeRoute('home'));

    }


}
