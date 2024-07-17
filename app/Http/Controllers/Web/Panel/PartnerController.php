<?php


namespace App\Http\Controllers\Web\Panel;

use App\Helpers\EncryptHelper;
use App\Http\Controllers\Core\CatalogCategoryController;
use App\Http\Controllers\Core\PartnerController as Controller;
use App\Models\AvailablePeriod;
use App\Models\Buyer;
use App\Models\BuyerAddress;
use App\Models\CatalogPartners;
use App\Models\Company;
use App\Models\GeneralCompany;
use App\Models\Partner;
use App\Models\PartnerSetting;
use App\Models\Region;
use App\Models\User;
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

class PartnerController extends Controller {


    /**
     * @return Application|Factory|RedirectResponse|Redirector|View
     */
    public function index() {
        $user = Auth::user();

        if ( $user->can( 'modify', new Partner() ) ) {
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

            return view( 'panel.partner.index', compact('counter'));
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
            $data[$i][] = $item->status == 1 ? '<img alt="'.__( 'user.status_' . $item->status ).'" class="icon-status" src="/images/icons/icon_ok_circle_green.svg" />' : '<img class="icon-status" src="/images/icons/icon_attention.svg" />';
            $data[$i][] = $item->logo != null ? '<div class="preview" style="background-image: url('.$item->logo->GlobalPreview.'); background-size: contain; background-repeat: no-repeat; background-position: center;""></div>' : '<div class="preview dummy"></div>';
            $data[$i][] = '<div class="company">'.$item->name.'</div>';
            $data[$i][] = '<div class="id">'.__('panel/partner.id').'&nbsp;'.$item->id.'</div>';
            $data[$i][] = '<div class="name">'.$item->brand.'</div>';
            $data[$i][] = '<div class="phone">'.@$item->user->phone.'</div>';
            $data[$i][] = '<a class="detail-link" href="' . localeRoute( 'panel.partners.show', $item ) . '"><span class="d-inline d-sm-none">'.__('app.btn_more').'</span></a>';
            $data[$i][] = localeRoute( 'panel.partners.show', $item );

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

            return redirect( localeRoute( 'panel.partners.index' ) )->with( 'message', $this->result['response']['message'] );
        } else {
            $partner = $result['data'];
            $partner->block_reason = $partner->block_reason ?? ($partner->blockReason ? ($partner->blockReason->reson->name ?? $partner->blockReason->comment) : "");
            $partner->block_date = $partner->blockReason ? $partner->blockReason->created_at->format("d.m.Y")."г" : "";
            return view( 'panel.partner.show', compact( 'partner') );
        }
    }


    /**
     * @return Application|Factory|View
     */
    public function create()
    {
        $generalCompanies = GeneralCompany::all();
        $availablePeriods = AvailablePeriod::where("status", 1)->get();
        $categories = CatalogCategoryController::tree(0,[],true);
        $regions = Region::select('regionid as id','nameRu as name')->get();
        $managers = User::whereHas('roles', function($query) {
            $query->where('name', '=', 'sales');
        })->get();
        return view('panel.partner.create',
            compact('categories',
                'managers',
                'regions',
                'generalCompanies',
                'availablePeriods'
            )
        );
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
                $route = 'panel.partners.index';
            else
                $route = 'panel.partners.create';
            return redirect(localeRoute($route))
                ->withErrors( $result['response']['errors'] )
                ->withInput()
                ->with( 'message', $result['response']['message'] );

        } else {
            return redirect(localeRoute('panel.partners.index'))->with( 'message', $result['response']['message'] );
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

            return redirect( localeRoute( 'panel.partners.index' ) )->with( 'message', $this->result['response']['message'] );
        } else {
            $partner = $result['data'];
            $categories = CatalogCategoryController::tree(0,[],true);

            $managers = User::whereHas('roles', function($query) {
                $query->where('name', '=', 'sales');
            })->get();

            $catalogPartners = CatalogPartners::where('partner_id',$id)->pluck('catalog_id')->toArray();
            $regions = Region::select('regionid as id','nameRu as name')->get();
            $generalCompanies = GeneralCompany::all();
            $availablePeriods = AvailablePeriod::where("status", 1)->get();
            $partner->is_trustworthy = PartnerSetting::where('company_id', $id)->pluck('is_trustworthy')->first();

            return view( 'panel.partner.edit',
                compact('partner',
                    'managers',
                    'categories',
                    'catalogPartners',
                    'regions',
                    'generalCompanies',
                    'availablePeriods'
                )
            );
        }

    }


    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Partner $partner
     *
     * @return Application|RedirectResponse|Redirector
     */
    public function update( Request $request, $id ) {

        $request->merge( [ 'partner_id' => $id ] );
        $result = $this->modify( $request );

        if ( $result['status'] != 'success' ) {

            //Define redirect route
            if ( $result['response']['code'] == 403 ) {
                $route = localeRoute( 'panel.partners.index' );
            } else {
                $route = localeRoute( 'panel.partners.edit', $id );
            }

            return redirect( $route )
                ->withErrors( $result['response']['errors'] )
                ->withInput()
                ->with( 'message', $result['response']['message'] );

        } else {
            return redirect( localeRoute( 'panel.partners.show', $id ) )->with( 'message', $result['response']['message'] );
        }
    }

    /*

    public function sallers(){


        $sallers = null;

        return view( 'panel.partner.sallers', compact( 'sallers') );

    } */


}
