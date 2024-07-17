@extends('templates.billing.app')
@section('class', 'buyer show')

@section('title', $buyer->fio)
@section('h1', __('billing/buyer.header_buyers'))

@section('center-header-prefix')
    <a class="link-back" href="{{localeRoute('billing.buyers.index')}}"><img src="{{asset('images/icons/icon_arrow_orange.svg')}}"></a>
@endsection

@section('center-header-control')
    <a href="{{localeRoute('billing.buyers.create')}}" class="btn btn-primary ml-auto btn-plus">
        {{__('billing/buyer.btn_create')}}
    </a>
@endsection

@section('content')

    <div class="user-card">

        @if($buyer->personals->passport_selfie)
            <div class="preview" style="background-image: url(/storage/{{$buyer->personals->passport_selfie->path}});"></div>
        @else
            <div v-else class="preview dummy"></div>
        @endif

        <div class="info">
            <div class="id">ID {{ $buyer->id }}</div>
            <div class="name">{{ $buyer->surname }} {{ $buyer->name }} {{ $buyer->patronymic }}</div>
            <div class="phone">{{ $buyer->phone }}</div>

            <div class="row params">
                <div class="col part">
                    <div class="caption">{{__('billing/order.lbl_buyer_rating')}}</div>
                    <div class="value">{{ @$buyer->settings->rating }}</div>
                </div>
                <div class="col part">
                    <div class="caption">{{__('billing/order.lbl_buyer_balance')}}</div>
                    <div class="value">{{ number_format(@$buyer->settings->balance,2,'.',' ') }} {{__('app.currency')}}</div>
                </div>
                <div class="col part">
                    <div class="caption">{{__('billing/order.lbl_buyer_limit')}}</div>
                    <div class="value">{{ number_format(@$buyer->settings->limit,2,'.',' ') }} {{__('app.currency')}}</div>
                </div>
                <div class="col part total">
                    <div class="caption">{{__('billing/order.lbl_buyer_period')}} ({{__('app.currency')}})</div>
                    <div class="value">{{ @$buyer->settings->period }}</div>
                </div>
            </div>
        </div>
    </div>


    <div class="orders list">

        <ul class="nav nav-tabs" id="orderStatus" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link active" @click="changeStatus('approve')" data-toggle="tab" href="#" role="tab" aria-selected="true">{{__('billing/order.txt_approve')}} ({{$counter['approve']}})</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" @click="changeStatus('active')" data-toggle="tab" href="#" role="tab" aria-selected="false">{{__('billing/order.txt_active')}} ({{$counter['active']}})</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" @click="changeStatus('payment')" data-toggle="tab" href="#" role="tab" aria-selected="false">{{__('billing/order.txt_payment')}} ({{$counter['payment']}})</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" @click="changeStatus('complete')" data-toggle="tab" href="#" role="tab" aria-selected="false">{{__('order.status_9')}}  ({{$counter['complete']}})</a>
            </li>
        </ul>

        {{--<div class="dataTablesSearch" id="dataTablesSearch">
            <div class="input-group">
                <input type="text" v-model="searchString" class="form-control" >
                <div class="input-group-append">
                    <button @click="updateList()" class="btn btn-success btn-search" type="button">{{__('app.btn_find')}}</button>
                </div>
            </div>
        </div>--}}

        <div v-if="loading" class="loading active"><img src="{{asset('images/media/loader.svg')}}"></div>
        <div v-else>
            <div class="orders">
                <div v-if="orders != null" class="dataTables_wrapper">
                    <div class="orders-list">
                        <div @click="detail(item.detailLink)" class="item" v-for="item in orders" :key="item.id">
                            <div class="row top">
                                <div class="col-12 col-sm-6 d-flex align-items-stretch pr-0">
                                    <div class="info row align-items-center">
                                        <div class="col-12 col-md-8 mb-1 mb-md-0">
                                            <span class="number">{{__('billing/order.header_order')}} № @{{ item.id }}</span>
                                            <span class="date">{{__('billing/order.lbl_from')}} @{{ item.created_at }}</span>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <div :class="'status status-'+ item.status">@{{ item.status_caption }}</div>
                                        </div>
                                    </div><!-- /.info.row -->
                                </div>
                                <div class="col-12 col-sm-6 pl-md-0">

                                    <div class="params">
                                        <div class="row ">
                                            <div class="col-12 col-sm part">
                                                <div class="caption">{{__('billing/order.lbl_debit')}}</div>
                                                <div class="value">@{{ item.credit }}</div>
                                            </div>
                                            <div class="col-12 col-sm part">
                                                <div class="caption">{{__('billing/order.lbl_credit')}}</div>
                                                <div class="value">@{{ item.debit }}</div>
                                            </div>
                                            <div class="col-12 col-sm part total">
                                                <div class="caption">{{__('billing/order.lbl_total')}}</div>
                                                <div class="value">@{{ item.total }} {{__('app.currency')}}</div>
                                            </div>
                                        </div><!-- /.row -->
                                    </div><!-- /.params -->

                                </div>
                            </div><!-- /.row -->

                            <table class="products">
                                <thead>
                                <th colspan="2">{{__('billing/order.lbl_product')}}</th>
                                <th><span class="d-none d-sm-inline">{{__('billing/order.lbl_product_price')}}</span></th>
                                <th><span class="d-none d-sm-inline">{{__('billing/order.lbl_product_amount')}}</span></th>
                                <th><span class="d-none d-sm-inline">{{__('billing/order.lbl_total')}}</span></th>
                                <th class="d-none d-md-table-cell"></th>
                                </thead>
                                <tbody>
                                <tr class="product" v-for="(product, index) in item.products">
                                    <td>
                                        <div class="img preview" v-if="product.preview" :style="'background-image: url(' + product.preview +');'"></div>
                                        <div class="img no-preview" v-else></div>
                                    </td>
                                    <td class="name">@{{ product.name }}</td>
                                    <td>@{{ product.price }}</td>
                                    <td class="amount">x@{{ product.amount }}</td>
                                    <td>
                                        <div class="total">@{{ product.price*product.amount }}</div>
                                    </td>
                                    <td class="d-none d-md-table-cell controls" v-if="index === 0" :rowspan="item.products.length">
                                        <div class="readmore"></div>
                                    </td>
                                </tr>
                                </tbody>
                            </table>

                            <a :href="item.detailLink" class="mt-1 d-block d-md-none btn btn-outline-primary">{{__('billing/order.btn_readmore')}}</a>
                        </div><!-- /.item -->

                    </div><!-- /.orders-list -->

                    <div class="dataTables_paginate">
                        <a @click="paginate(current - 1)" :class="'paginate_button previous ' + (current -1 < 1?'disabled':'')" :data-dt-idx="(current-1 >= 1?current-1:1)" tabindex="-1" id="DataTables_Table_0_previous">Предыдущая</a>
                        <span v-for="n in total">
                        <a @click="paginate(n)" :class="'paginate_button ' + (n==(current + 1)?'current':'')" :data-dt-idx="n" tabindex="0">1</a>
                    </span>
                        <a @click="paginate(current + 1)" class="paginate_button next disabled" :data-dt-idx="(current+1 <= total?current+1:total)" tabindex="-1" id="DataTables_Table_0_next">Следующая</a>
                    </div>
                </div><!-- /.dataTables_wrapper -->
            </div>
            <!-- /.orders -->
            <div class="alert alert-info mt-3" v-if="orders == null">
                {{__('billing/order.txt_empty_list')}}
            </div>
        </div>

        @include('billing.buyer.parts.detail')

    </div><!-- /.orders.list -->

@endsection
