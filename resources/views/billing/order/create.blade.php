@extends('templates.billing.app')
@section('title', __('billing/order.header_create_order'))
@section('class', 'orders edit')
<link href="{{ asset('assets/css/vue-treeselect.min.css') }}" rel="stylesheet">
@section('center-header-control')
    <a href="{{localeRoute('billing.orders.index')}}" class="btn btn-orange">{{__('app.btn_cancel')}}</a>
@endsection

@section('content')
<style>

</style>
    <div v-if="status == 'success'" class="order-success">
        <div class="">
            <lottie-player
                src="{{ asset('assets/json/confirm.lottie.json') }}"
                background="transparent"
                speed="1"
                style="width: 300px; height: 300px"
                loop
                autoplay
            ></lottie-player>
        </div>

        <div class="d-flex flex-column justify-content-center">
            <div class="font-weight-900 font-size-40 mb-4 text">
                {{__('billing/order.txt_sms_confirm_sent')}}
            </div>
            <a class="btn btn-orange text-white modern-shadow"
               href="{{localeRoute('billing.orders.index')}}">{{__('app.btn_close')}}</a>
        </div>
    </div>

    <div v-else>
        <form class="edit" method="POST" action="{{localeRoute('billing.orders.store')}}">
            @csrf

            <div class="buyer show">
                <div v-if="buyer == null">
                    <div class="lead">{{__('billing/order.lbl_buyer')}}</div>
                    <div class="form-row">
                        <div class="form-group col-12 col-sm-6 col-md-4">
                            <label>{{__('billing/order.lbl_partner_phone')}}</label>
                            <input :disabled="processing_user" v-mask="'+998 (##) ###-##-##'" required type="text"
                                   placeholder="{{__("billing/order.txt_search_by_phone")}}"
                                   :class="'form-control modified ' + (processing_user?'processing':'')"
                                   v-model="strSearchPhone" />

                            <div v-if="buyers.length > 0" class="dropdown-menu show user-info-dropdown">
                                <a v-for="(item, index) in buyers" :key="item.id" class="dropdown-item"
                                   v-on:click="setBuyer(index)">
                                    @{{item.surname}} @{{item.name}} @{{item.patronymic}}
                                    (@{{item.phone}})
                                </a>
                            </div>
                            <div v-if="buyers === 404" class="dropdown-menu show user-info-dropdown">
                                <div>
                                    {{ __('user.statuslar_0')}}
                                </div>

                            </div>
                            <div v-if="buyers === 403" class="dropdown-menu show user-info-dropdown">
                                <div>
                                    {{ __('user.statuslar_403')}}
                                </div>

                            </div>
                            <div v-if="buyers === 13" class="dropdown-menu show user-info-dropdown">
                                <div>
                                    {{ __('user.statuslar_13')}}
                                </div>

                            </div>
                            <div v-if="buyers === 14" class="dropdown-menu show user-info-dropdown">
                                <div>
                                    {{ __('user.statuslar_14')}}
                                </div>

                            </div>

                        </div>
                    </div><!-- /.form-row -->

                    <hr>
                </div>
                <div v-else class="user-card">
                    <div class="lead">{{ __('billing/order.lbl_buyer') }}</div>
                    <div class="row align-items-center mb-3 mb-md-0">
                        <div v-if="buyer.status === 4" class="col-xl-1 col-lg-2 col-md-2 col-6 mr-lg-5">
                            <div class="preview ml-3 mr-0" v-if="buyer.personals.latest_id_card_or_passport_photo"
                                 :style="`background-image: url({{ \App\Helpers\FileHelper::sourcePath() }}${buyer.personals.latest_id_card_or_passport_photo.path});`"></div>
                            <div v-else class="preview dummy"></div>
                        </div>
                        <div class="col-lg-3 col-md-3 col-6 info">
                            <div class="pt-3">
                                <div v-if="buyer.status === 4" class="name mb-1 font-weight-bold font-size-24">
                                    @{{ buyer.surname }} @{{ buyer.name }} @{{ buyer.patronymic }}
                                </div>
                                {{--                                <div class="mb-1 font-weight-normal">ID @{{ buyer.id }}</div>--}}
                                <div class="font-weight-normal mb-2">{{ __('account.phone_short') }}@{{buyer.phone}}
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-5 col-md-4 col-6">
                            <div class="row pl-3 pl-md-0">
                                <div class="d-flex">
                                    <div v-if="buyer.status === 4" class="icon-container">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                             xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M21.317 8.68237V18.0144H5.31698M2.68298 5.98438H18.683V15.3164H2.68298V5.98438ZM12.082 10.6504C12.082 11.7908 11.4556 12.7154 10.683 12.7154C9.91034 12.7154 9.28398 11.7908 9.28398 10.6504C9.28398 9.50991 9.91034 8.58538 10.683 8.58538C11.4556 8.58538 12.082 9.50991 12.082 10.6504Z"
                                                stroke="#FF7643"
                                                stroke-miterlimit="10"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            />
                                        </svg>
                                    </div>
                                </div>
                                <div v-if="buyer.status === 4" class="total mr-0 mr-md-3">
                                    <label class="font-weight-bold">
                                        {{__('billing/order.lbl_buyer_balance')}}
                                    </label>
                                    <div class="value text-orange">
                                        @{{ formatPrice(buyer.settings.balance) }} {{__('app.currency')}}
                                    </div>
                                </div>

                                <div v-if="buyer.status === 4" class="total mr-0 mr-md-3">
                                    <label class="font-weight-bold">
                                        {{__('billing/order.lbl_buyer_personal_account')}}
                                    </label>
                                    <div class="value text-orange">
                                        @{{ formatPrice(buyer.settings.personal_account) }} {{__('app.currency')}}
                                    </div>
                                </div>

                                <div v-if="buyer.status !== 4" class="total ">
                                    <div class="value text-orange">
                                        @{{ buyer.status_caption }}
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div class="col-lg-2 col-md-3 col-6">
                            <div v-if="buyer.status === 4"
                                 class="user-status-container verified">{{ strtoupper(__('user.status_4'))}}</div>
                            {{--<div class="user-status-container verified">@{{ buyer.status_caption }}</div>--}}
                        </div>
                        <button @click="unsetBuyer" type="button" class="btn btn-transparent">
                            <img src="{{asset('images/icons/icon_close.svg')}}">
                        </button>
                    </div>

                </div>

                <div v-if="phonesCount >= 2" class="alert alert-danger">
                    {{ __('billing/order.txt_phones_count') }} ( @{{ phonesCount }} {{ __('offer.piece') }} )
                </div>

                <input type="hidden" value="@{{buyer.id}}" name="user_id">
                <div class="error" v-if="'user_id' in errors">
                    @{{ errors.user_id }}
                </div>

            </div>

            <div class="alert alert-danger" v-if="errors.buyer">
                @{{ errors.buyer }}
            </div>
            <div class="products">
                <div class="lead">{{__('billing/order.lbl_products')}}</div>
                <div class="list">
                    <div class="item" v-for="(item, index) in products" :key="index">
                    <product
                        :product="item"
                        :units="units"
                        :disablesmartphonescat="disableMobileCategory"
                        @update="(product) => updateProduct(index, product)"
                        @update:unit="(id)=> updateProductUnit(id, index)"
                        :isoverlayed="buyer === null || buyer.status !== 4"
                        :index="index"
                        :clarify="clarify"
                        @delete-product="deleteProduct"
                    >
                    </product>
                    </div><!-- /.item -->
                </div><!-- /.list -->
                <div class="form-group">
                    <button  type="button" @click="addProductManually" class="btn btn-orange">
                        {{__('billing/order.btn_add_product')}}
                    </button>
                </div>
                                <div class="overlay" v-if="buyer === null || buyer.status !== 4" ></div>
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
                            :disabled="buyer == null"
                            class="form-control modified"
                            :class="{'is-invalid': period === null}"
                            @change="calculateOrder()"
                        >
                            <option selected disabled :value="null">{{__('billing/order.txt_select_period')}}</option>
                            <option
                                v-if="isMfoPartner"
                                v-for="(item, index) in mfoPeriods"
                                :value="item"
                            >
                                @{{  item.title_ru }}
                            </option>
                            <option
                                v-if="!isMfoPartner"
                                v-for="(item, index) in config_plans"
                                :value="index"
                            >
                                @{{  index }} {{__('app.months_1')}}
                            </option>

                        </select>
                        <span class="validation-error" v-if="!(buyer === null || buyer.status !== 4) && period === null">{{ __('billing/order.err_select_period') }}</span>
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
                                stroke="#FF7643" stroke-miterlimit="10" stroke-linecap="round"
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

            <hr>

            <div class="form-row order-total">

                <div class="form-group col-6 col-sm-6 col-md-4">
                    <label>{{__('billing/order.txt_plan_graf')}}</label>
                    <input class="form-control modified" type="text" disabled value="1 {{__('app.date')}}">
                </div>
                <div class="error" v-if="'plan_graf' in errors">
                    @{{ errors.plan_graf }}
                </div>

                @if(count($sallers) > 0)
                    <div class="form-group col-6 col-sm-6 col-md-4">

                        <label>{{__('billing/order.lbl_bonus')}}</label>

                        <select
                            class="form-control modified"
                            name="seller_id"
                            v-model="seller_id"
                            @input="calculateOrderDebounce"
                        >

                            @foreach($sallers as $saller)
                                <option @if($saller->id == old('seller_id')) selected
                                        @endif value="{{$saller->id}}">
                                    {{$saller->name . ' ' . $saller->secondname . ' ' . $saller->patronymic . ' (' . $saller->phone .')' }}
                                </option>
                            @endforeach

                        </select>
                    </div>
                @endif

            </div>

            <hr>

            <div class="order-total" v-if="(this.bonusAmount > 0) && (this.seller_id > 0)">
                <h4 class="lead"> {{ __('billing/buyer.seller_bonuses') }}</h4>
                <div class="bonus_card">
                    <img src="{{asset('images/bonus_card.svg')}}">
                    <div>
                        {{ __('billing/buyer.bonus_amount') }}
                        <br>
                            <p>@{{bonusAmount}} сум</p>
                    </div>
                </div>
            </div>

            <hr v-if="this.bonusAmount > 0">

            <div v-if="message">
                <div v-for="item in message" :class="'alert alert-' + item.type">@{{ item.text }}</div>
            </div>
            <div class="form-group">
                <div class="form-submit">
                    <button
                        :disabled="!isSubmitAllowed"
                        id="submitOrder"
                        type="button"
                        :class="['btn', 'btn-orange', totalCredit !== 0 ? 'modern-shadow' : ''  ]">
                        {{__('billing/order.btn_create_order')}}
                    </button>
                </div>
            </div>

            {{--</div>--}}
        </form>

        <!-- Order confirm Modal ! -->
        <div class="modal fade" id="modalCreateOrder" tabindex="-1" aria-labelledby="exampleModalLabel"
             aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title font-weight-bold"
                            id="modalLawsuitLabel">{{ __('billing/order.lbl_buyer') }}</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body text-center">

                        <img v-if="buyer && buyer.personals.latest_id_card_or_passport_photo !== null"
                             :src="`{{ \App\Helpers\FileHelper::sourcePath() }}${buyer.personals.latest_id_card_or_passport_photo.path}`"
                             width="100%">
                        <h5 class="mt-3 mb-0">{{ __('billing/order.title_confirm') }}</h5>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" style="padding: 0.6rem 1rem; border-radius: 8px"
                                data-dismiss="modal">{{__('app.btn_cancel')}}</button>
                        <button :disabled='loading' @click="createOrder" type="button" data-dismiss="modal"
                                class="btn btn-orange">{{__('app.btn_save')}}</button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div v-if="loading" class="loading active"><img src="{{asset('images/media/loader.svg')}}"></div>

    @include('billing.order.parts.create')
@endsection
