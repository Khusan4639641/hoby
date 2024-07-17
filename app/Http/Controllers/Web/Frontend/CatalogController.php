<?php

namespace App\Http\Controllers\Web\Frontend;

use App\Models\News;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use \App\Http\Controllers\Core\NewsController as Controller;

class CatalogController extends Controller{

    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View
     */
    public function index() {
        $subcategories = CatalogCategoryController::tree();
        return view( 'frontend.catalog.index', compact('subcategories'));
    }

}
