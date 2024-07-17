<?php

namespace App\Http\Controllers\Web\Panel;

use App\Http\Controllers\Controller;
use App\Models\GeneralCompany;
use \Illuminate\Support\Facades\Auth;


class FakeController extends Controller
{
    public function index()
    {
        if (
            !( Auth::user()->hasRole('admin') || Auth::user()->hasRole('finance') )
        ) {
            abort('403');
        }
        return view('panel.fake.index',[
            'companies'=> GeneralCompany::all()
        ]);
    }
    public function export()
    {
        if (
            !( Auth::user()->hasRole('admin') || Auth::user()->hasRole('finance') )
        ) {
            abort('403');
        }

        return view('panel.fake.excel');
    }
}
