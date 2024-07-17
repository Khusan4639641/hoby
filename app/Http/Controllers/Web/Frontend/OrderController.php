<?php

namespace App\Http\Controllers\Web\Frontend;

use App\Models\Buyer;
use \App\Http\Controllers\Core\OrderController as Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class OrderController extends Controller {

    public function processing($type) {
        $user = Auth::user();
        if($user == null || !isset($user->cartProducts))
            abort(404);
        $buyer = Buyer::find($user->id);
        $addressesShipping = $buyer->addressesShipping ?? collect([]);
        $products = $user->cartProducts;
        $cards = $buyer->cards;
        $api_token = $buyer->api_token;
        $phone = $buyer->phone;
        $personal_account = $buyer->settings->personal_account;
        $plans = Config::get('test.plans');

        if($products) {
            return view('frontend.order.processing', compact('type', 'addressesShipping', 'products','cards', 'plans', 'api_token', 'phone', 'personal_account'));
        } else {
            abort(404);
        }

    }

}
