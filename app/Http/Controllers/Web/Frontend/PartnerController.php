<?php

namespace App\Http\Controllers\Web\Frontend;


use App\Http\Controllers\Core\CatalogProductController as CoreProductController;
use App\Http\Controllers\Core\PartnerController as Controller;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;

class PartnerController extends Controller {
    //

    public function register(){
        return view('frontend.partner.register');
    }

    public function login(){
        return view('frontend.partner.login');
    }


    public function welcome(){
        $params = [
            'limit' => 10,
            'random'    => 'yes'
        ];
        $partners = $this->list($params)['data'];

        return view('frontend.partner.welcome', compact('partners'));
    }



    public function index(){
        $params = [
            'description__not' => "",
            'phone__not' => "",
            'address__not' => "",
            'brand__not' => "",
            'logo'  => ""
        ];
        $partners = $this->list($params)['data'];
        return view('frontend.partner.index', compact('partners'));
    }

    /**
     * Display the specified resource.
     *
     * @param Company $company
     * @return void
     */
    public function show(int $id)
    {

        //Данные партнера
        $result['partner'] = $this->detail($id)['data'];

        //Товары партнера
        $productController = new CoreProductController();

        //Собираем товары компании и филиалов
        $users = [];
        $user[] = $result['partner']->user->id;
        if(count($result['partner']->affiliates) > 0)
            foreach($result['partner']->affiliates as $affiliate)
                $user[] = $affiliate->user->id;

        $result['products'] = $productController->list(['user_id' => $user, 'limit' => 20, 'random' => 'Y'])['data'];


        return view( 'frontend.partner.show', $result);
    }
}
