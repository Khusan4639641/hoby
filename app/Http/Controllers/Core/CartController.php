<?php


namespace App\Http\Controllers\Core;
use App\Models\Cart;
use App\Models\Cart as Model;
use App\Models\CartSetting;
use App\Models\CatalogProduct;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class CartController extends CoreController {

    public function __construct()
    {
        parent::__construct();
        $this->model = app(Model::class);

        //Eager load
        $this->loadWith = [];
    }

    public function list( array $params = []) {
        //Get data from REQUEST if api_token is set
        $request = request()->all();
        if ( isset( $request['api_token'] ))
            $params = $request;
        $user = Auth::user();

        $filter['result'] = collect([]);
        $filter['total'] = 0;

        if($user) {
            $params = array_merge($params, ['user_id' => $user->id]);
            $filter = $this->filter($params);
        } else {
            $cartID = Cookie::get('cart') ?? request()->session()->get('cart');

            if($cartID){
                $params = array_merge($params, ['cart_id' => $cartID]);
                $filter = $this->filter($params);
            }
        }

        $catalogProductController = new CatalogProductController();

        foreach ($filter['result'] as $item){
            $item->product = $catalogProductController->detail($item->product_id)['data'];
        }

        //Collect data
        $this->result['response']['total']  = $filter['total'];
        $this->result['status']             = 'success';
        $this->result['data'] = $filter['result'];

        //Return data
        return $this->result();
    }

    public function add(Request $request) {

        $product = CatalogProduct::find($request->id);
        if(!$product) {
            $this->result['status']             = 'error';
            return $this->result();
        }
        $cartID = null;
        $user = null;
        if($request->api_token)
            $user = User::whereApiToken($request->api_token)->first();

        if($user){
            $userCart = $user->cartProducts;
            if($userCart->isNotEmpty())
                $cartID = $userCart->first()->cart_id;

        } else {
            $cartID = Cookie::get('cart') ?? request()->session()->get('cart');
        }

        if(!$cartID) {

            $cartID = md5(rand(0, 100000));

            $cart = new Cart();
            $cart->cart_id = $cartID;
            $cart->user_id = $user ? $user->id: null;
            $cart->product_id = $product->id;
            $cart->quantity = 1;

            $cart->save();

            request()->session()->put('cart', $cartID);
            Cookie::queue(Cookie::make('cart', $cartID, 60*24*365));

            $this->result['response']['total']  = self::countCartProducts($cartID);
            $this->result['status']             = 'success';
            return $this->result();
        }

        $cart = Cart::where(['cart_id' => $cartID, 'product_id' => $product->id])->first();

        if($cart){
            $cart->quantity++;
        } else {
            $cart = new Cart();
            $cart->cart_id = $cartID;
            $cart->user_id = $user ? $user->id: null;
            $cart->product_id = $product->id;
            $cart->quantity = 1;
        }

        $cart->save();
        $this->result['response']['total']  = self::countCartProducts($cartID);
        $this->result['status']             = 'success';
        return $this->result();
    }

    public function update(Request $request) {

        $cart = Cart::where(['cart_id' => $request->cart_id, 'product_id' => $request->product_id])->first();

        if($cart){
            $cart->quantity = $request->quantity;
            $cart->save();
        } else {
            $this->result['status']             = 'error';
            return $this->result();
        }

        $this->result['response']['total']  = self::countCartProducts($cart->cart_id);
        $this->result['status']             = 'success';
        return $this->result();
    }

    public function delete(Request $request) {

        $query = Cart::query();

        $query->where(['cart_id' => $request->cart_id]);

        if(is_array($request->product_id))
            $query->whereIn('product_id', $request->product_id);
        else
            $query->where('product_id', $request->product_id);

        $items = $query->get();

        if($items){
            foreach ($items as $item)
                $item->forceDelete();
        } else {
            $this->result['status'] = 'error';
            return $this->result();
        }

        $this->result['response']['total'] = self::countCartProducts($request->cart_id);
        $this->result['status'] = 'success';
        return $this->result();
    }

    public function clear(Request $request) {

        $cartID = $request->cart_id;

        if($cartID && $cartID !== ''){
            $query = Cart::query();
            $query->where(['cart_id' => $cartID]);
            $items = $query->get();

            if($items){
                foreach ($items as $item)
                    $item->forceDelete();
            } else {
                $this->result['status'] = 'error';
                return $this->result();
            }

            CartSetting::where(['cart_id' => $cartID])->forceDelete();

            $this->result['status'] = 'success';
        } else {
            $this->result['status'] = 'error';
        }

        return $this->result();
    }

    public static function countCartProducts($cartId = false){

        if(!$cartId)
            $cartId = Cookie::get('cart') ?? request()->session()->get('cart');
        if($cartId)
            return Cart::whereCartId($cartId)->count();

        return 0;

    }


    /**
     * Product sorting by companies
     *
     * @param array $params
     * @return array
     */
    public function prepare($params = []) {
        $products = [];

        $request = \Illuminate\Support\Facades\Request::all();
        if ( isset( $request['api_token'] )){
            $params = $request;
        }
        unset($params['api_token']);

        if($params['cart']){
            $pController = new CatalogProductController();

            foreach ($params['cart'] as $item) {
                $product = $pController->single($item['product_id'], ['partner']);

                $products[$product->partner->company_id][] = [
                    'id'            => $product->id,
                    'name'          => $product->locale->title,
                    'price'         => $product->price_origin??$product->price,
                    'weight'        => $product->weight,
                    'vendor_code'   => $product->vendor_code,
                    'amount'        => $item['quantity']
                ];
            }
        }

        $this->result['status'] = 'success';
        $this->result['data'] = $products;

        return $this->result;
    }

    public function saveSettings(Request $request){
        $user = Auth::user();

        if($user) {
            if($cartProduct = $user->cartProduct){
                $cartID = $cartProduct->cart_id;
                $cartSettings = $cartProduct->settings ?? new CartSetting();

                $cartSettings->cart_id = $cartID;
                $cartSettings->settings = json_encode($request->settings);

                $cartSettings->save();
                $this->result['status'] = 'success';
            }

        } else {
            $this->result['status'] = 'error';
        }

        return $this->result;
    }

    public function loadSettings(Request $request){
        $user = Auth::user();

        if($user) {
            if($cartProduct = $user->cartProduct){
                if($cartSettings = $cartProduct->settings){
                    $this->result['status'] = 'success';
                    $this->result['data'] = json_decode($cartSettings->settings);
                }
            }

        } else {
            $this->result['status'] = 'error';
        }

        return $this->result;
    }
}
