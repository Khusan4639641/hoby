<div class="schedule order-block">
    <div class="lead">{{__('cabinet/order.header_payments_schedule')}}</div>

    <div class="dataTables_wrapper">
        <table class="table dataTable">
            <thead>
            <th>{{__('cabinet/order.lbl_number')}}</th>
            <th>{{__('cabinet/order.lbl_payment_date')}}</th>
            <th>{{__('cabinet/order.lbl_payment_total')}}</th>
            <th>{{__('cabinet/order.lbl_payment_balance')}}</th>
            <th>{{__('app.status')}}</th>
            </thead>
            <tbody>
            @foreach($order->contract->schedule as $index => $payment)
                <tr>
                    <td>{{$index + 1}}</td>
                    <td>
                        @if ( $payment->status == 0 &&  strtotime($payment->payment_date) <= Illuminate\Support\Carbon::now()->timestamp)
                            <div class='payment-date expired'><span>{{$payment->date}}</span><span> {{__( 'cabinet/payment.expired' )}} </span></div>
                        @else
                            <div class='payment-date'><span>{{$payment->date}}</span></div>
                        @endif
                    </td>
                    <td>{{$payment->total}}</td>
                    <td>{{$payment->balance}}</td>
                    <td><span class="status status-{{$payment->status}}">{{__('payment.status_'.$payment->status)}}</span></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div><!-- /.dataTables_wrapper -->
</div><!-- /.schedule -->
