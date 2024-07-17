@extends('templates.billing.app')

@section('title', __('billing/order.header_orders'))
@section('class', 'orders list')

@section('center-header-control')
    <a href="{{localeRoute('billing.orders.create')}}" class="btn btn-orange">
        {{__('billing/order.btn_create_order')}}
    </a>
@endsection

@section('content')
    @{{ error }}

    @include('billing.order.parts.navigation-tabs')

    @if(Session::has('msg'))
        <h3 class="alert alert-info text-center mt-3">{{ session('msg') }}</h3>
    @endif
    <div v-if="loading" class="loading active"><img src="{{asset('images/media/loader.svg')}}"></div>
    <div v-else>
        <div v-if="orders != null" class="dataTables_wrapper">
            <div class="orders-list">
                <div v-if="item.contract" class="item" v-for="(item, index) in orders" :key="index">
                    <h4 v-if="item.contract.cancellation_status === 2"
                        class="error text-danger text-center ">{{__('billing/order.act_cancellation_denied')}}
                        <br> {{__('billing/order.contract')}} №@{{item.contract.id}}
                        <hr>
                    </h4>
                    <h4 v-else-if="item.contract.cancellation_status === 1"
                        class="successfully_finished text-center">{{__('billing/order.act_cancellation_sent')}}
                        <br> {{__('billing/order.contract')}} №@{{item.contract.id}}
                        <hr>
                    </h4>
                    <div class="row top mb-3">
                        <div class="col-12 d-flex align-items-stretch pr-0">
                            <div class="info row align-items-center">
                                <div class="col-12 col-md-8 mb-1 mb-md-0 d-flex">
                                    <div style="word-break: break-word; width: 55%">
                                        <span class="number">
                                            {{__('billing/order.header_order_with_client')}} № @{{ item.contract ? item.contract.id : '-' }}
                                        </span>
                                        <br>
                                        <span class="date">
                                            {{__('billing/order.lbl_from')}} @{{ item.contract?.created_at }}
                                        </span>
                                        <br>
                                        <span v-if="item.contract.status == 5 && item.contract.contract_cancellation_reason != ''">
                                            @{{ item.contract.contract_cancellation_reason }}
                                        </span>
                                    </div>

                                    <div
                                        v-if="item.contract.status !== 4 && item.contract.status !== 3 && item.contract.status !== 0">
                                        <span class="number">
                                            @{{ item.buyer?.surname }}
                                            @{{ item.buyer?.name }}
                                            @{{ item.buyer?.patronymic }}
                                        </span>
                                        <br>
                                        <span
                                            class="date">{{ __('account.phone_short') }} @{{ item.buyer?.phone }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4 pr-0">
                                    <div :class="
                                        {
                                            'order-status-container': true,
                                            'banned': item.contract.status === 3 || item.contract.status === 4,
                                            'completed': item.contract.status === 1,
                                            'cancel': item.contract.status === 5 || item.contract.status === 9 || item.contract.status === 2 ,
                                            'active': item.contract.status === 0 || item.contract.status === 10,
                                        }"
                                    >
                                        @{{ item.contract.status_caption }}
                                    </div>
                                </div>
                            </div><!-- /.info -->
                        </div>
                    </div><!-- /.row.top -->
                    <div v-if="item.contract.status === 0 || item.contract.status === 3 || item.contract.status === 4"
                         class="row">
                        <div class="col-md-3 lottie">
                            <div
                                v-if="item.contract.status === 3 || item.contract.status === 4"
                                class="lottie-container d-flex justify-content-center"
                                style="width: 250px"
                            >
                                <lottie-player
                                    src="{{ asset('assets/json/connection-error.json') }}"
                                    background="transparent"
                                    speed="1"
                                    style="width: 140px"
                                    loop
                                    autoplay
                                ></lottie-player>
                            </div>
                            <div
                                v-else
                                class="lottie-container"
                                style="width: 250px"
                            >
                                <lottie-player
                                    src="{{ asset('assets/json/confirm.lottie.json') }}"
                                    background="transparent"
                                    speed="1"
                                    style="width: 250px"
                                    loop
                                    autoplay
                                ></lottie-player>
                            </div>
                        </div>

                        <div class="col-md-9 d-flex flex-column justify-content-center">

                            <div v-if="item.contract.status === 3 || item.contract.status === 4"
                                 class="font-weight-900 font-size-32 mb-4 text">
                                {{__('billing/order.text_expired')}}
                            </div>
                            <div v-else class="font-weight-900 font-size-32 mb-4 text">
                                {{__('billing/order.txt_sms_confirm_sent')}}
                            </div>

                            <div class="user-info row">
                                <div class="col-md-4">
                                    <div class="mb-2 font-weight-bold font-size-18">
                                        @{{ item.buyer.surname }} @{{ item.buyer.name }} @{{ item.buyer.patronymic }}
                                    </div>
                                    <div class="mb-2" style="font-weight: 600;">
                                        {{ __('account.phone_short') }} @{{ phoneFormat(item.buyer.phone) }}
                                    </div>
                                </div>
                                <div v-if="item.contract.status === 3 || item.contract.status === 4"
                                     class="col-md-4 d-flex align-items-center">
                                    <div class="d-flex">
                                        <div class="icon-container">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                 xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M21.317 8.68237V18.0144H5.31698M2.68298 5.98438H18.683V15.3164H2.68298V5.98438ZM12.082 10.6504C12.082 11.7908 11.4556 12.7154 10.683 12.7154C9.91034 12.7154 9.28398 11.7908 9.28398 10.6504C9.28398 9.50991 9.91034 8.58538 10.683 8.58538C11.4556 8.58538 12.082 9.50991 12.082 10.6504Z"
                                                    stroke="#FF7643" stroke-miterlimit="10" stroke-linecap="round"
                                                    stroke-linejoin="round"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="total">
                                        <label
                                            class="font-weight-bold font-size-16">{{__('cabinet/order.lbl_debt')}}</label>
                                        <div class="font-weight-bold text-orange">
                                            @{{formatPrice(item.totalDebt)}}&nbsp;{{__('app.currency')}}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <table class="products table-bordered">
                        <thead>
                        <th>{{__('billing/order.lbl_product')}}</th>
                        <th><span class="d-sm-inline">{{__('billing/order.lbl_product_amount')}}</span></th>
                        <th><span class="d-sm-inline">{{__('billing/order.lbl_product_price')}}</span></th>
                        <th><span class="d-sm-inline">{{__('billing/order.lbl_total')}}</span></th>
                        <th>{{ __('billing/order.lbl_nds') }}</th>
                        <th>{{ __('billing/order.lbl_total_nds') }}</th>
                        <th>{{ __('billing/order.lbl_all_with_nds') }}</th>
                        </thead>
                        <tbody>
                        <tr class="product" v-for="(product, index) in item.products">
                            <td>@{{ product.original_name ? product.original_name : product.name }}</td>
                            <td>
                                <div>x @{{ product.amount }}</div>
                            </td>
                            <td>
                                <div class="">@{{ numberFormat(product.price_without_nds)}}
                                </div>
                            </td>
                            <td>@{{ numberFormat(product.total_price) }}
                            </td>
                            <td>@{{ product.nds_percent }}</td>
                            <td>@{{ numberFormat(product.nds_sum) }}</td>
                            <td>@{{ numberFormat(product.price_discount * product.amount) }}</td>
                        </tr>
                        <tr>
                            <td colspan="3">{{ __('billing/order.lbl_result') }}</td>
                            <td>@{{ numberFormat(item.total_price) }}</td>
                            <td>@{{ item.nds * 100 }}</td>
                            <td>@{{ numberFormat(item.nds_sum) }}</td>
                            <td>@{{ numberFormat(item.partner_total) }}</td>
                        </tr>
                        </tbody>
                    </table><!-- /.products -->

                    <a :href="item.detailLink"
                       class="mt-1 d-block d-md-none btn btn-outline-primary">
                        {{__('billing/order.btn_readmore')}}
                    </a>
                    <div class="mt-3">
                        <div
                            v-if="item.contract.status === 1 || item.contract.status === 10 || item.contract.status === 3 || item.contract.status === 4"
                            class="row part total d-flex justify-content-between px-0"
                        >

                            <div class="col-md-3 mb-md-0 mb-2">
                                <a
                                    :href="item.contract.client_act ? sourcePath + item.contract.client_act.path : `${sourcePath}contract/${item.contract.id}/buyer_account_${item.contract.id}.pdf`"
                                    target="_blank"
                                    class="btn btn-orange mr-2"
                                    role="button"
                                    style="padding: 11px"
                                >
                                    {{__('offer.btn_download_act')}}
                                </a>
                            </div>

                            <div class="col-md-9 mb-md-0 mb-2">
                                <div class="row justify-content-end">
                                    <component v-if="item.contract?.act_status === 0" is="style">
                                        .custom-file-label:after {
                                        content: url("{{ asset('assets/icons/paperclip.svg') }}");
                                        width: 50px;
                                        }
                                    </component>

                                    {{-- Client photo Uploader --}}
                                    <div v-if="item.contract?.client_status == 0  || item.contract?.client_status == 2 "
                                         class="col-md-3 mb-md-0 mb-2">
                                        <div
                                            class="d-flex flex-row-reverse justify-content-between align-items-center custom-file">

                                            <div class="d-flex flex-row-reverse align-items-center">

                                                <a
                                                    class="btn btn-orange"
                                                    style="padding: 2px 7px"
                                                    :href="routeToJs(`{{localeRoute('billing.orders.cancel', ['%id%', '%type%'])}}`, {'id':item.id, 'type': 'client-photo'})"
                                                >
                                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                         xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M9.504 5.57031L15.934 12.0003L9.504 18.4303"
                                                              stroke="#FFF" stroke-width="0.7" stroke-miterlimit="10"
                                                              stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                </a>

                                            </div>

                                            <div class="uploader-text">
                                                <span :class="{ 'text-grey': imei.name }">
                                                    {{__('billing/order.txt_client_photo')}}
                                                </span>
                                                <br>
                                                <span v-if="imei.choose == item.contract?.id && client_photo.name">@{{ imei.name }}</span>
                                            </div>
                                        </div>

                                        <div v-if="imei.choose == item.contract?.id && imei.name == null"
                                             v-for="error in imei.message" class="text-danger">@{{ error.text }}
                                        </div>

                                    </div>
                                    {{-- Imei Uploader --}}
                                    <div
                                        v-if="(item.contract?.imei_status == 0 || item.contract?.imei_status == 2) && item.isCategoryMobile"
                                        class="col-md-3 mb-md-0 mb-2">
                                        <div
                                            class="d-flex flex-row-reverse justify-content-between align-items-center custom-file">
                                            <input
                                                type="file"
                                                id="imei"
                                                accept=".png, .jpg, .jpeg, .gif, .pdf"
                                                class="custom-file-input act"
                                                name="imei"
                                                :data-contractid="item.contract?.id"
                                                hidden
                                                @change="updateImeiFiles($event)"
                                            >

                                            <div class="d-flex flex-row-reverse align-items-center">

                                                <a
                                                    class="btn btn-orange"
                                                    style="padding: 2px 7px"
                                                    :href="routeToJs(`{{localeRoute('billing.orders.cancel', ['%id%', '%type%'])}}`, {'id':item.id, 'type': 'upload-imei'})"
                                                >
                                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                         xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M9.504 5.57031L15.934 12.0003L9.504 18.4303"
                                                              stroke="#FFF" stroke-width="0.7" stroke-miterlimit="10"
                                                              stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                </a>

                                            </div>

                                            <div class="uploader-text">
                                                <span :class="{ 'text-grey': imei.name }">
                                                    {{__('billing/order.txt_photo_imei')}}
                                                </span>
                                                <br>
                                                <span v-if="imei.choose == item.contract?.id && imei.name">@{{ imei.name }}</span>
                                            </div>
                                        </div>
                                        <div v-if="imei.choose == item.contract?.id && imei.name == null"
                                             v-for="error in imei.message" class="text-danger">@{{ error.text }}
                                        </div>

                                    </div>
                                    {{-- Act Uploader --}}
                                    <div v-if="item.contract?.act_status == 0 || item.contract?.act_status == 2"
                                         class="col-md-3 mb-md-0 mb-3">
                                        <div
                                            class="d-flex flex-row-reverse justify-content-between align-items-center custom-file mb-3">

                                            <div class="d-flex flex-row-reverse align-items-center">

                                                <a
                                                    class="btn btn-orange"
                                                    style="padding: 2px 7px"
                                                    :href="routeToJs(`{{localeRoute('billing.orders.cancel', ['%id%', '%type%'])}}`, {'id':item.id, 'type': 'upload-act'})"
                                                >
                                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                         xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M9.504 5.57031L15.934 12.0003L9.504 18.4303"
                                                              stroke="#FFF" stroke-width="0.7" stroke-miterlimit="10"
                                                              stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                </a>

                                            </div>

                                            <div class="uploader-text">
                                                <span :class="{ 'text-grey': act.name }">
                                                    {{__('billing/order.txt_photo_act')}}
                                                </span>
                                                <br>
                                                <span v-if="act.choose == item.contract?.id && act.name">
                                                    @{{ act.name }}
                                                </span>
                                            </div>
                                        </div>
                                        <div v-if="act.choose == item.contract?.id && act.name == null"
                                             v-for="error in act.message" class="text-danger">@{{ error.text }}
                                        </div>
                                    </div>
                                    {{-- Cancel Act Uploader --}}

                                    <div
                                        v-if="item.contract?.cancel_act_status !== 1 && item.isCancelBtnShow !== 1 && item.contract.cancellation_status !== 1 && item.manager_request !== 1 && item.contract.status !== 3 && item.contract.status !== 4"
                                        class="col-md-3 mb-md-0 mb-2"
                                    >
                                        <button @click="showPopup(true, item.id)" class="btn btn-red border-radius-8 w-100" style="padding: 11px; color: #fff;"> {{__('billing/order.btn_cancel_order')}}</button>

                                        <div v-if="cancel_act.choose == item.contract?.id && cancel_act.name == null"
                                             v-for="error in cancel_act.message" class="text-danger">@{{ error.text }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div
                        v-if="item.contract?.act_status === 2 && item.contract?.status !== 5"
                        class="text-danger bg-red border-radius-8 p-2 text-center w-auto mt-4 my-2"
                    >
                        {{__('billing/contract.act_status_2')}}
                    </div>
                    <div
                        v-if="item.contract?.imei_status === 2 && item.contract?.status !== 5"
                        class="text-danger bg-red border-radius-8 p-2 text-center w-auto mt-4 my-2"
                    >
                        {{__('billing/contract.imei_status_2')}}
                    </div>
                    <div
                        v-if="item.contract?.client_status === 2 && item.contract?.status !== 5"
                        class="text-danger bg-red border-radius-8 p-2 text-center w-auto mt-4 my-2"
                    >
                        {{__('billing/contract.client_photo_status_2')}}
                    </div>
                    <div
                        v-if="item.contract?.cancel_reason != null && item.contract?.cancel_reason === 'wrong name' && item.contract?.status === 5"
                        class="text-danger bg-red border-radius-8 p-2 text-center w-auto mt-4 my-2"
                    >
                        {{__('billing/contract.canceled_txt')}}
                    </div>
                    <div
                        v-if="item.contract?.cancel_reason != null && item.contract?.status === 10"
                        class="text-danger bg-red border-radius-8 p-2 text-center w-auto mt-4 my-2"
                    >
                        @{{item.contract?.cancel_reason}}
                    </div>
                </div>
            </div><!-- /.orders-list -->

            <div class="dataTables_paginate">
                <a @click="paginate(current)"
                   :class="'paginate_button previous ' + (current -1 < 1?'disabled':'')"
                   :data-dt-idx="(current-1 >= 1?current-1:1)" tabindex="-1"
                   id="DataTables_Table_0_previous">{{__('billing/index.prev')}}</a>
                <span v-for="n in total">
                    <a @click="paginate(n)" :class="'paginate_button ' + (n==(current + 1)?'current':'')"
                       :data-dt-idx="n" tabindex="0">@{{ n }}</a>
                </span>
                <a @click="paginate(current + 2)" class="paginate_button next disabled"
                   :data-dt-idx="(current+1 <= total?current+1:total)" tabindex="-1"
                   id="DataTables_Table_0_next">{{__('billing/index.next')}}</a>
            </div>
        </div>

        <div v-else>
            {{__('billing/order.txt_empty_list')}}
        </div>
    </div>

    <transition>
        <div v-if="visiblePopup" class="popup active">
            <form class="reason" enctype="multipart/form-data" :action="routeToJs(`{{localeRoute('billing.orders.cancel', ['%id%', '%type%'])}}`, {'id':checkedOrder, 'type': 'cancel'})">
                <button class="reason__close" @click.prevent="showPopup(false)">×</button>
                <h5 class="reason__title">{{__('billing/order.cancellation_reason')}}</h5>
                <textarea maxlength="256" name="cancellation_reason" required v-model="cancellationReason" ></textarea>
                <button :disabled="!cancellationReason.length" type="submit" class="btn btn-red border-radius-8 w-100" style="padding: 11px;">
                    {{__('billing/order.btn_cancel_order')}}
                </button>
            </form>
        </div>
    </transition>


    @include('billing.order.parts.list')


@endsection
