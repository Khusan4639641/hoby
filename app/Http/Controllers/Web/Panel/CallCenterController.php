<?php


namespace App\Http\Controllers\Web\Panel;

use App\Helpers\EncryptHelper;
use App\Http\Controllers\Core\FinanceController as Controller;
use App\Models\Buyer;
use App\Models\BuyerAddress;
use App\Models\Partner;
use App\Models\Payment;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class CallCenterController extends Controller {

    public function index() {
        $user = Auth::user();
        //if ( $user->can( 'modify', new Payment() ) ) {
            return view( 'panel.callcenter.index' );
        //} else {
          //  $this->message( 'danger', __( 'app.err_access_denied' ) );

           // return redirect( localeRoute( 'panel.index' ) )->with( 'message', $this->result['response']['message'] );
        //}
    }

    /**
     * @param $id
     *
     * @return Application|Factory|RedirectResponse|Redirector|\Illuminate\View\View
     */
    public function show( $id ) {

    }

    public function edit( $id ) {


    }
}
