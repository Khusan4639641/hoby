<?php

namespace App\Http\Controllers\Web\Billing;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Core\PartnerAffiliateController as Controller;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;


class AffiliateController extends Controller
{


    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
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

        return view('billing.affiliate.index', compact('counter'));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return view('billing.affiliate.create');
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Application|RedirectResponse|Redirector
     */
    public function store(Request $request)
    {
        $result = $this->add( $request );

        if ( $result['status'] != 'success' ) {

            //Define redirect route
            if($result['response']['code'] == 403)
                $route = 'billing.affiliates.index';
            else
                $route = 'billing.affiliates.create';
            return redirect(localeRoute($route))
                ->withErrors( $result['response']['errors'] )
                ->withInput()
                ->with( 'message', $result['response']['message'] );

        } else {
            return redirect(localeRoute('billing.affiliates.index'))->with( 'message', $result['response']['message'] );
        }
    }


    /**
     * @param array|Collection $items
     * @return array
     */
    protected function formatDataTables( $items ) {
        $i    = 0;
        $data = [];

        foreach ( $items as $item ) {
            $data[ $i ][] = $item->status == 1 ? '<img alt="'.__( 'company.status_' . $item->status ).'" class="icon-status" src="/images/icons/icon_ok_circle_green.svg" />' : '<img class="icon-status" src="/images/icons/icon_attention.svg" />';
            $data[ $i ][] = '<div class="date">'.$item->date.'</div>';
            if($item->logo)
                $data[$i][] = '<div class="preview" style="background-image: url('.$item->logo->preview.')"></div>';
            else
                $data[$i][] = '<div class="preview dummy"></div>';
            $data[ $i ][] = '<div class="company">'.$item->name.'</div>';
            $data[ $i ][] = '<div class="name">'.$item->user->fio.'</div>';
            $data[ $i ][] = '<div class="phone">'.$item->user->phone.'</div>';

            $data[ $i ][] = ($item->status == 0) ? "<a href='" . localeRoute( 'billing.affiliates.edit', $item ) . "'>" . __( 'app.btn_edit' ) . "</a>" : '';
            $data[ $i ][] = '<a class="detail-link" href="' . localeRoute( 'billing.affiliates.show', $item ) . '"><span class="d-inline d-sm-none">'.__('app.btn_more').'</span></a>';
            $data[ $i ][] = localeRoute( 'billing.affiliates.show', $item );

            $i ++;
        }
        return parent::formatDataTables( $data );
    }



    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        $result = $this->detail( $id );
        if ( $result['status'] != 'success' && $result['response']['code'] == 403 ) {
            $this->message( 'danger', __( 'app.err_access_denied' ) );

            return redirect( localeRoute( 'billing.affiliates.index' ) )->with( 'message', $this->result['response']['message'] );
        } else {
            $affiliate = $result['data'];
            return view( 'billing.affiliate.show', compact( 'affiliate') );
        }
    }



    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Application|RedirectResponse|Redirector
     */
    public function edit($id)
    {
        $result = $this->detail( $id );

        if ( $result['status'] != 'success' && $result['response']['code'] == 403 ) {
            $this->message( 'danger', __( 'app.err_access_denied' ) );

            return redirect( localeRoute( 'billing.affiliates.index' ) )->with( 'message', $this->result['response']['message'] );
        } else {
            $affiliate = $result['data'];

            return view( 'billing.affiliate.edit', compact( 'affiliate' ) );
        }
    }



    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param  int  $id
     * @return Application|RedirectResponse|Redirector
     */
    public function update(Request $request, $id)
    {
        $request->merge( [ 'id' => $id ] );
        $result = $this->modify( $request );

        if ( $result['status'] != 'success' ) {

            //Define redirect route
            if ( $result['response']['code'] == 403 ) {
                $route = localeRoute( 'billing.affiliates.index' );
            } else {
                $route = localeRoute( 'billing.affiliates.edit', $id );
            }

            return redirect( $route )
                ->withErrors( $result['response']['errors'] )
                ->withInput()
                ->with( 'message', $result['response']['message'] );

        } else {
            return redirect( localeRoute( 'billing.affiliates.index' ) )->with( 'message', $result['response']['message'] );
        }
    }



    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }
}
