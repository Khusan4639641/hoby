<?php

namespace App\Http\Controllers\Web\Cabinet;

use App\Http\Controllers\Core\BuyerPaymentController;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PaymentController extends BuyerPaymentController {

    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View
     */

    public function index() {
        $user = Auth::user();

        $counter = [];

        //Active orders
        $params       = [
            'orderBy'    => 'payment_date',
            'status'  => 0,
            'contract|status'   => 1,
            'total_only' => 'yes'
        ];
        $counter['0'] = $this->filter( $params )['total'];

        //Credit orders
        $params       = [
            'orderBy'    => 'payment_date',
            'payment_date__less' => Carbon::now()->format("Y-m-d H:i:s"),
            'contract|status'   => 4,
            'status'  => 0,
            'total_only' => 'yes'
        ];
        $counter['2'] = $this->filter( $params )['total'];

        //Credit orders
        $params       = [
            'orderBy'    => 'payment_date',
            'status'  => 1,
            'total_only' => 'yes'
        ];
        $counter['1'] = $this->filter( $params )['total'];

        $payments = $this->list( [ 'status' => 0, 'user_id' => $user->id ] )['data'];
        $total    = $this->totalPaymentInMonth( $payments );

        return view( 'cabinet.payment.index', compact( 'total', 'counter' ) );
    }


    /**
     * @param array|Collection $items
     *
     * @return array
     */
    protected function formatDataTables( $items ) {

        $i     = 0;
        $data  = [];
        foreach ( $items as $item ) {

            if ( $item->status == 0 &&  strtotime($item->payment_date) <= Carbon::now()->timestamp) {
                $date = "<div class='payment-date expired'><span>{$item->date}</span><span>" . __( 'cabinet/payment.expired' ) . "</span></div>";
            } else {
                $date = "<div class='payment-date'><span>{$item->date}</span></div>";
            }

            $data[ $i ][] = $date;
            $data[ $i ][] = __( 'cabinet/payment.order_caption', [ 'id'   => $item->contract->order->id,
                                                                   'date' => $item->date
            ] );
            $data[ $i ][] = "<a class='contract-link' href='" . localeRoute( 'cabinet.orders.show', $item->contract->order ) . "#offer'>№{$item->contract->id}</a>";
            $data[ $i ][] = '<span class="d-inline d-md-none">'.__('cabinet/payment.installment').'</span> '.$item->contract->period.' '.__('app.months');
            $data[ $i ][] = '<strong>'.$item->total.'</strong>'. '('.$item->balance.')'.__('app.currency');
            $data[ $i ][] = '<a class="order-link" href="' . localeRoute( 'cabinet.orders.show', $item->contract->order ) . '"><span class="d-inline d-sm-none">'.__('app.btn_more').'</span></a>';

            $i ++;
        }

        return parent::formatDataTables( $data );
    }
}
