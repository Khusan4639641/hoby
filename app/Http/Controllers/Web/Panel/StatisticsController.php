<?php


namespace App\Http\Controllers\Web\Panel;
use \App\Http\Controllers\Core\StatisticsController as Controller;
use Illuminate\Support\Facades\Auth;


class StatisticsController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function index() {
        $user = Auth::user();
        $result = $this->finance();
        if ($result['status'] != 'success' && $result['response']['code'] == 403) {
            $this->message( 'danger', __( 'app.err_access_denied' ) );
            return redirect( localeRoute( 'billing.index' ) )->with( 'message', $this->result['response']['message'] );
        } else {
            return view( 'panel.statistics.index', compact('user') );
        }
    }
}
