<?php

namespace App\Http\Controllers\Web\Panel;

use App\Helpers\CardHelper;
use App\Helpers\EncryptHelper;
use App\Http\Controllers\Core\CatalogCategoryController;
use App\Http\Controllers\Core\SallerController as Controller;

use App\Models\Card;
use App\Models\CatalogPartners;
use App\Models\Company;
use App\Models\User;
use App\Models\Saller;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

use App\Http\Requests\AddSellerRequest;
use App\Http\Requests\UpdateSellerRequest;

class SallerController extends Controller
{

    /**
     * @return Application|Factory|RedirectResponse|Redirector|View
     */
    public function index() {
        $user = Auth::user();


        if ( $user->can( 'modify', new Saller() ) ) {

            return view( 'panel.saller.index' /*, compact('counter')*/ );
        } else {
            $this->message( 'danger', __( 'app.err_access_denied' ) );

             return redirect( localeRoute( 'panel.index' ) )->with( 'message', $this->result['response']['message'] );
        }
    }

    /**
     * @param array|Collection $items
     * @return array
     */
    protected function formatDataTables( $items )
    {
        $i    = 0;
        $data = [];
        foreach ( $items as $item )
        {
            $data[$i][] = '<div class="company">'.$item->name.'</div>';
            $data[$i][] = '<div class="id">'.__('panel/partner.id').'&nbsp;'.$item->id.'</div>';
            $data[$i][] = '<div class="name">'.@$item->company->brand.'</div>';
            $data[$i][] = '<div class="phone">'.@$item->user->phone.'</div>';
            $data[$i][] = '<a class="detail-link" href="' . localeRoute( 'panel.sallers.edit', $item ) . '"><span class="d-inline d-sm-none">'.__('app.btn_more').'</span></a>';
            $data[$i][] = localeRoute( 'panel.sallers.edit', $item );

            $i ++;
        }
        return parent::formatDataTables($data);
    }



    /**
     * @param $id
     *
     * @return Application|Factory|RedirectResponse|Redirector|View
     */
    public function show( $id ) {

        $result = $this->detail( $id );

        if ( $result['status'] != 'success' && $result['response']['code'] == 403 ) {
            $this->message( 'danger', __( 'app.err_access_denied' ) );

            return redirect( localeRoute( 'panel.sallers.index' ) )->with( 'message', $this->result['response']['message'] );
        } else {
            $saller = $result['data'];

            return view( 'panel.saller.show', compact( 'saller') );
        }
    }


    /**
     * @return Application|Factory|View
     */
    public function create()
    {

        $companies = Company::where('status',1)->where('brand','!=','')->orderBy('brand')->get();
        $user = Auth::user();

        return view('panel.saller.create',compact('companies','user'));
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Application|RedirectResponse|Redirector
     */
    public function store(AddSellerRequest $request)
    {
        $result = $this->add( $request );

        if ( $result['status'] != 'success' ) {

            //Define redirect route
            if($result['response']['code'] == 403)
                $route = 'panel.sallers.index';
            else
                $route = 'panel.sallers.create';
            return redirect(localeRoute($route))
                ->withErrors( $result['response']['errors'] )
                ->withInput()
                ->with( 'message', $result['response']['message'] );

        } else {
            return redirect(localeRoute('panel.sallers.index'))->with( 'message', $result['response']['message'] );
        }
    }


    /**
     * @param $id
     * @return Application|Factory|RedirectResponse|Redirector|View
     */
    public function edit( $id ) {

        $result = $this->detail( $id );

        if ( $result['status'] != 'success' && $result['response']['code'] == 403 ) {
            $this->message( 'danger', __( 'app.err_access_denied' ) );

            return redirect( localeRoute( 'panel.sallers.index' ) )->with( 'message', $this->result['response']['message'] );
        } else {
            $saller = $result['data'];

            $companies = Company::where('status',1)->where('brand','!=','')->orderBy('brand')->get();

            $user = Auth::user();

            $card = '';
            $exp = '';

            if ($sallerCard = Card::where('user_id', $id)->first()) {
                $card = CardHelper::getCardNumberMask(EncryptHelper::decryptData($sallerCard->card_number));
                $exp = EncryptHelper::decryptData($sallerCard->card_valid_date);
            }

            return view( 'panel.saller.edit', compact( 'saller','companies','user','card','exp') );
        }

    }


    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param saller $saller
     *
     * @return Application|RedirectResponse|Redirector
     */
    public function update(UpdateSellerRequest $request, $id) {

        $request->merge( [ 'saller_id' => $id ] );
        $result = $this->modify( $request );

        if ( $result['status'] != 'success' ) {

            //Define redirect route
            if ( $result['response']['code'] == 403 ) {
                $route = localeRoute( 'panel.sallers.index' );
            } else {
                $route = localeRoute( 'panel.sallers.edit', $id );
            }

            return redirect( $route )
                ->withErrors( $result['response']['errors'] )
                ->withInput()
                ->with( 'message', $result['response']['message'] );

        } else {
            return redirect( localeRoute( 'panel.sallers.index' ) )->with( 'message', $result['response']['message'] );
        }
    }


}
