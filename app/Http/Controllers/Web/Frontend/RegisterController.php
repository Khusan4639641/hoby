<?php


namespace App\Http\Controllers\Web\Frontend;


class RegisterController {
    public function index() {
        $populateProductController = new \App\Http\Controllers\Core\CatalogProductController();
        $populateProducts = $populateProductController->list(['limit' => 12, 'random' => true])['data'];

        return view('frontend.register.index', compact('populateProducts'));
    }
}
