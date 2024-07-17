<?php

namespace App\Http\Controllers\Web\Panel;

use App\Helpers\LocaleHelper;
use App\Http\Controllers\Core\DiscountController as Controller;
use App\Models\Discount;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\View\View;

class DiscountController extends Controller
{

    /**
     * @param array $items
     * @return array
     */
    protected function formatDataTables ($items = []){

        $i = 0;
        $data = [];
        foreach ( $items as $item ) {
            if($item->locale->image_list != null)
                $data[$i][] = '<div class="preview" style="background-image: url('.$item->locale->image_list->preview.')"></div>';
            else
                $data[$i][] = '<div class="preview dummy"></div>';


            $data[$i][] =   '<div class="dates">'
                                .'<div class="date">'.$item->date_start.' '.$item->time_start.'</div>'
                                .'<div class="date">'.$item->date_end.' '.$item->time_end.'</div>'
                            .'</div>';


            $data[$i][] = '<div class="inner">
                               <div class="title"><a href="'.localeRoute('panel.discounts.edit', $item).'">'.mb_substr($item->locale->title, 0, 70).'</a></div>
                           </div>';

            $data[$i][] = '<div class="params">'
                                .'<div class="period">'.$item->period_min.'&ndash;'.$item->period_max.' '.__('app.months').'</div>'
                                .'<div class="amount">'.$item->amount_max.'&ndash;'.$item->amount_min.' '.__('app.currency').'</div>'
                          .'</div>';

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

        return parent::formatDataTables($data);
    }


    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View
     */
    public function index()
    {
        $user = Auth::user();
        if($user->hasPermission('modify-discount')) {
            //Active orders
            $params = [
                'status__loe' => 1,
                'total_only' => 'yes'
            ];
            $counter['active'] = $this->filter($params)['total'];

            //Credit orders
            $params = [
                'status' => 0,
                'total_only' => 'yes'
            ];
            $counter['draft'] = $this->filter($params)['total'];

            //Credit orders
            $params = [
                'status' => 8,
                'total_only' => 'yes'
            ];
            $counter['archive'] = $this->filter($params)['total'];

            //Credit orders
            $params = [
                'total_only' => 'yes'
            ];
            $counter['all'] = $this->filter($params)['total'];
            return view('panel.discount.index', compact('user', 'counter'));
        }else {
            $this->message('danger', __('app.err_access_denied'));
            return redirect(localeRoute('panel.index'))->with('message', $this->result['response']['message']);
        }
    }



    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View
     */
    public function create()
    {
        $user = Auth::user();
        if($user->can('add', Discount::class)) {
            $languages = LocaleHelper::languages();
            $plans = Config::get('test.plans');
            return view('panel.discount.create', compact('languages', 'plans'));
        }else {
            $this->message('danger', __('app.err_access_denied'));
            return redirect(localeRoute('panel.discounts.index'))->with('message', $this->result['response']['message']);
        }
    }



    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request)
    {
        $result = $this->add( $request );

        if ( $result['status'] != 'success' ) {

            //Define redirect route
            if($result['response']['code'] == 403)
                $route = 'panel.index';
            else
                $route = 'panel.discounts.create';

            return redirect(localeRoute($route))
                ->withErrors( $result['response']['errors'] )
                ->withInput()
                ->with( 'message', $result['response']['message'] );

        } else {
            return redirect(localeRoute('panel.discounts.index'))->with( 'message', $result['response']['message'] );
        }
    }


    /**
     * Display the specified resource.
     *
     * @param Discount $discount
     * @return RedirectResponse
     */
    public function show(Discount $discount)
    {
        return redirect(localeRoute('panel.discounts.edit', $discount));
    }



    /**
     * Show the form for editing the specified resource.
     *
     * @param Discount $discount
     * @return Application|Factory|View
     */
    public function edit(Discount $discount)
    {
        $result = $this->detail($discount->id);

        if ($result['status'] != 'success' && $result['response']['code'] == 403) {
            $this->message('danger', __('app.err_access_denied'));
            return redirect(localeRoute('panel.discounts.index'))->with('message', $this->result['response']['message']);
        } else {
            $data = [
                'discount'      => $result['data'],
                'languages'     => LocaleHelper::languages(),
                'plans'         => Config::get('test.plans')
            ];

            return view('panel.discount.edit', $data);
        }

    }


    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Discount $discount
     * @return Application|RedirectResponse|Redirector
     */
    public function update(Request $request, Discount $discount)
    {
        $request->merge(['id' => $discount->id]);
        $result = $this->modify( $request );

        if ( $result['status'] != 'success' ) {

            //Define redirect route
            if($result['response']['code'] == 403)
                $route = localeRoute('panel.index');
            else
                $route = localeRoute('panel.discounts.edit', $discount);

            return redirect($route)
                ->withErrors( $result['response']['errors'] )
                ->withInput()
                ->with( 'message', $result['response']['message'] );

        } else {
            return redirect(localeRoute('panel.discounts.index'))->with( 'message', $result['response']['message'] );
        }
    }


    /**
     * @param Discount $discount
     * @return Application|RedirectResponse|Redirector
     * @throws Exception
     */
    public function destroy(Discount $discount)
    {
        $result = $this->delete( $discount );
        return redirect(localeRoute( 'panel.discounts.index' ))->with( 'message', $result['response']['message'] );
    }
}
