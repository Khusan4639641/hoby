<?php

namespace App\Http\Controllers\Web\Frontend;

use Illuminate\Http\Request;

class SearchController extends FrontendController
{

    public function index() {

        $request = request();

        $q = $request->get('q');

        $productController = new \App\Http\Controllers\Core\CatalogProductController();
        $result = $productController->list(['title__like' => $q]);
        $products = [];

        if($result['status'] == 'success'){
            $products = $result['data'];
        }

        return view('frontend.search.index', compact('products', 'q'));
    }


}
