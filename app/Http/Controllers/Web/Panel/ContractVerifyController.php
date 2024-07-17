<?php

namespace App\Http\Controllers\Web\Panel;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;

use Illuminate\View\View;

class ContractVerifyController
{
    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View
     */
    public function index()
    {
        return view('panel.contract_verify.index');
    }
}
