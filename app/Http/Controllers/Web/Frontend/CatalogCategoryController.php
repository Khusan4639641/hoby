<?php

namespace App\Http\Controllers\Web\Frontend;

use App\Helpers\LocaleHelper;
use App\Http\Controllers\Core\CatalogCategoryController as Controller;
use App\Http\Controllers\Core\CatalogProductController as CoreProductController;
use App\Models\CatalogCategory;
use App\Models\CatalogProduct;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CatalogCategoryController extends Controller {


    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View
     */
    public function index() {
        return view( 'frontend.catalog.category.index');
    }

    public function show(String $slug, $id) {

        $resultCategory = $this->detail($id);

        if ( $resultCategory['status'] == 'success' ) {
            $category = $resultCategory['data'];
            $productController = new CoreProductController();
            $arrProducts = $category->products()->pluck('id')->toArray();
            $resultProduct = $productController->list(['id' => $arrProducts]);
            $products = $resultProduct['status'] == 'success' ? $resultProduct['data'] : [];
            $subcategories = self::tree($id);
            return view( 'frontend.catalog.category.show', compact('category', 'subcategories', 'products'));
        } else {
            abort(404);
        }
    }
}
