<?php

namespace App\Http\Controllers\Web\Frontend;

class MenuController extends FrontendController {
    private $menu = [
        'news'     => [
            'route' => 'news.index'
        ],
        'partners' => [
            'route' => 'partners.index'
        ],

    ];


    /**
     * Render menu
     */
    public static function render() {

    }
}
