<?php

namespace App\Http\Controllers\Core;

use App\Helpers\EncryptHelper;
use App\Models\Order;
use App\Models\Contract as Model;
use App\Models\Payment;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;


class CallCenterController extends CoreController {


    /**
     * OrderController constructor
     */
    public function __construct() {
        parent::__construct();
        $this->model = app( Model::class );
    }


    /**
     * @param array $params
     *
     * @return array
     */
    public function filter( $params = [] ) {
        return parent::filter( $params );
    }


    /**
     * Get items ids list
     *
     * @param array $params
     *
     * @return array|bool|false|string
     */
    public function list( array $params = [] ) {

    }


    /**
     * @param $id
     * @param array $with
     *
     */
    protected function single( $id, $with = [] ) {

    }


    /**
     * Order detail
     *
     * @param int $id
     *
     * @return array|false|string
     */
    public function detail( int $id ) {

    }


}
