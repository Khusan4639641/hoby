<?php

namespace App\Http\Controllers\Web\Collector;

use App\Http\Controllers\Controller;

class CollectorController extends Controller {
    public function frontend() {
        return view('collector.collector_layout');
    }
}
