<div class="partner-buyer">
    <div class="partner">
        <div class="title mt-3">{{__('panel/contract.partner')}}</div>
        <div class="caption">
            <div class="row">
                <div class="col-4">
                    <div class="label">
                        {{__('panel/contract.partner')}}
                    </div>
                </div>
                <div class="col-8">
                    <div class="value">{{$contract->partner->fio}} </div>
                </div>
            </div>
            <div class="row">
                <div class="col-4">
                    <div class="label">
                        {{__('panel/contract.company')}}
                    </div>
                </div>
                <div class="col-8">
                    <div class="value">{{$contract->partner->company->name}} </div>
                </div>
            </div>
            <div class="row">
                <div class="col-4">
                    <div class="label">
                        {{__('panel/contract.address')}}
                    </div>
                </div>
                <div class="col-8">
                    <div class="value">{{$contract->partner->company->address}}</div>
                </div>
            </div>
            <div class="row">
                <div class="col-4">
                    <div class="label">
                        {{__('panel/contract.legal_address')}}
                    </div>
                </div>
                <div class="col-8">
                    <div class="value">{{$contract->partner->company->legal_address}}</div>
                </div>
            </div>
            <div class="row">
                <div class="col-4">
                    <div class="label">
                        {{__('panel/contract.inn')}}
                    </div>
                </div>
                <div class="col-8">
                    <div class="value">{{$contract->partner->company->inn}}</div>
                </div>
            </div>
            <div class="row">
                <div class="col-4">
                    <div class="label">
                        {{__('panel/contract.payment_account')}}
                    </div>
                </div>
                <div class="col-8">
                    <div class="value">{{$contract->partner->company->payment_account}}</div>
                </div>
            </div>

            <a class="more"
               href="{{localeRoute('panel.partners.show', $contract->partner->company)}}">{{__('app.btn_more')}}</a>
        </div>
    </div><!-- /.partner -->

    <div class="buyer">
        <div class="title mt-3">{{__('panel/contract.buyer')}}</div>
        <div class="caption">
            <div class="row">
                <div class="col-4">
                    <div class="label">
                        {{__('panel/contract.buyer')}}
                    </div>
                </div>
                <div class="col-8">
                    <div class="value">{{$contract->buyer->fio}}</div>
                </div>
            </div>
            <div class="row">
                <div class="col-4">
                    <div class="label">
                        {{__('panel/contract.address')}}
                    </div>
                </div>
                <div class="col-8">
                    <div class="value">{{@$contract->buyer->addressRegistration->country}}
                        , {{@$contract->buyer->addressRegistration->city}}
                        , {{@$contract->buyer->addressRegistration->address}}</div>
                </div>
            </div>
            <div class="row">
                <div class="col-4">
                    <div class="label">
                        ID
                    </div>
                </div>
                <div class="col-8">
                    <div class="value">{{$contract->buyer->id}}</div>
                </div>
            </div>
            <div class="row">
                <div class="col-4">
                    <div class="label">
                        {{__('panel/contract.phone')}}
                    </div>
                </div>
                <div class="col-8">
                    <div class="value">{{$contract->buyer->phone}}</div>
                </div>
            </div>


            <div class="row">
                <div class="col-4">
                    <div class="label">
                        {{__('panel/contract.installment_period')}}
                    </div>
                </div>
                <div class="col-8">
                    <div class="value">{{$contract->period}}</div>
                </div>
            </div>
            <div class="row">
                <div class="col-4">
                    <div class="label">
                        {{__('panel/contract.installment_amount')}}
                    </div>
                </div>
                <div class="col-8">
                    <div class="value">{{number_format($contract->total,2,'.',' ')}}</div>
                </div>
            </div>
            <div class="row">
                <div class="col-4">
                    <div class="label">
                        {{__('panel/contract.total_debt')}}
                    </div>
                </div>
                <div class="col-8">
                    <div class="value">{{number_format($contract->totalDebt,2,'.',' ')}}</div>
                </div>
            </div>
            <a class="more" href="{{localeRoute('panel.buyers.show', $contract->buyer)}}">{{__('app.btn_more')}}</a>
        </div>
    </div><!-- /.buyer -->
</div>

<div class="order-data">
    <table class="table-order">
        <thead>
        <tr>
            <th>№</th>
            <th>{{__('panel/contract.product_name')}}</th>
            <th>{{__('panel/contract.product_qty')}}</th>
            <th>{{__('panel/contract.product_price')}}</th>
            {{--            <th>{{__('panel/contract.product_nds')}}</th>--}}
            <th>{{__('panel/contract.product_nds_sum')}}</th>
            <th>{{__('panel/contract.product_sum')}}</th>
            <th>{{__('panel/contract.product_nds_total')}}</th>
        </tr>
        </thead>
        <tbody>
        @php
            $total = 0;
        @endphp
        @isset($contract->order->products)

            @php
                $nds = Config::get('test.nds'); // 0.15
            @endphp
            @foreach($contract->order->products as $product)
                @php
                    // nds calculation
                    $nds_sum = $product->price / 1.15 * $nds;
                    // core product price calc
                    $core_product_price = $product->price - $nds_sum;
                    $sum = $product->amount * $core_product_price;
                    $nds_total = $nds_sum + $sum;
                    $total += $nds_total;
                @endphp
                <tr>
                    <td>{{$product->id}}</td>
                    <td>{{$product->name}}</td>
                    <td>{{$product->amount}}</td>
                    <td>{{number_format($core_product_price,2,'.',' ')}}</td>
                    {{--                    <td>{{$nds*100}}</td>--}}
                    <td>{{number_format($nds_sum,2,'.',' ')}}</td>
                    <td>{{number_format($sum,2,'.',' ')}}</td>
                    <td>{{number_format($nds_total,2,'.',' ')}}</td>
                </tr>
            @endforeach

        @else
            <tr>
                <td colspan="8">
                    {{__('billing/catalog.txt_empty_list')}}
                </td>
            </tr>
        @endisset
        </tbody>
    </table>
    @if($contract->order->shipping_code)
        <table class="order-info">
            <tr>
                <td class="label">{{__('panel/contract.order_delivery_method')}}</td>
                <td>{{__('shipping/'.strtolower($contract->order->shipping_code).'.name')}}</td>
            </tr>
            <tr>
                <td class="label">{{__('panel/contract.order_delivery_address')}}</td>
                <td>{{$contract->order->shipping_address}}</td>
            </tr>
        </table>
    @endif


    <div class="order-total mb-2">
        <div class="installment">
            <span>{{(__('panel/contract.installment_conditions'))}}</span>
            <span>{!! __('panel/contract.installment_conditions_text', ['total' => number_format($contract->total,2,'.',' '), 'period' => $contract->period])!!}</span>
        </div>
        <div class="total">
            <span>{{__('panel/contract.order_total')}}</span>
            <span>{{number_format($contract->total,2,'.',' ')}}</span>
        </div>
    </div>
</div>

<hr>

<div class="col-4 pl-0">
    <button class="btn btn-orange text-left btn-block text-center dropdown-toggle" type="button" data-toggle="collapse" data-target="#collapseExample"
            aria-expanded="false" aria-controls="collapseExample">
        {{__('cabinet/order.header_payments_schedule')}}
    </button>
</div>

<div class="collapse mt-3" id="collapseExample">
    <table class="table-schedule">
        <thead>
        <tr>
            <th>{{__('panel/contract.number')}}</th>
            <th>{{__('panel/contract.date')}}</th>
            <th>{{__('panel/contract.pay')}}</th>
            <th>{{__('panel/contract.balance')}}</th>
            <th>{{__('panel/contract.status')}}</th>
        </tr>
        </thead>
        <tbody>
        @isset($contract->schedule)
            @php
                $i = 1;
            @endphp
            @foreach($contract->schedule as $schedule)
                <tr @if($schedule->status == 2) class="expired" @endif>
                    <td>{{$i++}}</td>
                    <td>{{$schedule->date}}</td>
                    <td class="pay">{{number_format($schedule->total,2,'.',' ')}}</td>
                    <td>{{number_format($schedule->balance,2,'.',' ')}}</td>
                    <td>
                        @switch($schedule->status)
                            @case(0)
                            -
                            @break
                            @case(1)
                            <div class="paid">{{__('panel/contract.paid')}} {{$schedule->status_date}}</div>
                            @break
                            @case(2)
                            {{__('panel/contract.expired')}}
                            @break
                        @endswitch
                    </td>
                </tr>
            @endforeach
        @endisset
        </tbody>
    </table>
</div>


