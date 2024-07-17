<?php

namespace App\Http\Controllers\Web\Frontend;

use App\Models\Buyer;
use App\Models\CatalogProduct;
use Illuminate\Http\Request;
use \App\Http\Controllers\Core\CartController as Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;


class CartController extends Controller {

    public function index() {
        $result = $this->list();

        $user = Auth::user();
        $verified = false;
        if($user)
            $verified = $user->status == 4 && $user->hasRole('buyer');

        $status = $user->status ?? null;

        $settings = $plans = null;
        if($verified) {
            $buyer = Buyer::find($user->id);
            $buyer->with('settings');
            $settings = [
                'limit' => $buyer->settings->limit,
                'period' => $buyer->settings->period
            ];
            $plans = Config::get('test.plans');
        }

        if($result['status'] == 'success'){
            $cart = [];
            if(count($result['data']) > 0){

                foreach($result['data'] as $item)
                    $grouped_products[$item->product->partner->company_id][] = $item;

                $cart['products'] = json_encode($grouped_products);
                $cart['id'] = $result['data'][0]->cart_id;
            }

            return view('frontend.cart.index', compact('cart', 'verified', 'settings', 'plans', 'status'));
        } else {
            return abort(404);
        }
    }

}
