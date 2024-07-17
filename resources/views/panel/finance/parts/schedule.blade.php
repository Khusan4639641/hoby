<div class="schedule order-block">
    <div class="lead">{{__('cabinet/order.header_payments_schedule')}}</div>

    <table class="table table2">
        <thead>
        <th>{{__('cabinet/order.lbl_number')}}</th>
        <th>{{__('cabinet/order.lbl_payment_date')}}</th>
        <th>{{__('cabinet/order.lbl_payment_date')}}</th>
        <th>{{__('cabinet/order.lbl_payment_balance')}}</th>
        <th>{{__('app.status')}}</th>
        </thead>
        <tbody>
        @foreach($order->contract->schedule as $index => $payment)
            <tr>
                <td>{{$index + 1}}</td>
                <td>{{$payment->date}}</td>
                <td>{{$payment->total}}</td>
                <td>{{$payment->balance}}</td>
                <td><span class="status status-{{$payment->status}}">{{__('payment.status_'.$payment->status)}}</span></td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <div class="mt-4">
        <a href="#" class="btn btn-outline-primary">{{__('offer.btn_download_offer')}}</a>
        <a href="#" class="btn btn-outline-primary">{{__('offer.btn_download_act')}}</a>
    </div>
</div><!-- /.schedule -->

