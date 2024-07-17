@extends('templates.cabinet.app')

@section('class', 'orders show')
@section('title', __('cabinet/order.header_order').' №'.$order->id)

@section('h1')
    {{__('cabinet/order.header_order').' №'.$order->id}}
    <span class="date">{{$order->created_at}}</span>
@endsection

@section('center-header-control')
    <div class="status">
        @if($order->status < 9 && $order->contract)
            <div class="order-status status-{{$order->status}}">{{__('cabinet/order.header_order')}}: {{__('order.status_'.$order->status)}}</div>
        @endif
        @if($order->contract)
            <div class="contract-status status-{{$order->contract->status}}">{{__('cabinet/order.header_contract')}}: {{__('contract.status_'.$order->contract->status)}}</div>
        @endif
    </div>

    @if($order->contract && $order->contract->status === 1)
            <button class="btn btn-primary" data-toggle="modal" data-target="#repayModal">{{__('cabinet/order.btn_repay')}}</button>
    @endif
@endsection

@section('center-header-prefix')
    <a class="link-back" href="{{localeRoute('cabinet.orders.index')}}"><img src="{{asset('images/icons/icon_arrow_green.svg')}}"></a>
@endsection



@section('content')

    <div class="details">
        @if($order->contract)
            <div class="params">
                <div class="row">
                    @if($order->contract->nextPayment)
                        <div class="col-12 col-sm-6 col-md part">
                            <div class="caption">{{__('cabinet/order.lbl_payment_amount')}}</div>
                            <div class="value sb">{{ $order->contract->nextPayment->total }}</div>
                        </div>
                        <div class="col-12 col-sm-6 col-md part">
                            <div class="caption">{{__('cabinet/order.lbl_payment_date')}}</div>
                            <div class="value">{{ $order->contract->nextPayment->payment_date }}</div>
                        </div>
                    @endif
                    <div class="col-12 col-sm-6 col-md part">
                        <div class="caption">{{__('cabinet/order.lbl_payments_count')}}</div>
                        <div class="value sb">{{ count($order->contract->activePayments) }} / {{ count($order->contract->schedule) }}</div>
                    </div>
                    <div class="col-12 col-sm-6 col-md part">
                        <div class="caption">{{__('cabinet/order.lbl_balance')}}</div>
                        <div class="value">{{ $order->contract->balance }}</div>
                    </div>
                    <div class="col-12 col-sm-6 col-md part total">
                        <div class="caption">{{__('cabinet/order.lbl_total')}}</div>
                        <div class="value">{{ $order->total }}</div>
                    </div>
                </div>
            </div><!-- /.params -->
        @endif

        <hr>
        <div class="partner">
            <div class="lead">{{__('cabinet/order.header_shop')}} {{$order->company->name}}</div>

            <div class="row">
                <div class="col-12 col-md-6">
                    <div class="part">
                        <div class="caption">{{__('cabinet/order.company_address')}}</div>
                        <div class="value">{{$order->company->address}}</div>
                    </div>
                    <div class="part">
                        <div class="caption">{{__('cabinet/order.company_legal_address')}}</div>
                        <div class="value">{{$order->company->legal_address}}</div>
                    </div>
                    <div class="part">
                        <div class="caption">{{__('cabinet/order.company_inn')}}</div>
                        <div class="value">{{$order->company->inn }}</div>
                    </div>
                    <div class="part">
                        <div class="caption">{{__('cabinet/order.company_bank_name')}}</div>
                        <div class="value">{{$order->company->bank_name }}</div>
                    </div>
                    <div class="part">
                        <div class="caption">{{__('cabinet/order.company_payment_account')}}</div>
                        <div class="value">{{$order->company->payment_account }}</div>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    @if($order->shipping_code)
                        <div class="row part">
                            <div class="col-12 col-md-3 caption">{{__('cabinet/order.lbl_shipping_name')}}</div>
                            <div class="col-12 col-md-9 value">
                                {{$order->shipping_name}}
                            </div>
                        </div>
                        <div class="row part">
                            <div class="col-12 col-md-3 caption">{{__('cabinet/order.lbl_shipping_address')}}</div>
                            <div class="col-12 col-md-9 value">
                                {{$order->shipping_address}}
                            </div>
                        </div>
                    @endif
                </div>
            </div><!-- /.row -->


        </div><!-- /.partner -->

        <hr>

        <div class="products">
            <div class="lead">{{__('billing/order.lbl_products')}} - {{count($order->products)}}</div>
            <table class="table">
                <thead>
                    <th colspan="2">{{__('cabinet/order.lbl_product_name')}}</th>
                    <th><span class="d-none d-sm-inline">{{__('cabinet/order.lbl_price')}}</span></th>
                    <th><span class="d-none d-sm-inline">{{__('cabinet/order.lbl_amount')}}</span></th>
                    <th><span class="d-none d-sm-inline">{{__('cabinet/order.lbl_total')}}</span></th>
                </thead>
                <tbody>
                @foreach($order->products as $product)
                    <tr>
                        <td>
                            @if($product->preview)
                                <div class="img preview" style="background-image: url({{$product->preview}});"></div>
                            @else
                                <div class="img no-preview"></div>
                            @endif
                        </td>
                        <td>
                            <div class="vendor-code">{{__('billing/order.lbl_product_code')}}: {{$product->vendor_code}}</div>
                            <div class="name">{{$product->name }}</div>
                        </td>
                        <td>{{$product->price }}</td>
                        <td class="amount">x{{$product->amount }}</td>
                        <td>
                            <div class="total">{{$product->price*$product->amount }}</div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div><!-- /.products -->

    </div><!-- /.details -->

    @if($order->contract && $order->status != 5)

        @include('cabinet.order.parts.schedule')
        @include('cabinet.order.parts.offer')
        @include('cabinet.order.parts.repay')
    @endif

@endsection

