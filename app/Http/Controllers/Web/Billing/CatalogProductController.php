<?php

namespace App\Http\Controllers\Web\Billing;

use App\Http\Controllers\Core\CatalogProductController as Controller;

use Illuminate\Contracts\Foundation\Application;  // Для Redirector'a
use Illuminate\Http\RedirectResponse;             // Для Redirector'a
use Illuminate\Routing\Redirector;                // Для Redirector'a

class CatalogProductController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return Application|RedirectResponse|Redirector  // Redirector
     */
    public function index() {
        return redirect(localeRoute('billing.orders.index'));
    }

}
