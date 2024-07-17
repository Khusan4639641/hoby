@extends('templates.cabinet.app')

@section('h1', __('cabinet/order.header_orders'))
@section('title', __('cabinet/order.header_orders'))
@section('center-header-prefix')
    <a class="link-back" href="{{localeRoute('cabinet.index')}}"><img src="{{asset('images/icons/icon_arrow_green.svg')}}"></a>
@endsection

@section('class', 'orders list')


@section('content')

    <ul class="nav nav-tabs" id="orderStatus" role="tablist">
        <li class="nav-item" role="presentation">
            <a :class="'nav-link' + (hash == '#active'?' active':'')" @click="changeStatus('active')" data-toggle="tab" href="#" role="tab" aria-selected="true">{{__('cabinet/order.status_active')}} ({{$counter['active']}})</a>
        </li>
        <li class="nav-item" role="presentation">
            <a :class="'nav-link' + (hash == '#approve'?' active':'')" @click="changeStatus('approve')" data-toggle="tab" href="#" role="tab" aria-selected="false">{{__('cabinet/order.status_approve')}} ({{$counter['approve']}})</a>
        </li>
        @if(Auth::user()->status == 4)
            <li class="nav-item" role="presentation">
                <a :class="'nav-link' + (hash == '#credit'?' active':'')" @click="changeStatus('credit')" data-toggle="tab" href="#" role="tab" aria-selected="false">{{__('cabinet/order.status_credit')}} ({{$counter['credit']}})</a>
            </li>
        @endif
        <li class="nav-item" role="presentation">
            <a :class="'nav-link' + (hash == '#complete'?' active':'')" @click="changeStatus('complete')" data-toggle="tab" href="#" role="tab" aria-selected="false">{{__('cabinet/order.status_complete')}} ({{$counter['complete']}})</a>
        </li>
    </ul>

    <div class="dataTablesSearch" id="dataTablesSearch">
        <div class="input-group">
            <input type="text" v-model="searchString" class="form-control" >
            <div class="input-group-append">
                <button @click="updateList()" class="btn btn-success btn-search" type="button">{{__('app.btn_find')}}</button>
            </div>
        </div>
    </div>

    <div v-if="loading" class="loading active"><img src="{{asset('images/media/loader.svg')}}"></div>
    <div v-else>
        <div v-if="orders != null" class="dataTables_wrapper">
            <div class="orders-list">
                <div class="item" v-for="item in orders" :key="item.id">

                    <div class="info">
                        <div class="row">
                            <div class="col-12 col-sm-7 contract-title mb-2 mb-sm-0">
                                <span class="number">{{__('cabinet/order.header_order')}} № @{{ item.id }}</span> <span class="date">{{__('cabinet/order.txt_from')}} @{{ item.created_at }}</span>
                            </div>

                            <div class="col-12 col-sm-5 text-sm-right mb-2 mb-sm-0">
                                <span :class="'order-status status-'+ item.status">@{{ item.status_caption }}</span>
                                <span v-if="item.contract && item.status != 5" :class="'contract-status status-'+ item.contract.status">@{{ item.contract.status_caption }}</span>
                            </div>
                        </div>
                    </div><!-- /.info -->

                    <div v-if="item.contract" class="params">
                        <div class="row">
                            <div v-if="item.contract.next_payment" class="col-12 col-sm-6 col-md part">
                                <div class="caption">{{__('cabinet/order.lbl_payment_amount')}}</div>
                                <div class="value">@{{ item.contract.next_payment.total }}</div>
                            </div>
                            <div v-if="item.contract.next_payment" class="col-12 col-sm-6 col-md part">
                                <div class="caption">{{__('cabinet/order.lbl_payment_date')}}</div>
                                <div class="value">@{{ item.contract.next_payment.payment_date }}</div>
                            </div>
                            <div class="col-12 col-sm-6 col-md part">
                                <div class="caption">{{__('cabinet/order.lbl_payments_count')}}</div>
                                <div class="value">@{{ item.contract.active_payments.length }} / @{{ item.contract.schedule.length }}</div>
                            </div>
                            <div v-if="item.totalDebt > 0" class="debt col-12 col-sm-6 col-md part">
                                <div class="caption">{{__('cabinet/order.lbl_debt')}}</div>
                                <div class="value">@{{ item.totalDebt }}</div>
                            </div>
                            <div v-else class="col-12 col-sm-6 col-md part">
                                <div class="caption">{{__('cabinet/order.lbl_balance')}}</div>
                                <div class="value">@{{ item.contract.balance }}</div>
                            </div>

                            <div class="col-12 col-sm-6 col-md part total">
                                <div class="caption">{{__('cabinet/order.lbl_total')}}</div>
                                <div class="value">@{{ item.total }}</div>
                            </div>
                        </div>
                    </div><!-- /.params -->
                    <div v-else class="params">
                        <div class="col-12 col-sm-6 col-md part total">
                            <div class="caption">{{__('cabinet/order.lbl_total')}}</div>
                            <div class="value">@{{ item.total }}</div>
                        </div>
                    </div>


                    <div class="products">
                        <div class="products-list">
                            <table class="table">
                                <thead>
                                <th colspan="2">{{__('cabinet/order.lbl_product_name')}}</th>
                                <th><span class="d-none d-sm-inline">{{__('cabinet/order.lbl_price')}}</span></th>
                                <th class="d-none d-md-inline"><span>{{__('cabinet/order.lbl_amount')}}</span></th>
                                <th><span class="d-none d-sm-inline">{{__('cabinet/order.lbl_total')}}</span></th>
                                <th class="d-none d-md-table-cell"></th>
                                </thead>
                                <tbody>
                                <tr class="product" v-for="(product, index) in item.products">
                                    <td>
                                        <div class="img preview" v-if="product.preview" :style="'background-image: url(' + product.preview +');'"></div>
                                        <div class="img no-preview" v-else></div>
                                    </td>
                                    <td class="name">@{{ product.name }}</td>
                                    <td class="price d-none d-md-table-cell">@{{ product.price }}</td>
                                    <td class="amount">x@{{ product.amount }}</td>
                                    <td class="total">
                                        @{{ product.price*product.amount }}
                                    </td>

                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <a :href="item.detailLink" class="mt-1 btn btn-outline-primary">{{__('cabinet/order.btn_readmore')}}</a>
                    </div><!-- /.products -->

                </div><!-- /.item -->
            </div><!-- /.orders-list -->

            <div class="dataTables_paginate">
                <a @click="paginate(current - 1)" :class="'paginate_button previous ' + (current -1 < 1?'disabled':'')" :data-dt-idx="(current-1 >= 1?current-1:1)" tabindex="-1" id="DataTables_Table_0_previous">Предыдущая</a>
                <span v-for="n in total">
                    <a @click="paginate(n)" :class="'paginate_button ' + (n==(current + 1)?'current':'')" :data-dt-idx="n" tabindex="0">@{{ n }}</a>
                </span>
                <a @click="paginate(current + 1)" class="paginate_button next disabled" :data-dt-idx="(current+1 <= total?current+1:total)" tabindex="-1" id="DataTables_Table_0_next">Следующая</a>
            </div>
        </div>
        <div v-else>
            {{__('billing/order.txt_empty_list')}}
        </div>
    </div>

    @include('cabinet.order.parts.list')

@endsection
