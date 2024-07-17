<?php

namespace App\Http\Controllers\Web\Frontend;

use App\Models\News;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use \App\Http\Controllers\Core\NewsController as Controller;

class NewsController extends Controller{

    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View
     */
    public function index() {
        $result = $this->list();
        $news = $result['data'];
        return view( 'frontend.news.index', compact('news'));
    }


    /**
     * Display the specified resource.
     *
     * @param News $news
     * @return RedirectResponse
     */
    public function show(News $news)
    {
        //Данные новости
        $result['news'] = $this->detail($news->id)['data'];

        //Плхожие новости
        $filter = ['id__not' =>$news->id, 'orderByDesc' => 'date', 'limit' => 6];
        $result['related'] = $this->list($filter)['data'];

        return view( 'frontend.news.show', $result);
    }

}
