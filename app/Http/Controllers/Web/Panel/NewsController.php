<?php

namespace App\Http\Controllers\Web\Panel;

use App\Helpers\LocaleHelper;
use App\Http\Controllers\Core\NewsController as Controller;
use App\Http\Requests\NewsRequest;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;

use App\Models\News;
use Illuminate\View\View;


class NewsController extends Controller
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

            $data[$i][] = '<div class="date">'.$item->date.'</div>';

            if($item->locale->image != null){
                /*$url = \App\Helpers\FileHelper::url("news-language/" .$item->locale->image->element_id. "/preview_" . $item->locale->image->name);
                $data[$i][] = '<div class="preview" style="background-image: url('  .$url.')"></div>';*/
                $data[$i][] = '<div class="preview" style="background-image: url('.$item->locale->image->preview.')"></div>';

            } else
                $data[$i][] = '<div class="preview dummy"></div>';

            $data[$i][] = '<div class="inner">
                               <div class="title"><a href="'.localeRoute('panel.news.edit', $item).'">'.mb_substr($item->locale->title, 0, 70).'</a></div>
                               <div class="preview_text">'.mb_substr($item->locale->preview_text, 0, 50).'</div>
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
        if($user->hasPermission('modify-news')) {
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

            return view('panel.news.index', compact('user', 'counter'));
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
        if($user->can('add', News::class)) {
            $languages = LocaleHelper::languages();
            return view('panel.news.create', compact('languages'));
        } else {
            $this->message('danger', __('app.err_access_denied'));
            return redirect(localeRoute('panel.index'))->with('message', $this->result['response']['message']);
        }
    }



    /**
     * Store a newly created resource in storage.
     *
     * @param NewsRequest $request
     * @return RedirectResponse
     */
    public function store(NewsRequest $request)
    {
        $result = $this->add( $request );

        if ( $result['status'] != 'success' ) {

            //Define redirect route
            if($result['response']['code'] == 403)
                $route = 'panel.index';
            else
                $route = 'panel.news.create';

            return redirect(localeRoute( $route))
                ->withErrors( $result['response']['errors'] )
                ->withInput()
                ->with( 'message', $result['response']['message'] );

        } else {
            return redirect(localeRoute('panel.news.index'))->with( 'message', $result['response']['message'] );
        }
    }


    /**
     * Display the specified resource.
     *
     * @param News $news
     * @return RedirectResponse
     */
    public function show(News $news)
    {
        return redirect(localeRoute('panel.news.edit', $news));
    }



    /**
     * Show the form for editing the specified resource.
     *
     * @param News $news
     * @return Application|Factory|View
     */
    public function edit(News $news)
    {

        $result = $this->detail($news->id);

        if ($result['status'] != 'success' && $result['response']['code'] == 403) {
            $this->message('danger', __('app.err_access_denied'));
            return redirect(localeRoute('panel.index'))->with('message', $this->result['response']['message']);
        } else {
            $data = [
                'news' => $result['data'],
                'languages' => LocaleHelper::languages()
            ];
            return view('panel.news.edit', $data);
        }

    }


    /**
     * Update the specified resource in storage.
     *
     * @param NewsRequest $request
     * @param News $news
     * @return Application|RedirectResponse|Redirector
     */
    public function update(NewsRequest $request, News $news)
    {
        $request->merge(['id' => $news->id]);
        $result = $this->modify( $request );

        if ( $result['status'] != 'success' ) {

            //Define redirect route
            if($result['response']['code'] == 403)
                $route = localeRoute('panel.index');
            else
                $route = localeRoute('panel.news.edit', $news);

            return redirect($route)
                ->withErrors( $result['response']['errors'] )
                ->withInput()
                ->with( 'message', $result['response']['message'] );

        } else {
            return redirect(localeRoute('panel.news.index'))->with( 'message', $result['response']['message'] );
        }
    }


    /**
     * Destroy news
     *
     * @param News $news
     * @return Application|RedirectResponse|Redirector
     * @throws Exception
     */
    public function destroy(News $news)
    {
        $result = $this->delete( $news );
        return redirect(localeRoute( 'panel.news.index' ))->with( 'message', $result['response']['message'] );
    }
}
