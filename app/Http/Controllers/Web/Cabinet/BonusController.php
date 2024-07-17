<?php

namespace App\Http\Controllers\Web\Cabinet;

use App\Http\Controllers\Web\Frontend\FrontendController;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;

class BonusController extends FrontendController
{
    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View
     */
    public function index() {
        return view( 'cabinet.bonuses.index' );
    }
}
