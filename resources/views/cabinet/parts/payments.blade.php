@if(count($payments) > 0)
    <section class="payments list">
        <div class="dataTables_wrapper">
            <div class="lead">{{__('cabinet/payment.next_payments')}}</div>
            <table class="table payments-list mt-4 dataTable">
                <tbody>
                @foreach($payments as $item)
                    <tr>
                        <td class="item-date">
                            @if ( $item->status == 2 )
                                <div class='payment-date expired'><span>{{$item->date}}</span><span>{{__( 'cabinet/payment.expired' )}}</span></div>
                            @else
                                <div class='payment-date'><span>{{$item->date}}</span></div>
                            @endif
                        </td>
                        <td class="item-title">
                            {{__( 'cabinet/payment.order_caption', [ 'id'   => $item->contract->order->id,
                                                               'date' => $item->date
        ] )}}
                        </td>
                        <td>
                            <a class="contract-link" href="{{localeRoute( 'cabinet.orders.show', $item->contract->order )}}#offer">№{{$item->contract->id}}</a>
                        </td>
                        <td>
                            <span class="d-inline d-md-none">{{__('cabinet/payment.installment')}}</span>
                            {{$item->contract->period}} {{__('app.months')}}
                        </td>
                        <td><strong>{{$item->total}}</strong> ({{$item->balance}}) {{__('app.currency')}}</td>
                        <td class="item-readmore">
                            <a class="order-link" href="{{localeRoute( 'cabinet.orders.show', $item->contract->order )}}"><span class="d-inline d-sm-none">{{__('app.btn_more')}}</span></a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <!-- /.dataTables_wrapper -->

        <div class="text-center">
            <a href="{{localeRoute('cabinet.payments.index')}}" class="btn btn-arrow btn-success">{{__('cabinet/payment.payments_schedule')}}</a>
        </div>

    </section><!-- /.payments.list -->

@endif
