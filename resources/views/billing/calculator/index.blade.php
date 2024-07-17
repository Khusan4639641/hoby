@extends('templates.billing.app')
@section('title', __('billing/menu.calculator'))
@section('class', 'orders edit')

@section('center-header-control')
    <a href="{{localeRoute('billing.orders.index')}}" class="btn btn-orange">{{__('app.btn_back')}}</a>
@endsection

{{--@section('center-header-prefix')--}}
{{--    <a class="link-back" href="{{localeRoute('billing.orders.index')}}"><img--}}
{{--            src="{{asset('images/icons/icon_arrow_orange.svg')}}"></a>--}}
{{--@endsection--}}

@section('content')
    <div>
        <form class="edit" method="POST" action="{{localeRoute('billing.orders.store')}}">
            @csrf

            <div class="buyer show">

                <input type="hidden" value="@{{buyer.id}}" name="user_id">
                <div class="error" v-if="'user_id' in errors">
                    @{{ errors.user_id }}
                </div>

            </div>

            <div class="products">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="lead mb-0">{{__('billing/order.lbl_products')}}</div>
                    <div class="font-size-18 text-right">
                        <span>{{ __('billing/catalog.product_price_total') }}</span>
                        <span class="text-orange font-weight-bold">@{{formatPrice(total)}} {{__('app.currency')}}</span>
                    </div>
                </div>
                <div class="list">
                    <div class="item" v-for="(item, index) in products" :key="index">
                        <product
                            :product="item"
                            :index="index"
                            @delete-product="deleteProduct(index)"
                        ></product>
                    </div><!-- /.item -->
                </div><!-- /.list -->
                <div class="form-group">
                    <button type="button" @click="addProductManually"
                            {{--                            :disabled="buyer == null"--}}
                            class="btn btn-orange">
                        {{__('billing/order.btn_add_product')}}
                    </button>
                </div>
            </div>

            <div v-if="productEmpty != null" class="alert alert-danger">@{{ productEmpty }}</div>

            <hr>

            <div class="lead">{{__('billing/order.lbl_calculate')}}</div>

            <!--  показываем депозитный взнос, если есть ---->
            <div v-if="deposit_message">
                <div
                    v-for="item in deposit_message"
                    :class="'alert alert-' + item.type">
                    {{__('app.deposit')}} @{{ item.deposit }} {{__('app.currency')}}
                </div>
            </div>

            <div class="form-row order-total">
                <div class="form-group col-12 col-sm-6 col-md">
                    <label>{{__('billing/order.lbl_period')}}</label>
                    <div class="">
                        <select
                            v-model="period"
                            ref="selectPeriod"
                            name="period"
                            :class="'form-control modified' + (errors.period?' is-invalid':'')"
                            @change="calculateOrder()"
                        >
                            <option value="0">{{__('billing/order.txt_select_period')}}</option>
                            <option v-for="(item, index) in config_plans" :value="index">
                                @{{index}} {{__('app.months_1')}}
                            </option>
                        </select>
                    </div>
                    <div class="error" v-if="'period' in errors">
                        @{{ errors.period }}
                    </div>
                </div>
                <div class="form-group col-12 col-sm-6 offset-md-1 col-md mb-sm-0 mb-3 d-flex align-items-center">
                    <div class="icon-container">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                             xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M21.317 8.68237V18.0144H5.31698M2.68298 5.98438H18.683V15.3164H2.68298V5.98438ZM12.082 10.6504C12.082 11.7908 11.4556 12.7154 10.683 12.7154C9.91034 12.7154 9.28398 11.7908 9.28398 10.6504C9.28398 9.50991 9.91034 8.58538 10.683 8.58538C11.4556 8.58538 12.082 9.50991 12.082 10.6504Z"
                                stroke="#fff" stroke-miterlimit="10" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                    </div>
                    <div>
                        <label class="font-weight-bold">{{__('billing/order.lbl_payment_monthly')}}</label>
                        <div class="value monthly">@{{formatPrice(paymentMonthly)}} {{__('app.currency')}}</div>
                    </div>
                </div>
                <div class="form-group col-12 col-sm-6 col-md mb-0 d-flex align-items-center">
                    <div class="icon-container">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                             xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M21.317 8.68237V18.0144H5.31698M2.68298 5.98438H18.683V15.3164H2.68298V5.98438ZM12.082 10.6504C12.082 11.7908 11.4556 12.7154 10.683 12.7154C9.91034 12.7154 9.28398 11.7908 9.28398 10.6504C9.28398 9.50991 9.91034 8.58538 10.683 8.58538C11.4556 8.58538 12.082 9.50991 12.082 10.6504Z"
                                stroke="#FF7643" stroke-miterlimit="10" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                    </div>
                    <div class="total">
                        <label class="font-weight-bold">{{__('billing/order.lbl_price_total_credit')}}</label>
                        <div class="value">@{{formatPrice(totalCredit)}}&nbsp;{{__('app.currency')}}</div>
                    </div>

                </div>

                <div v-if="calculating" class="loading active"><img src="{{asset('images/media/loader.svg')}}"></div>
            </div>

            <div v-if="message">
                <div v-for="item in message" :class="'alert alert-' + item.type">@{{ item.text }}</div>
            </div>
        </form>

    </div>

    <div v-if="loading" class="loading active"><img src="{{asset('images/media/loader.svg')}}"></div>

    @include('billing.calculator.parts.create')
@endsection
