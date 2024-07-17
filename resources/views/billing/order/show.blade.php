@extends('templates.billing.app')
{{--@section('header-sec')--}}
{{--    @parent--}}
{{--    @include('templates.common.parts.edited.header', ['test' => '123 test text'])--}}
{{--@endsection--}}

@section('class', 'orders show')

@section('center-header-prefix')
    {{--    <a class="link-back" href="{{localeRoute('billing.orders.index')}}">--}}
    {{--        <img src="{{asset('images/icons/icon_arrow_orange.svg')}}">--}}
    {{--    </a>--}}
    <div class="d-flex align-items-center">
        <h1 class="font-size-32 mb-0 mr-3">{{ __('billing/order.header_order').' № ' . $order->contract->id }}</h1>
        <div>{{$order->created_at}}</div>
    </div>
@endsection

@section('content')
    <div v-for="item in message" :class="'alert alert-'+ item.type">
        @{{item.text}}
    </div>

    <div class="buyer">
        <div class="user-card">
            <div class="font-weight-bold font-size-24 mb-3">{{ __('billing/order.lbl_buyer') }}</div>
            <div class="row">
                <div class="col-lg-1 col-md-2 col-6">
                    @if($order->buyer->personals->passport_selfie)
                        <div class="preview mr-0"
                             style="'background-image: url(/storage/{{ $order->buyer->personals->passport_selfie->path }}'"></div>
                    @else
                        <div v-else class="preview dummy"></div>
                    @endif
                </div>
                <div class="col-lg-4 col-md-3 col-6 info">
                    <div class="mt-3">
                        <div class="name mb-1 font-weight-bold font-size-24">
                            {{ $order->buyer->surname }} {{ $order->buyer->name }} {{ $order->buyer->patronymic }}
                        </div>
                        {{--                        <div class="mb-1 font-weight-normal">ID {{ $orderbuyer.id }}</div>--}}
                        <div class="font-weight-normal mb-2">{{ __('account.phone_short') }}{{$order->buyer->phone}}
                        </div>
                    </div>
                </div>
                <div class="col-lg-5 col-md-4 col-6">
                    <div class="row mt-3">
                        <div class="d-flex">
                            <div class="icon-container ml-3 ml-md-0">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                     xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M21.317 8.68237V18.0144H5.31698M2.68298 5.98438H18.683V15.3164H2.68298V5.98438ZM12.082 10.6504C12.082 11.7908 11.4556 12.7154 10.683 12.7154C9.91034 12.7154 9.28398 11.7908 9.28398 10.6504C9.28398 9.50991 9.91034 8.58538 10.683 8.58538C11.4556 8.58538 12.082 9.50991 12.082 10.6504Z"
                                        stroke="#FF7643" stroke-miterlimit="10" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>
                        <div class="total">
                            <label class="font-weight-bold">
                                {{__('billing/order.lbl_total')}} ({{__('app.currency')}})
                            </label>
                            <div class="font-weight-bold text-orange">
                                {{ number_format($order->total, 2, '.', ' ') }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-3 col-6">
                    <div class="user-status-container {{ $order->status === 5 || $order->status === 0 ? 'banned' : 'verified' }}">
                        {{ $order->status_caption }}
                    </div>
                </div>
            </div>
        </div>
        {{--        @if($order->shipping_code)--}}
        {{--            <div class="row part">--}}
        {{--                <div class="col-12 col-md-3 caption">{{__('billing/order.lbl_shipping_name')}}</div>--}}
        {{--                <div class="col-12 col-md-9 value">--}}
        {{--                    {{$order->shipping_name}}--}}
        {{--                </div>--}}
        {{--            </div>--}}
        {{--            <div class="row part">--}}
        {{--                <div class="col-12 col-md-3 caption">{{__('billing/order.lbl_shipping_address')}}</div>--}}
        {{--                <div class="col-12 col-md-9 value">--}}
        {{--                    {{$order->shipping_address}}--}}
        {{--                </div>--}}
        {{--            </div>--}}
        {{--            <div class="row part">--}}
        {{--                <div class="col-12 col-md-3 caption">{{__('billing/order.lbl_shipping_price')}}</div>--}}
        {{--                <div class="col-12 col-md-9 value">--}}
        {{--                    {{$order->shipping_price}}--}}
        {{--                </div>--}}
        {{--            </div>--}}
        {{--        @endif--}}
    </div>

    {{--    <div class="row params">--}}
    {{--        <div class="col-12 col-sm part">--}}
    {{--            <div class="caption">{{__('billing/order.lbl_debit')}}</div>--}}
    {{--            <div class="value">{{$order->credit }}</div>--}}
    {{--        </div>--}}
    {{--        <div class="col-12 col-sm part">--}}
    {{--            <div class="caption">{{__('billing/order.lbl_credit')}}</div>--}}
    {{--            <div class="value">{{$order->debit }}</div>--}}
    {{--        </div>--}}
    {{--        <div class="col-12 col-sm part total">--}}
    {{--            <div class="caption">{{__('billing/order.lbl_total')}} ({{__('app.currency')}})</div>--}}
    {{--            <div class="value">{{$order->total }}</div>--}}
    {{--        </div>--}}
    {{--    </div>--}}

    <div class="font-weight-bold font-size-24 mb-3">
        {{__('billing/order.lbl_products')}} - {{count($order->products)}}
    </div>
    <div class="products">
        <table>
            <thead>
            <th>{{__('billing/order.lbl_product')}}</th>
            <th><span class="d-none d-sm-inline">{{__('billing/order.lbl_product_price')}}</span></th>
            <th><span class="d-none d-sm-inline">{{__('billing/order.lbl_product_amount')}}</span></th>
            <th><span class="d-none d-sm-inline">{{__('billing/order.lbl_total')}}</span></th>
            </thead>
            <tbody>
            @foreach($order->products as $product)
                <tr class="product">
                    {{--                    <td>--}}
                    {{--                        @if($product->preview)--}}
                    {{--                            <div class="img preview" style="background-image: url({{$product->preview}});"></div>--}}
                    {{--                        @else--}}
                    {{--                            <div class="img no-preview"></div>--}}
                    {{--                        @endif--}}
                    {{--                    </td>--}}
                    <td>
                        {{--                        <div class="vendor-code">--}}
                        {{--                            {{__('billing/order.lbl_product_code')}}: {{$product->vendor_code}}--}}
                        {{--                        </div>--}}
                        <div class="name">{{$product->name }}</div>
                    </td>
                    <td>{{ number_format($product->price, 2, '.', ' ') }}</td>
                    <td>x{{$product->amount }}</td>
                    <td>
                        <div>{{ number_format($product->price * $product->amount, 2, '.', ' ') }}</div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    @if($order->contract && $order->status === 9)
        <div class="download-act pb-3">
            <div class="font-weight-bold font-size-24 my-3">{{__('billing/order.title_upload_act')}}</div>
            <div v-for="item in act.message" :class="'alert alert-'+ item.type">
                @{{item.text}}
            </div>


            <form v-if="act.status != 1 && act.status != 3" action="" @submit="uploadAct">
                <div class="alert alert-info" v-if="act.status == 2">
                    {{__('billing/contract.act_status_2')}}
                </div>

                <div class="form-row">
                    <div class="form-group col-12 col-md-6">
                        <component is="style">
                            .custom-file-label:after {
                            content: "{{__('app.btn_choose_file')}}";
                            }
                        </component>

                        <div class="custom-file">
                            <input class="custom-file-input" @change="updateFiles" accept=".png, .jpg, .jpeg, .gif"
                                   name="act" type="file" id="act">
                            <label class="custom-file-label modified" for="act">
                                <span v-if="act.new && act.new.name">@{{ act.new.name }}</span>
                                <span v-else>{{__('app.btn_choose_file')}}</span>
                            </label>
                        </div>
                    </div>
                    <div class="form-group col-12 col-md-6">
                        <button class="btn btn-orange download-act-button">{{__('app.btn_upload')}}</button>
                    </div>

                </div>
                <!-- /.form-row -->
            </form>
            <div v-else>

                <div class="alert alert-info" v-if="act.status == 1">
                    {{__('billing/contract.act_status_1')}}
                </div>
                <div class="alert alert-success" v-if="act.status == 3">
                    {{__('billing/contract.act_status_3')}}
                </div>

                <p v-if="act.path"><a target="_blank" class="btn btn-outline-primary"
                                      :href="act.path">{{__('billing/contract.act_view')}}</a></p>
            </div>
        </div>


        <div class="my-3 download-files-container">
            {{--            <a target="_blank" href="{{$offer_pdf}}" class="btn btn-orange mr-md-4">{{__('offer.btn_download_offer')}}</a>--}}
            <a target="_blank" href="{{$account_pdf}}" class="btn btn-orange mr-md-4">{{__('offer.btn_download_act')}}</a>
            {{--            --}}{{--<a target="_blank" href="{{localeRoute('billing.orders.account', $order)}}"--}}
            {{--               class="btn btn-orange">{{__('offer.btn_download_act_old')}}</a>--}}
        </div>
    @endif


    @if($order->contract)
        <div class="download-act pb-3">
            <div class="font-weight-bold font-size-24 my-3">{{__('billing/order.title_upload_cancel_act')}}</div>
            <div v-for="item in cancel_act.message" :class="'alert alert-'+ item.type">
                @{{item.text}}
            </div>


            <form v-if="cancel_act.status != 1 && cancel_act.status != 3" action="" @submit="uploadCancelAct">
                <div class="alert alert-info" v-if="cancel_act.status == 2">
                    {{__('billing/contract.cancel_act_status_2')}}
                </div>

                <div class="form-row">
                    <div class="form-group col-12 col-md-6">
                        <component is="style">
                            .custom-file-label:after {
                            content: "{{__('app.btn_choose_file')}}";
                            }
                        </component>

                        <div class="custom-file">
                            <input class="custom-file-input" @change="updateCancelFiles" accept=".png, .jpg, .jpeg, .gif"
                                   name="cancel_act" type="file" id="cancel_act">
                            <label class="custom-file-label modified" for="act">
                                <span v-if="cancel_act.new1 && cancel_act.new1.name">@{{ cancel_act.new1.name }}</span>
                                <span v-else>{{__('app.btn_choose_file')}}</span>
                            </label>
                        </div>
                    </div>
                    <div class="form-group col-12 col-md-6">
                        <button class="btn btn-orange download-act-button">{{__('app.btn_upload')}}</button>
                    </div>

                </div>
                <!-- /.form-row -->
            </form>
            <div v-else>

                <div class="alert alert-info" v-if="cancel_act.status == 1">
                    {{__('billing/contract.act_status_1')}}
                </div>
                <div class="alert alert-success" v-if="cancel_act.status == 3">
                    {{__('billing/contract.act_status_3')}}
                </div>

                <p {{--v-if="cancel_act.path2"--}}><a target="_blank" class="btn btn-outline-primary"
                                      :href="cancel_act.path2">{{__('billing/contract.act_view')}}</a></p>
            </div>
        </div>


    @endif

    <div v-if="loading" class="loading active"><img src="{{asset('images/media/loader.svg')}}"></div>


    <script>
        var app = new Vue({
            el: '#app',
            data: {
                message: [],
                status: '{{$order->status}}',
                status_caption: '{{$order->status_caption}}',
                loading: false,
                @if($order->contract)
                act: {
                    status: '{{$order->contract->act_status}}',
                    new: null,
                    message: [],
                    path: '{{$order->contract->act?'/storage/'.$order->contract->act->path:null}}',
                },
                cancel_act: {
                    status: '{{$order->contract->cancel_act_status}}',
                    new1: null,
                    message: [],
                    path2: '{{$order->contract->cancelAct?'/storage/'.$order->contract->cancelAct->path:null}}',
                }
                @endif
            },
            methods: {
                changeStatus(status = null, caption = null) {
                    if (status != null) {
                        this.loading = true;
                        axios.post('/api/v1/orders/status', { // ??? куда зачем
                                api_token: Cookies.get('api_token'),
                                status: status,
                                id: {{$order->id}}
                            },
                            { headers: { 'Content-Language': '{{app()->getLocale()}}' } },
                        ).then(response => {
                            if (response.data.status === 'success') {
                                this.status = status;
                                this.status_caption = caption;
                                this.message = response.data.response.message;
                            }
                            this.loading = false;
                        });
                    }
                },


                @if($order->contract)
                uploadAct(e) {
                    e.preventDefault();

                    this.loading = true;
                    this.act.message = [];

                    if (this.act.new != null) {
                        formData = new FormData();
                        formData.append('api_token', '{{Auth::user()->api_token}}');
                        formData.append('id', '{{$order->contract->id}}');
                        formData.append('act', this.act.new);

                        axios.post('/api/v1/contracts/upload-act', formData, { headers: { 'Content-Language': '{{app()->getLocale()}}' } })
                            .then(response => {
                                if (response.data.status === 'success') {
                                    this.act.status = 1;
                                } else {
                                    this.act.status = 0;
                                    this.act.new = null;
                                    this.act.message = response.data.response.message;
                                }

                                this.loading = false;
                                app.$forceUpdate();
                            });
                    } else {
                        this.act.message.push({
                            'type': 'danger',
                            'text': '{{__('app.btn_choose_file')}}',
                        });
                    }

                    this.loading = false;
                },

                uploadCancelAct(e) {
                    e.preventDefault();

                    this.loading = true;
                    this.cancel_act.message = [];

                    if (this.cancel_act.new1 != null) {
                        formData = new FormData();
                        formData.append('api_token', '{{Auth::user()->api_token}}');
                        formData.append('id', '{{$order->contract->id}}');
                        formData.append('cancel_act', this.cancel_act.new1);
                        console.log(formData);

                        axios.post('/api/v1/contracts/upload-cancel-act', formData, { headers: { 'Content-Language': '{{app()->getLocale()}}' } })
                            .then(response => {
                                if (response.data.status === 'success') {
                                    this.cancel_act.status = 1;
                                    app.$forceUpdate();
                                } else {
                                    this.cancel_act.status = 0;
                                    this.cancel_act.new1 = null;
                                    this.cancel_act.message = response.data.response.message;
                                }

                                this.loading = false;
                                console.log(this.cancel_act.status);
                                app.$forceUpdate();
                            });
                    } else {
                        this.cancel_act.message.push({
                            'type': 'danger',
                            'text': '{{__('app.btn_choose_file')}}',
                        });
                    }

                    this.loading = false;


                },
                @endif

                updateFiles(e) {
                    let files = e.target.files;

                    if (files.length > 0)
                        this.act.new = files[0];


                    if (this.act.old) {
                        this.files_to_delete.push(this.act.old);
                    }
                },

                updateCancelFiles(e) {
                    let files = e.target.files;

                    if (files.length > 0)
                        this.cancel_act.new1 = files[0];


                    if (this.cancel_act.old) {
                        this.files_to_delete.push(this.cancel_act.old);
                    }
                },
            },
        });
    </script>

@endsection
