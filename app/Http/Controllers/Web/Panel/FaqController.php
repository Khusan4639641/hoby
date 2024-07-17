<?php

namespace App\Http\Controllers\Web\Panel;

use App\Helpers\LocaleHelper;
use App\Http\Controllers\Core\FaqController as Controller;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;

use App\Models\Faq;
use Illuminate\View\View;


class FaqController extends Controller
{

    /**
     * @param Collection | array $items
     * @return array
     */
    protected function formatDataTables ($items){

        $i = 0;
        $data = [];
        foreach ( $items as $item ) {

            $data[$i][] = '<div class="inner">
                               <div class="title"><a href="'.localeRoute('panel.faq.edit', $item).'">'.mb_substr($item->locale->title, 0, 70).'</a></div>
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
        if($user->hasPermission('modify-faq')) {
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

            return view('panel.faq.index', compact('user', 'counter'));
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
        if($user->can('add', Faq::class)) {
            $languages = LocaleHelper::languages();
            return view('panel.faq.create', compact('languages'));
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
        $result = $this->add( $request );

        if ( $result['status'] != 'success' ) {

            //Define redirect route
            if($result['response']['code'] == 403)
                $route = 'panel.index';
            else
                $route = 'panel.faq.create';

            return redirect(localeRoute( $route))
                ->withErrors( $result['response']['errors'] )
                ->withInput()
                ->with( 'message', $result['response']['message'] );

        } else {
            return redirect(localeRoute('panel.faq.index'))->with( 'message', $result['response']['message'] );
        }
    }


    /**
     * Display the specified resource.
     *
     * @param Faq $faq
     * @return RedirectResponse
     */
    public function show(Faq $faq)
    {
        return redirect(localeRoute('panel.faq.edit', $faq));
    }



    /**
     * Show the form for editing the specified resource.
     *
     * @param Faq $faq
     * @return Application|Factory|View
     */
    public function edit(Faq $faq)
    {
        $result = $this->detail($faq->id);

        if ($result['status'] != 'success' && $result['response']['code'] == 403) {
            $this->message('danger', __('app.err_access_denied'));
            return redirect(localeRoute('panel.index'))->with('message', $this->result['response']['message']);
        } else {
            $data = [
                'faq' => $result['data'],
                'languages' => LocaleHelper::languages()
            ];
            return view('panel.faq.edit', $data);
        }

    }


    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Faq $faq
     * @return Application|RedirectResponse|Redirector
     */
    public function update(Request $request, Faq $faq)
    {
        $request->merge(['id' => $faq->id]);
        $result = $this->modify( $request );

        if ( $result['status'] != 'success' ) {

            //Define redirect route
            if($result['response']['code'] == 403)
                $route = localeRoute('panel.index');
            else
                $route = localeRoute('panel.faq.edit', $faq);

            return redirect($route)
                ->withErrors( $result['response']['errors'] )
                ->withInput()
                ->with( 'message', $result['response']['message'] );

        } else {
            return redirect(localeRoute('panel.faq.index'))->with( 'message', $result['response']['message'] );
        }
    }


    /**
     * Destroy faq
     *
     * @param Faq $faq
     * @return Application|RedirectResponse|Redirector
     */
    public function destroy(Faq $faq)
    {
        $result = $this->delete( $faq );
        return redirect(localeRoute( 'panel.faq.index' ))->with( 'message', $result['response']['message'] );
    }
}
