<?php

namespace App\Http\Controllers\Web\Billing;

use App\Http\Controllers\Web\Frontend\FrontendController;
use Illuminate\Support\Facades\Auth;

use Illuminate\Contracts\Foundation\Application;  // Для Redirector'a
use Illuminate\Http\RedirectResponse;             // Для Redirector'a
use Illuminate\Routing\Redirector;                // Для Redirector'a


class IndexController extends FrontendController
{
    /**
     * Display a listing of the resource.
     *
     * @return Application|RedirectResponse|Redirector  // Redirector
     */
    public function index()
    {
        return redirect(localeRoute('billing.orders.index'));
    }

    public function userStatus()
    {
        $user = Auth::user();
        return view('billing.user-status.index', compact('user'));
    }

}
