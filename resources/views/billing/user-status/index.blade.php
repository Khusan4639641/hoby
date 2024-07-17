@extends('templates.billing.app')
@section('title', __('billing/menu.user_status'))
@section('class', 'orders edit')

@section('center-header-control')
    <a href="{{localeRoute('billing.orders.index')}}" class="btn btn-orange">{{__('app.btn_back')}}</a>
@endsection

@section('content')

    <div class="buyer show">
        <div>
            <div class="lead">{{__('billing/billing.lbl_check_status')}}</div>
            <div class="form-row">
                <div class="form-group col-12 col-sm-6 col-md-4">

                    <label>{{__('billing/order.lbl_partner_phone')}}</label>
                    <input v-mask="'+998 (##) ###-##-##'" required type="text"
                           placeholder="{{__("billing/order.txt_search_by_phone")}}"
                           :class="'form-control modified ' + (processing_user?'processing':'')"
                           v-model="strSearchPhone"
                    />

                    <div v-if="buyers.length > 0" class="dropdown-menu show user-info-dropdown">
                        <a v-for="(item, index) in buyers" :key="item.id" class="dropdown-item"
                           v-on:click="setBuyer(index)">
                            {{--                                    <div class="preview" v-if="item.personals && item.personals.passport_selfie"--}}
                            {{--                                         :style="'background-image: url(/storage/' + item.personals.passport_selfie.path + ');'"></div>--}}
                            {{--                                    <div v-else class="preview dummy"></div>--}}

                            @{{item.surname}} @{{item.name}} @{{item.patronymic}}
                            (@{{item.phone}})
                        </a>
                    </div>
                </div>

                <div class="form-group col-12 col-sm-5 col-md-3">
                    <label>&nbsp;</label>
                    <button
                        :disabled="buyer !== null"
                        class="btn btn-orange"
                        @click="checkUser"
                    >
                        {{__('billing/buyer.btn_check_status')}}
                    </button>
                </div>
            </div><!-- /.form-row -->
            <hr>
        </div>

        <div class="user-card in-status-page">
            {{-- Title --}}
            <div v-if="buyer !== null" class="lead">{{ __('app.status') }}</div>
            <div v-else class="lead">{{ __('app.last_buyers') }}</div>

            <div v-if="buyer !== null" class="row align-items-center mb-3 mb-md-0">
                <div v-if="buyer.status === 4" class="col-xl-1 col-lg-2 col-md-2 col-6 mr-lg-5">
                    <div v-if="buyer.personals.latest_id_card_or_passport_photo"
                         class="preview ml-3 mr-0"
                         :style="`background-image: url({{ \App\Helpers\FileHelper::sourcePath() }}${buyer.personals.latest_id_card_or_passport_photo.path});`"
                    ></div>
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
                                @{{ buyer.settings.balance }} {{__('app.currency')}}
                            </div>
                        </div>

                        <div v-if="buyer.status === 4" class="total mr-0 mr-md-3">
                            <label class="font-weight-bold">
                                {{__('billing/order.lbl_buyer_personal_account')}}
                            </label>
                            <div class="value text-orange">
                                @{{ buyer.settings.personal_account }} {{__('app.currency')}}
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
                    <div v-if="buyer.status === 4" class="user-status-container verified">{{ strtoupper(__('user.status_4')) }}</div>
                </div>
                <button @click="unsetBuyer" class="btn btn-transparent">
                    <img src="{{asset('images/icons/icon_close.svg')}}">
                </button>
            </div>

            {{-- 5 buyers --}}
            <div v-else>
                <div v-if="lastBuyers.length > 0">
                    <div v-for="(buyer, index) in lastBuyers"
                         :key="index"
                         class="row py-3"
                         style="box-shadow: inset 0 -2px 0 rgba(0, 0, 0, 0.08);"
                    >
                        <div class="col-lg-1 col-md-2 col-6">
                            <div class="preview ml-3 mr-0" v-if="buyer.personals?.passport_selfie"
                                 :style="`background-image: url('{{ \App\Helpers\FileHelper::sourcePath() }}' + ${buyer.personals?.passport_selfie.path})`"></div>
                            <div v-else class="preview dummy"></div>
                        </div>

                        <div class="col-lg-3 col-md-3 col-6 info">
                            <div class="pt-3">
                                <div class="name mb-1 font-weight-bold font-size-24">
                                    @{{ buyer.surname }} @{{ buyer.name }} @{{ buyer.patronymic }}
                                </div>
                                <div class="font-weight-normal mb-2">
                                    {{ __('account.phone_short') }}@{{buyer.phone}}
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-4 col-6">
                            <div class="row mt-3 pl-3 pl-md-0">
                                <div class="d-flex">
                                    <div class="icon-container">
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
                                <div class="total mr-0 mr-md-3">
                                    <label class="font-weight-bold">
                                        {{__('billing/order.lbl_buyer_balance')}}
                                    </label>
                                    <div class="value text-orange">
                                        @{{ buyer.settings?.balance || 0 }} {{__('app.currency')}}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-5 col-md-3 col-6 d-flex align-items-center justify-content-end">
                            <div class="total orders-list">
                                <div class="value order-status-container w-auto"
                                     :class="{
                                    'banned': buyer.status === 9,
                                    'completed': buyer.status === 4,
                                    'active': buyer.status === 2 || buyer.status === 10 || buyer.status === 1 || buyer.status === 5,
                                }"
                                >
                                    @{{ buyer.status_caption }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center user-status-div">
                    <span class="user-status">
                        {{ __('auth.error_user_not_found') }}
                    </span>
                </div>
            </div>
        </div>

        <input type="hidden" value="@{{buyer.id}}" name="user_id">
        <div class="error" v-if="'user_id' in errors">
            @{{ errors.user_id }}
        </div>

    </div>

    <div v-if="loading" class="loading active"><img src="{{asset('images/media/loader.svg')}}"></div>
    <script>
        const APP_LOCALE= '{{app()->getLocale()}}'
        axios.defaults.headers.common = {
            "Content-Language": APP_LOCALE,
        };
    </script>
    @include('billing.user-status.parts.index')
@endsection
