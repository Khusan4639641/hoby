<?php


namespace App\Http\Controllers\Web\Billing;
use \App\Http\Controllers\Core\NotificationController as Controller;
use Illuminate\Support\Facades\Auth;


class NotificationController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function index() {
        $user = Auth::user();
        $notifications = $this->all()['data'];
        return view( 'billing.notification.index', compact('user', 'notifications') );
    }
}
