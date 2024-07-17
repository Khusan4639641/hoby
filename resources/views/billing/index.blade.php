@extends('templates.billing.app')

@section('class', 'index')

@section('title', __('billing/index.header_index'))

@section('content')
    <section class="orders list">
        <div class="header-row">
            <div class="title">{{__('billing/index.header_orders')}}</div>
            <div class="action">
                <a
                    role="button"
                    href="{{localeRoute('billing.orders.index')}}"
                    class="text-orange"
                >
                    {{__('billing/index.txt_all_orders')}}
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9.504 5.57031L15.934 12.0003L9.504 18.4303" stroke="#FF7643" stroke-width="0.7"
                              stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </a>
            </div>
        </div>
        @if(count($orders) > 0)
            <div class="dataTables_wrapper">
                <div class="orders-list">

                    @foreach($orders as $item)
                        <div class="item" data-link="{{localeRoute('billing.orders.show', $item->id)}}">
                            <div class="row top">
                                <div class="col-12 d-flex align-items-stretch pr-0">
                                    <div class="info row align-items-center">
                                        <div class="col-12 col-md-8 mb-1 mb-md-0">
                                            <span class="number font-weight-bold font-size-18">
                                                {{__('billing/order.header_order')}} № {{ $item->id }}
                                            </span>
                                            <br>
                                            <span
                                                class="date">{{__('billing/order.lbl_from')}} {{ $item->created_at }}</span>
                                        </div>
                                        <div class="col-12 col-md-4 pr-0">
                                            <div
                                                class="order-status-container {{ $item->status !== 5 ? 'banned' : 'completed' }}">
                                                {{ $item->status_caption }}
                                            </div>
                                        </div>
                                    </div><!-- /.info.row -->
                                </div>
                            </div><!-- /.row -->

                            <table class="products">
                                <thead>
                                <th>{{__('billing/order.lbl_product')}}</th>
                                {{--                                <th>{{__('billing/order.lbl_product')}}</th>--}}
                                <th><span class="d-sm-inline">{{__('billing/order.lbl_product_price')}}</span>
                                </th>
                                <th><span class="d-sm-inline">{{__('billing/order.lbl_product_amount')}}</span>
                                </th>
                                <th><span class="d-sm-inline">{{__('billing/order.lbl_total')}}</span></th>
                                <th class="d-none d-md-table-cell"></th>
                                </thead>
                                <tbody>
                                @foreach($item->products as $index => $product)
                                    <tr class="product">
                                        {{--                                        <td>--}}
                                        {{--                                            @if($product->preview)--}}
                                        {{--                                                <div class="img preview"--}}
                                        {{--                                                     style="background-image: url({{$product->preview}});"></div>--}}
                                        {{--                                            @else--}}
                                        {{--                                                <div class="img no-preview"></div>--}}
                                        {{--                                            @endif--}}
                                        {{--                                        </td>--}}
                                        <td class="name">{{ $product->name }}</td>
                                        <td>{{ number_format($product->price, 2, '.', ' ') }}</td>
                                        <td>x{{ $product->amount }}</td>
                                        <td>
                                            <div>{{ number_format($product->price*$product->amount, 2, '.', ' ') }}</div>
                                        </td>
                                        {{--                                        @if($index === 0)--}}
                                        {{--                                            <td class="d-none d-md-table-cell controls"--}}
                                        {{--                                                rowspan="{{count($item->products)}}">--}}
                                        {{--                                                <div class="readmore"></div>--}}
                                        {{--                                            </td>--}}
                                        {{--                                        @endif--}}
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>

                            {{--                            <a :href="item.detailLink"--}}
                            {{--                               class="mt-1 d-block d-md-none btn btn-outline-primary">--}}
                            {{--                                {{__('billing/order.btn_readmore')}}--}}
                            {{--                            </a>--}}
                            <div
                                class="col-12 col-sm part total d-flex justify-content-between align-items-center mt-3 px-0">
                                <div>
                                    <a role="button" href="{{ localeRoute('billing.orders.show', $item->id) }}"
                                       class="btn btn-orange text-white">{{ __('app.btn_more') }}</a>
                                </div>
                                <div>
                                    <span class="total-price">{{__('billing/catalog.price_total')}}: &nbsp;</span>
                                    <span class="value text-orange font-weight-bold font-size-20">
                                {{ number_format($item->total, 2, '.', ' ') }}
                            </span>
                                </div>
                            </div>
                        </div><!-- /.item -->
                    @endforeach

                </div><!-- /.orders-list -->

            </div><!-- /.dataTables_wrapper -->

            {{--            <div class="controls">--}}
            {{--                <a href="{{localeRoute('billing.orders.index')}}"--}}
            {{--                   class="btn btn-orange">--}}
            {{--                    {{__('billing/index.txt_all_orders')}}--}}
            {{--                </a>--}}
            {{--            </div>--}}

            {{--            <script>--}}
            {{--            $(document).ready(function () {--}}
            {{--                $('.orders-list .item').click(function () {--}}
            {{--                    let link = $(this).data('link');--}}
            {{--                    if (link) window.location.href = link;--}}
            {{--                });--}}
            {{--            });--}}
            {{--            </script>--}}
        @else
            <p>{{__('billing/index.txt_order_not_found')}}</p>
        @endif
    </section><!-- /.orders -->


    {{--    <section class="company">--}}
    {{--        <div class="logo">--}}
    {{--            @if($info['logo'])--}}
    {{--                <img class="mb-4" height="70px" src="{{$info['logo']}}">--}}
    {{--            @else--}}
    {{--                <div class="img no-preview"></div>--}}
    {{--            @endif--}}
    {{--        </div>--}}
    {{--        <div class="h1">{{$info['company_name']}}</div>--}}

    {{--        <table class="table">--}}
    {{--            <tr>--}}
    {{--                <td>{{__('billing/index.api_token')}}</td>--}}
    {{--                <td>{{$info['api_token']}}</td>--}}
    {{--            </tr>--}}
    {{--            <tr>--}}
    {{--                <td>{{__('billing/profile.company_inn')}}</td>--}}
    {{--                <td>{{$info['company_inn']}}</td>--}}
    {{--            </tr>--}}
    {{--        </table>--}}
    {{--        <div class="description">{!! $info['company_description']!!}lorem ipsum dolor sit amet</div>--}}
    {{--        <div class="form-controls">--}}
    {{--            <a class="btn btn-primary"--}}
    {{--               href="{{localeRoute('billing.profile.edit')}}">{{__('billing/profile.btn_edit_data')}}</a>--}}
    {{--        </div>--}}
    {{--    </section>--}}
    <section class="d-flex p-4 company-info">
        <div class="mr-2 mr-md-3 mr-lg-5">
            @if($info['logo'])
                <img class="mb-4" height="70px" src="{{$info['logo']}}">
            @else
                <img src="{{ asset('/images/media/noimage.svg') }}" width="100" class="bg-grey">
            @endif
        </div>
        <div class="pt-4">
            <p class="company-name">{{ $info['company_name'] }}</p>
            {{--            <p>ID {{ $info['company_id'] }}</p>--}}
        </div>
    </section>
@endsection
