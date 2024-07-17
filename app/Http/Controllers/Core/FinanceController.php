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


class FinanceController extends CoreController {

    private $config = [

    ];

    private $encryptedFields = [
        'birthday',
        'city_birth',
        'work_company',
        'work_phone',
        'passport_number',
        'passport_date_issue',
        'passport_issued_by',
        'home_phone',
        'pinfl',
        'social_vk',
        'social_facebook',
        'social_linkedin',
        'social_instagram',
    ];

    /**
     * Fields validator
     *
     * @param array $data
     *
     * @return Validator
     */
    private $validatorRules = [
        'amount' => ['required'],
        'contractor' => [''],
       // 'insurance' => [],
        'date' => ['required', 'date'],
    ];


    /**
     * OrderController constructor
     */
    public function __construct() {
        parent::__construct();
        $this->model = app( Model::class );

        //Eager load
        $this->loadWith = [
            'order',
            'order.products'
        ];
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
        $user = Auth::user();

        //Get data from REQUEST if api_token is set
        $request = request()->all();
        if ( isset( $request['api_token'] ) ) {
            $params = $request;
        }

        //Filter elements
        $filter = $this->filter( $params );
        //Render items
        foreach ( $filter['result'] as $index => $item ) {
            $item->permissions = $this->permissions( $item, $user );

            $item->totalDebt = 0;
            foreach ( $item->debts as $debt ) {
                $item->totalDebt += $debt->total;
            }
        }


        //Collect data
        $this->result['response']['total'] = $filter['total'];
        $this->result['status']            = 'success';

        //Format data
        if ( isset( $params['list_type'] ) && $params['list_type'] == 'data_tables' ) {

            $filter['result'] = $this->formatDataTables( $filter['result'] );
        }

        $this->result['data'] = $filter['result'];

        //Return data
        return $this->result();
    }


    /**
     * @param $id
     * @param array $with
     *
     * @return Builder|\Illuminate\Database\Eloquent\Model|object
     */
    protected function single( $id, $with = [] ) {
        $single = parent::single( $id, array_merge( $this->loadWith, [ 'company' ] ) );

        return $single;
    }


    /**
     * Order detail
     *
     * @param int $id
     *
     * @return array|false|string
     */
    public function detail( int $id ) {

        $order = $this->single( $id );
        $user  = Auth::user();

        if ( $order ) {
            if ( $user->can( 'detail', $order ) ) {

                $this->result['status']        = 'success';
                $this->result['data']['order'] = $order;
            } else {
                //Error: access denied
                $this->result['status']           = 'error';
                $this->result['response']['code'] = 403;
                $this->message( 'danger', __( 'app.err_access_denied' ) );
            }
        } else {
            $this->result['status']           = 'error';
            $this->result['response']['code'] = 404;
            $this->message( 'danger', __( 'app.err_not_found' ) );
        }

        return $this->result();
    }

    /**
     * Order detail
     *
     * @param int $id
     *
     * @return array|false|string
     */
    public function detailOrder( int $id ) {

        $order = Order::whereId( $id )->with([])->first();
        $user  = Auth::user();

        if ( $order ) {
            if ( $user->can( 'detail-finance', $order ) ) {

                if ($order->buyer->personals)
                    foreach ($order->buyer->personals->getAttributes() as $key => $value)
                        $order->buyer->personals[$key] = in_array($key, $this->encryptedFields) ? EncryptHelper::decryptData($value) : $value;

                $order->nds = Config::get('test.nds');

                $order->totalDebt = 0;
                foreach ( $order->contract->debts as $debt ) {
                    $order->totalDebt += $debt->total;
                }

                $this->result['status'] = 'success';
                $this->result['data']   = $order;
            } else {
                //Error: access denied
                $this->result['status']           = 'error';
                $this->result['response']['code'] = 403;
                $this->message( 'danger', __( 'app.err_access_denied' ) );
            }
        } else {
            $this->result['status']           = 'error';
            $this->result['response']['code'] = 404;
            $this->message( 'danger', __( 'app.err_not_found' ) );
        }

        return $this->result();
    }

    public function addReceipt(Request $request){
        $validator = $this->validator($request->all(), $this->validatorRules);

        if ($validator->fails()) {
            $this->result['status'] = 'error';
            $this->result['response']['errors'] = $validator->errors();
        } else {

            $payment = new Payment();
            $payment->order_id = $request->order_id;
            $payment->contract_id = $request->contract_id;
            $payment->amount = $request->amount;
            //$payment->insurance_request_id = $request->insurance_request_id;
            $payment->created_at = $request->date;
            $payment->type = $request->receipt_type;
            $payment->save();

            $credit = false;

            if($request->action == 'to_supplier'){
                $order = Order::find($request->order_id);

                if($order){
                    $order->credit -= abs($request->amount);
                    $order->save();
                    $credit = $order->credit;
                }
            }

            $this->result['data']['credit']   = $credit;
            $this->result['status'] = 'success';
            $this->message( 'success', __( 'panel/finance.txt_receipt_added' ) );
        }
        return $this->result();
    }

    public function listReceipt( array $params = []) {
        $user = Auth::user();

        //Get data from REQUEST if api_token is set
        $request = request()->all();
        if ( isset( $request['api_token'] ))
            $params = $request;

        $payments = Payment::whereOrderId($params['order_id'])->get();

        //Collect data
        $this->result['status']             = 'success';
        $this->result['data'] = $payments;

        //Return data
        return $this->result();
    }
}
