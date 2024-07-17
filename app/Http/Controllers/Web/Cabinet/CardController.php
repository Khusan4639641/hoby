<?php

namespace App\Http\Controllers\Web\Cabinet;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Http\Controllers\Core\CardController as Controller;

class CardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View
     */
    public function index()
    {
        $buyer = Auth::user();
        return view('cabinet.card.index', compact('buyer'));
    }

}
