<?php

namespace App\Http\Controllers\Web\Panel\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index()
    {
        return view('panel.accounts.index');
    }
    public function create()
    {
        return view('panel.accounts.create');
    }
    public function createWithMask()
    {
        return view('panel.accounts.create-mask');
    }
}
