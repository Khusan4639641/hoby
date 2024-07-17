<?php

namespace App\Http\Controllers\Web\Panel;

use App\Helpers\LocaleHelper;
use App\Http\Controllers\Core\PostalRegionController as Controller;
use App\Models\PostalRegion;
use App\Models\KatmRegion;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PostalRegionController extends Controller {

    /**
     * @param Collection | array $items
     * @return array
     */
    protected function formatDataTables($items) {

        $i = 0;
        $data = [];

        foreach ( $items as $item ) {
            $data[$i][] = '<div class="date">'.$item->id.'</div>';
            $data[$i][] = '<div class="inner">
                               <div class="title"><a href="'.localeRoute('panel.postal-regions.edit', $item).'">'.mb_substr($item->name, 0, 70).'</a></div>
                           </div>';
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

        //if ($user->hasPermission('modify-news')) {
        if (true) {

            return view('panel.postal_regions.index', compact('user'));

        } else {

            $this->message('danger', __('app.err_access_denied'));
            return redirect(
                localeRoute('panel.index'))->with('message', $this->result['response']['message']
            );
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

        //if ($user->can('add', PostalRegion::class)) {
        if (true) {

            $katm_regions = KatmRegion::orderBy('region_name')->pluck('region_name', 'region');

            return view('panel.postal_regions.create', compact('katm_regions'));

        } else {

            $this->message('danger', __('app.err_access_denied'));
            return redirect(
                localeRoute('panel.index'))->with('message', $this->result['response']['message']
            );
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

        if ($result['status'] != 'success') {

            //Define redirect route
            if ($result['response']['code'] == 403)
                $route = 'panel.index';
            else
                $route = 'panel.postal-regions.create';

            return redirect(localeRoute($route))
                ->withErrors( $result['response']['errors'] )
                ->withInput()
                ->with('message', $result['response']['message']);

        } else {
            return redirect(localeRoute('panel.postal-regions.index'))
                    ->with('message', $result['response']['message']);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param PostalRegion $region
     * @return RedirectResponse
     */
    public function show(PostalRegion $region)
    {
        return redirect(localeRoute('panel.postal-regions.edit', $region));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param PostalRegion $region
     * @return Application|Factory|View
     */
    public function edit(PostalRegion $postal_region)
    {
        $result = $this->detail($postal_region->id);

        if ($result['status'] != 'success' && $result['response']['code'] == 403) {

            $this->message('danger', __('app.err_access_denied'));
            return redirect(localeRoute('panel.index'))->with('message', $this->result['response']['message']);

        } else {

            $katm_regions = KatmRegion::orderBy('region_name')->pluck('region_name', 'region');

            $data = [
                'region' => $result['data'],
                'katm_regions' => $katm_regions
            ];

            return view('panel.postal_regions.edit', $data);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param PostalRegion $region
     * @return Application|RedirectResponse|Redirector
     */
    public function update(Request $request, PostalRegion $postal_region)
    {
        $request->merge(['id' => $postal_region->id]);
        $result = $this->modify($request);

        if ($result['status'] != 'success') {

            // Define redirect route
            if ($result['response']['code'] == 403)
                $route = localeRoute('panel.index');
            else
                $route = localeRoute('panel.postal-regions.edit', $postal_region);

            return redirect($route)
                ->withErrors($result['response']['errors'])
                ->withInput()
                ->with('message', $result['response']['message']);

        } else {

            return redirect(localeRoute('panel.postal-regions.index'))->with('message', $result['response']['message']);
        }
    }

    /**
     * Destroy region
     *
     * @param PostalRegion $region
     * @return Application|RedirectResponse|Redirector
     * @throws Exception
     */
    public function destroy(PostalRegion $region)
    {
        $result = $this->delete($region);

        return redirect(localeRoute( 'panel.postal-regions.index' ))->with('message', $result['response']['message']);
    }
}
