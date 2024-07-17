<?php

namespace App\Http\Controllers\Web\Frontend;

use App\Http\Controllers\Core\CatalogProductController as Controller;
use Illuminate\Support\Facades\Config;

class CatalogProductController extends Controller {

    public function show(String $slug, $id) {

        $result = $this->detail($id);

        if ( $result['status'] == 'success' ) {
            $product = $result['data'];

            #Related Products
            $relatedProducts = $this->list(['limit' => 6, 'id' => $this->relatedProducts($product)->toArray()])['data'];
            $relatedProductsLimit = 6;
            $countRelatedProducts = $relatedProducts->count();

            if($countRelatedProducts < $relatedProductsLimit){
                $_arrNotID = $this->relatedProducts($product)->toArray() ?? [];
                array_push($_arrNotID, $product->id);
                $randomProducts = $this->list(['limit' => $relatedProductsLimit - $countRelatedProducts, 'random' => true, 'id__not' => $_arrNotID])['data'];
                $relatedProducts = $relatedProducts->merge($randomProducts);
            }

            #Credit plans
            $plans = Config::get('test.plans');

            return view( 'frontend.catalog.product.show', compact('product', 'relatedProducts', 'plans'));
        } else {
            abort(404);
        }

    }
}
