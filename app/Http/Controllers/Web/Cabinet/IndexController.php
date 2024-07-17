<?php

namespace App\Http\Controllers\Web\Cabinet;



use App\Http\Controllers\Core\CardController;
use App\Models\Buyer;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class IndexController
{

    /**
     * @return Application|Factory|View
     */
    public function index()
    {


        $user = Buyer::find(Auth::id());

        //Payments
        $pController = new PaymentController();
        $params = [
            'orderBy'    => 'payment_date',
            'limit'      => 3,
            'status'     => 0,
            'contract|status'   => 1
        ];
        $payments = $pController->filter($params)['result'];

       //Cards
        $cards = $user->cards;

        return view('cabinet.index', compact('payments', 'cards', 'user'));
    }


    public function refill(){
        $user = Auth::user();

        //Cards
        $params = [
            'user_id'  => $user->id
        ];
        $cController = new CardController();
        $cards = $cController->list($params)['data'];


        return view('cabinet.index_mobile', compact('payments', 'cards', 'user'));
    }


}
