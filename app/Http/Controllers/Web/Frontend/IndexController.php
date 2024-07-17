<?php

namespace App\Http\Controllers\Web\Frontend;

use \App\Http\Controllers\Core\CatalogProductController;
use \App\Http\Controllers\Core\PartnerController;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class IndexController extends FrontendController
{

    public function  __construct()
    {
        $this->middleware('redirect');
    }

    /**
     * @return Application|Factory|View
     */
    public function index() {

        return view('frontend.partner.login' );

    }



}
