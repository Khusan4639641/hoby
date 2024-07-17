<?php

namespace App\Http\Controllers\Web\Panel;

use App\Http\Controllers\Controller;
use App\Models\GeneralCompany;
use \Illuminate\Support\Facades\Auth;


class FaqInfoController extends Controller
{
    public function index()
    {
        return view('panel.faq.index');
    }

}
