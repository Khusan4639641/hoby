<?php

namespace App\Http\Controllers\Web\Frontend;

use \App\Http\Controllers\Core\FaqController as Controller;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;

class FaqController extends Controller {
    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View
     */
    public function index() {
        $params = [
            'status'    => 1
        ];
        $result = $this->list($params);
        $faq = $result['data'];

        return view( 'frontend.faq.index', compact('faq'));
    }


    /**
     * @param string $template
     * @return Application|Factory|View
     */
    public static function widget($template = 'footer'){
        $view = 'frontend.faq.widget_'.$template;

        $faqController = new \App\Http\Controllers\Core\FaqController();

        $params = [
            'limit'     => 5,
            'status'    => 1
        ];
        $result = $faqController->list($params);
        $faq = $result['data'];

        return view($view, compact('faq'));
    }
}
