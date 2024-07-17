<?php

namespace App\Http\Controllers\Web\Panel;

use App\Http\Controllers\Core\SlidesController as Controller;
use App\Helpers\LocaleHelper;
use App\Models\Slide;
use App\Models\Slider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SlidesController extends Controller
{

    /**
     * @param Collection | array $items
     * @return array
     */
    protected function formatDataTables ($items){

        //$items = $this->list($items);

        $i = 0;
        $data = [];
        foreach ( $items as $item ) {
            $data[$i][] = '<div class="sort">'.$item->sort.'</div>';

            if($item->image != null)
                $data[$i][] = '<div class="preview" style="background-image: url('.$item->image->preview.')"></div>';
            else
                $data[$i][] = '<div class="preview dummy"></div>';

            $data[$i][] = '<div class="inner"><div class="title">'.$item->title.'</div></div>';

            $data[$i][] = '<div class="language">'.$item->languae_code.'</div>';

            $data[$i][] = '<a href="'.localeRoute('panel.slides.edit', $item->id).'" type="button"
                                class="btn btn-sm btn-link">'.__('app.btn_edit').'</a>';

            $data[$i][] = '<button onclick="confirmDelete('.$item->id.')" type="button"
                                class="btn-delete">'.__('app.btn_delete').'</button>';
            $i++;
        }

        return parent::formatDataTables($data);
    }


    /**
     * Display a listing of the resource.
     *
     * @param int $id
     * @return Application|Factory|View
     */
    public function index(int $id)
    {
        $user = Auth::user();
        $slider = Slider::find($id);

        if($slider && $user->hasPermission('modify-slider')) {
            return view('panel.slides.index', compact('slider') );
        }else {
            $this->message('danger', __('app.err_access_denied'));
            return redirect(localeRoute('panel.index'))->with('message', $this->result['response']['message']);
        }
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param Slide $slide
     * @return Application|Factory|View
     */
    public function edit(Slide $slide)
    {
        $result = $this->detail($slide->id);

        if ($result['status'] != 'success' && $result['response']['code'] == 403) {
            $this->message('danger', __('app.err_access_denied'));
            return redirect(localeRoute('panel.index'))->with('message', $this->result['response']['message']);
        } else {
            $data = [
                'slide'         => $slide,
                'languages'     => LocaleHelper::languages(),
                'slider'        =>Slider::find($slide->slider_id)
            ];
            return view('panel.slides.edit', $data);
        }

    }


    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Slide $slide
     * @return Application|RedirectResponse|Redirector
     */
    public function update(Request $request, Slide $slide)
    {
        $request->merge(['id' => $slide->id]);
        $result = $this->modify( $request );

        if ( $result['status'] != 'success' ) {

            //Define redirect route
            if($result['response']['code'] == 403)
                $route = localeRoute('panel.index');
            else
                $route = localeRoute('panel.slides.edit', $slide);

            return redirect($route)
                ->withErrors( $result['response']['errors'] )
                ->withInput()
                ->with( 'message', $result['response']['message'] );

        } else {
            return redirect(localeRoute('panel.slides.index', $slide->slider_id))->with( 'message', $result['response']['message'] );
        }
    }



    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View
     */
    public function create(int $id)
    {
        $user = Auth::user();
        $slider = Slider::find($id);

        if($slider && $user->can('add', Slide::class)) {
            $languages = LocaleHelper::languages();
            return view('panel.slides.create', compact('slider', 'languages'));
        } else {
            $this->message('danger', __('app.err_access_denied'));
            return redirect(localeRoute('panel.index'))->with('message', $this->result['response']['message']);
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
        $result = $this->add($request);

        if ($result['status'] != 'success') {

            //Define redirect route
            if ($result['response']['code'] == 403)
                $route = localeRoute('panel.index');
            else
                $route = localeRoute('panel.slides.create', $request->slider_id);

            return redirect($route)
                ->withErrors($result['response']['errors'])
                ->withInput()
                ->with('message', $result['response']['message']);

        } else {
            return redirect(localeRoute('panel.slides.index', $request->slider_id))->with('message', $result['response']['message']);
        }
    }
}
