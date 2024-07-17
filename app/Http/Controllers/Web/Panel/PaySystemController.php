<?php


namespace App\Http\Controllers\Web\Panel;

use App\Helpers\EncryptHelper;
use App\Http\Controllers\Core\PaySystemController as Controller;
use App\Models\Buyer;
use App\Models\BuyerAddress;
use App\Models\PaySystem;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PaySystemController extends Controller {


    /**
     * @return Application|Factory|RedirectResponse|Redirector|View
     */
    public function index() {
        $user = Auth::user();

        //if ( $user->can( 'modify', new PaySystem() ) ) {

            //Active orders
            $params = [
                'status' => 1,
                'total_only' => 'yes'
            ];
            $counter['verified'] = $this->filter($params)['total'];

            //Credit orders
            $params = [
                'status' => 0,
                'total_only' => 'yes'
            ];
            $counter['verification'] = $this->filter($params)['total'];


            //Credit orders
            $params = [
                'total_only' => 'yes'
            ];
            $counter['all'] = $this->filter($params)['total'];

            return view( 'panel.pay-system.index', compact('counter'));
        /*} else {
            $this->message( 'danger', __( 'app.err_access_denied' ) );

            return redirect( localeRoute( 'panel.index' ) )->with( 'message', $this->result['response']['message'] );
        }*/
    }



    /**
     * @param array|Collection $items
     * @return array
     */
    protected function formatDataTables( $items ) {
        $i    = 0;
        $data = [];
        foreach ( $items as $item ) {
            $data[$i][] = '<div class="inner">
                               <div class="title"><a href="'.localeRoute('panel.pay-system.edit', $item).'">'.mb_substr($item->locale->title, 0, 70).'</a></div>
                               <div class="text">'.mb_substr($item->locale->text, 0, 50).'</div>
                           </div>';

            if($item->status != 1)
                $data[$i][] = '<button onclick="publish('.$item->id.')" class="btn btn-sm btn-success" type="button">'.__('app.btn_publish').'</button>';
            elseif($item->status == 1)
                $data[$i][] = '<button onclick="archive('.$item->id.')" class="btn btn-sm btn-archive" type="button">'.str_replace(' ', '&nbsp;', __('app.btn_archive')).'</button>';
            else
                $data[$i][] = '';

            $data[$i][] = '<button onclick="confirmDelete('.$item->id.')" type="button"
                                class="btn-delete">'.__('app.btn_delete').'</button>';
            $i++;
        }
        return parent::formatDataTables( $data );
    }



    /**
     * @param $id
     *
     * @return Application|Factory|RedirectResponse|Redirector|View
     */
    public function show( $id ) {

        /*$result = $this->detail( $id );
        dd($result);
        if ( $result['status'] != 'success' && $result['response']['code'] == 200 ) {
            $this->message( 'danger', __( 'app.err_access_denied' ) );

            return redirect( localeRoute( 'panel.pay-system.index' ) )->with( 'message', $this->result['response']['message'] );
        } else {

            $pay_system = $result['data'];
            return view( 'panel.pay-system.show', compact( 'pay_system') );
        }*/

        return view( 'panel.pay-system.show', $id );
    }


    /**
     * @return Application|Factory|View
     */
    public function create()
    {
        return view('panel.pay-system.create');
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Application|RedirectResponse|Redirector
     */
    public function store(Request $request)
    {

        $result['status'] = 'success';
        if ( $result['status'] != 'success' ) {

            //Define redirect route
            if($result['response']['code'] == 403)
                $route = 'panel.pay-system.index';
            else
                $route = 'panel.pay-system.create';
            return redirect(localeRoute($route))
                ->withErrors( $result['response']['errors'] )
                ->withInput()
                ->with( 'message', $result['response']['message'] );

        } else {
            return redirect(localeRoute('panel.pay-system.index'))->with( 'message', $result['response']['message'] );
        }
    }


    /**
     * @param $id
     * @return Application|Factory|RedirectResponse|Redirector|View
     */
    public function edit( $id ) {

        $data = $this->detail( $id );

        if ( $data['status'] != 'success' && $data['response']['code'] == 200 ) {
            $this->message( 'danger', __( 'app.err_access_denied' ) );

            return redirect(localeRoute('panel.index'))->with('message', $this->result['response']['message']);
        } else {
            $pay_system = $data['data'];
            return view( 'panel.pay-system.edit', compact('pay_system'));
        }

    }


    /**
     * Update the specified resource in storage.
     *
     * @param Request $pay_system
     * @param Partner $pay_system
     *
     * @return Application|RedirectResponse|Redirector
     */
    public function update( Request $request, $pay_sys) {

        $request->merge( [ 'id' => $pay_sys->id ] );
        $result = $this->modify( $request );

        if ( $result['status'] != 'success' ) {

            //Define redirect route
            if ( $result['response']['code'] == 403 ) {
                $route = localeRoute( 'panel.pay-system.index' );
            } else {
                $route = localeRoute( 'panel.pay-system.edit', $pay_sys->id );
            }

            return redirect( $route )
                ->withErrors( $result['response']['errors'] )
                ->withInput()
                ->with( 'message', $result['response']['message'] );

        } else {
            return redirect( localeRoute( 'panel.pay-system.show', $pay_sys->id ) )->with( 'message', $result['response']['message'] );
        }
    }
}
