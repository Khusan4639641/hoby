<?php

namespace App\Http\Controllers\Web\Frontend;

use Illuminate\Http\Request;

class PageController extends FrontendController
{
    public function render(Request $request){
        $name = $request->route()->getName();
        $view = 'frontend.'.$name;

        if(view()->exists($view)) {
            return view($view);
        }else{
            return abort(404);
        }
    }
    public function payInstruction(\Symfony\Component\HttpFoundation\Request $request)
    {
        return view('frontend.page.pay-instruction');
    }
}
