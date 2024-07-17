<?php

namespace App\Http\Controllers\Web\Panel;

use App\Helpers\LocaleHelper;
use App\Http\Controllers\Core\PostalAreaController as Controller;
use App\Models\PostalArea;
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

class PostalAreaController extends Controller {

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
                               <div class="title"><a href="'.localeRoute('panel.postal-areas.edit', $item).'">'.mb_substr($item->name, 0, 70).'</a></div>
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

            return view('panel.postal_areas.index', compact('user'));

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

        //if ($user->can('add', PostalArea::class)) {
        if (true) {

            $postal_regions = PostalRegion::orderBy('name')->pluck('name', 'id');

            $katm_local_regions = KatmRegion::orderBy('local_region_name')->pluck('local_region_name', 'local_region');

            return view('panel.postal_areas.create', compact('postal_regions', 'katm_local_regions'));

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
                $route = 'panel.postal-areas.create';

            return redirect(localeRoute($route))
                ->withErrors( $result['response']['errors'] )
                ->withInput()
                ->with('message', $result['response']['message']);

        } else {
            return redirect(
                localeRoute('panel.postal-areas.index'))->with('message', $result['response']['message']
            );
        }
    }

    /**
     * Display the specified resource.
     *
     * @param PostalArea $area
     * @return RedirectResponse
     */
    public function show(PostalArea $area)
    {
        return redirect(localeRoute('panel.postal-areas.edit', $area));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param PostalArea $area
     * @return Application|Factory|View
     */
    public function edit(PostalArea $postal_area)
    {
        $result = $this->detail($postal_area->id);

        if ($result['status'] != 'success' && $result['response']['code'] == 403) {

            $this->message('danger', __('app.err_access_denied'));
            return redirect(localeRoute('panel.index'))->with('message', $this->result['response']['message']);

        } else {

            $postal_regions = PostalRegion::orderBy('name')->pluck('name', 'id');
            $katm_local_regions = KatmRegion::orderBy('local_region_name')->pluck('local_region_name', 'local_region');

            $data = [
                'area' => $result['data'],
                'postal_regions' => $postal_regions,
                'katm_local_regions' => $katm_local_regions
            ];

            return view('panel.postal_areas.edit', $data);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param PostalArea $area
     * @return Application|RedirectResponse|Redirector
     */
    public function update(Request $request, PostalArea $postal_area)
    {
        $request->merge(['id' => $postal_area->id]);
        $result = $this->modify($request);

        //dd($request);

        if ($result['status'] != 'success') {

            // Define redirect route
            if ($result['response']['code'] == 403)
                $route = localeRoute('panel.index');
            else
                $route = localeRoute('panel.postal-areas.edit', $postal_area);

            return redirect($route)
                ->withErrors($result['response']['errors'])
                ->withInput()
                ->with('message', $result['response']['message']);

        } else {

            return redirect(localeRoute('panel.postal-areas.index'))->with('message', $result['response']['message']);
        }
    }

    /**
     * Destroy region
     *
     * @param PostalArea $area
     * @return Application|RedirectResponse|Redirector
     * @throws Exception
     */
    public function destroy(PostalArea $area)
    {
        $result = $this->delete($area);

        return redirect(localeRoute( 'panel.postal-areas.index' ))->with('message', $result['response']['message']);
    }
}
