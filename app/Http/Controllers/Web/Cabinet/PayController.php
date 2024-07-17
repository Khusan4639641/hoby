<?php

namespace App\Http\Controllers\Web\Cabinet;

use App\Models\Buyer;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Http\Controllers\Core\PayController as Controller;

class PayController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View
     */
    public function index()
    {
        //Mobile
        $params = [
            'status'    => 1,
            'order_by'  => 'name',
            'type'      => 1
        ];
        $services['mobile'] = $this->list($params)['data'];

        //Internet
        $params = [
            'status'    => 1,
            'order_by'  => 'name',
            'type'      => 0
        ];
        $services['internet'] = $this->list($params)['data'];

        //Balance
        $balance = ProfileController::userInfo()['zcoin'];

        return view('cabinet.pay.index', compact('services', 'balance'));
    }

}
