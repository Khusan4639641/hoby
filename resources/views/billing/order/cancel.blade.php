@extends('templates.billing.app')
{{--@section('header-sec')--}}
{{--    @parent--}}
{{--    @include('templates.common.parts.edited.header', ['test' => '123 test text'])--}}
{{--@endsection--}}

@section('class', 'buyer create')

@section('center-header-prefix')
{{--    <a class="link-back" href="{{localeRoute('billing.orders.index')}}">--}}
<a class="link-back"
   href="{{ request()->query('from') == 'manager' ? localeRoute('billing.contracts_for_cancellation') : localeRoute('billing.orders.index')}}">
        <img src="{{asset('images/icons/icon_arrow_orange.svg')}}">
    </a>
    <div class="d-flex align-items-center">
        <h1 class="font-size-32 mb-0 mr-3">{{ __('billing/order.header_order').' № ' . $order->contract->id }}</h1>
    </div>
@endsection

{{--@section('center-header-control')--}}
{{--    <a href="{{localeRoute('billing.orders.create')}}" class="btn btn-orange">--}}
{{--        {{__('billing/order.btn_create_order')}}--}}
{{--    </a>--}}
{{--@endsection--}}

@section('content')

    <div v-for="item in message" :class="'alert alert-'+ item.type">
        @{{item.text}}
    </div>

    @if($type === 'upload-act')
        <div id="newBuyer" class="row">
            <div class="col-12 mb-3">
                <div class="lead">{{__('billing/order.act_needed')}}</div>
            </div>
            <div class="col-12 col-md-2 form-group">
                <input @change="updateFiles" accept=".png, .jpg, .jpeg, .gif, pdf"
                       name="act" type="file" class="d-none"
                       id="act-uploader">

                <div v-if="act.preview != null" class="preview">
                    <div class="img" :style="'background-image: url(' + act.preview+');'"></div>
                </div>

                <div v-else class="no-preview img-passport-2">
                    <div class="img m-0"></div>
                </div>
            </div>

            <div class="col-md-10" style="margin: auto 0;">
                <div class="lead">{{ __('billing/contract.photo_quality') }}</div>
                <p>{{ __('billing/contract.photo_formats') }} JPG, PNG, PDF</p>
                <label class="btn-orange btn px-5 py-2" for="act-uploader">{{ __('app.btn_upload') }}</label>
            </div>
        </div>

        <div class="col-12 mt-5">
            <div class="btn btn-orange px-5 py-2" @click="uploadAct">{{ __('app.btn_save') }}</div>
        </div>

        <div class="mt-3 alert text-red" v-for="item in act.message">@{{ item.text }}</div>
    @endif

    @if($type === 'upload-imei')
        <div id="newBuyer" class="row">
            <div class="col-12 mb-3">
                <div class="lead">{{__('order.title_upload_imei')}}</div>
            </div>

            <div class="col-12 col-md-2 form-group">
                <input @change="updateImeiFiles" accept=".png, .jpg, .jpeg, .gif, pdf"
                       name="act" type="file" class="d-none"
                       id="imei-uploader">

                <div v-if="imei.preview != null" class="preview">
                    <div class="img" :style="'background-image: url(' + imei.preview+');'"></div>
                </div>

                <div v-else class="no-preview img-passport-2">
                    <div class="img m-0"></div>
                </div>
            </div>

            <div class="col-md-10" style="margin: auto 0;">
                <div class="lead">{{ __('billing/contract.photo_quality') }}</div>
                <p>{{ __('billing/contract.photo_formats') }} JPG, PNG, PDF</p>
                <label class="btn-orange btn px-5 py-2" for="imei-uploader">{{ __('app.btn_upload') }}</label>
            </div>
        </div>

        <div class="col-12 mt-5">
            <div class="btn btn-orange px-5 py-2" @click="uploadImei">{{ __('app.btn_save') }}</div>
        </div>

        <div class="mt-3 alert text-red" v-for="item in imei.message">@{{ item.text }}</div>
    @endif

    @if($type === 'cancel')
        <div class="order-success">
            <div class="d-flex flex-column justify-content-center mx-auto">
                <img src="{{ asset('images/cancel-act-img.svg') }}" class="mx-auto" width="200" alt="badge">

                <div class="font-weight-900 font-size-40 text w-75 text-center mx-auto">
                    {{ __('billing/contract.agree_cancel_contract') }}
                </div>

                <div class="mx-auto" style="width: 320px">
                    <div v-if="hasSmsSent">
                        <label class="mt-3" for="cancelSmsCode">{{ __('auth.label_input_sms_code') }}</label>
                        <input
                            v-model="smsCode"
                            v-mask="'####'"
                            id="cancelSmsCode"
                            type="text"
                            class="form-control modified w-100"
                            placeholder="{{ __('billing/order.sms_code') }}"
                        >
                    </div>

                    <button v-if="!hasSmsSent"
                            class="btn btn-orange text-white modern-shadow mt-3 mx-auto w-100 px-5 py-3"
                            @click="sendCancelSms"
                    >
                        {{ __('app.yes') }}, {{ __('billing/order.btn_cancel_order') }}
                    </button>

                    <button v-else class="btn btn-orange text-white modern-shadow mt-3 mx-auto w-100 px-5 py-3"
                            @click="checkCancelSms"
                    >
                        {{ __('billing/order.btn_cancel_order') }}
                    </button>
                    <p class="text-red text-center mt-2" v-if="this.hasError">{{ __('auth.error_code_wrong') }}</p>

                </div>
            </div>
        </div>
    @endif


    @if($type === 'client-photo')
        <div id="newBuyer" class="row">
            <div class="col-12 mb-3">
                <div class="lead">{{__('billing/order.title_upload_client_photo')}}</div>
            </div>

            <div class="col-12 col-md-2 form-group">
                <input @change="updateClientFiles" accept=".png, .jpg, .jpeg, .gif, pdf"
                       name="act" type="file" class="d-none"
                       id="client-photo-uploader">

                <div v-if="client_photo.preview != null" class="preview">
                    <div class="img" :style="'background-image: url(' + client_photo.preview+');'"></div>
                </div>

                <div v-else class="no-preview img-passport-2">
                    <div class="img m-0"></div>
                </div>
            </div>

            <div class="col-md-10" style="margin: auto 0;">
                <div class="lead">{{ __('billing/contract.photo_quality') }}</div>
                <p>{{ __('billing/contract.photo_formats') }} JPG, PNG, PDF</p>
                <label class="btn-orange btn px-5 py-2" for="client-photo-uploader">{{ __('app.btn_upload') }}</label>
            </div>

        </div>

        <div class="col-12 mt-5">
            <div class="btn btn-orange px-5 py-2" @click="uploadClientPhoto">{{ __('app.btn_save') }}</div>
        </div>
        <div class="mt-3 alert text-red" v-for="item in client_photo.message">@{{ item.text }}</div>
    @endif

    <div v-if="loading" class="loading active"><img src="{{asset('images/media/loader.svg')}}"></div>

    <script>
    var app = new Vue({
        el: '#app',
        data: {
            hasError: false,
            message: [],
            loading: false,
            hasSmsSent: false,
            smsCode: null,
            act: {
                status: '{{$order->contract->act_status}}',
                new: null,
                preview: null,
                message: [],
            },
            imei: {
                status: '{{$order->contract->imei_status}}',
                new: null,
                preview: null,
                message: [],
            },

            client_photo: {
                status: '{{$order->contract->client_photo}}',
                new: null,
                preview: null,
                message: [],
            },
        },
        methods: {
            uploadAct() {
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
                                window.location.href = `{{localeRoute('billing.orders.index')}}`;
                            } else {
                                this.act.status = 0;
                                this.act.new = null;
                                this.act.message = response.data.response.message;
                            }

                            this.loading = false;
                            app.$forceUpdate();
                        })
                        .catch(error => {
                            this.act.message.push({
                                'type': 'danger',
                                'text': error.message,
                            });
                        })
                } else {
                    this.act.message.push({
                        'type': 'danger',
                        'text': '{{__('app.btn_choose_file')}}',
                    });
                }

                this.loading = false;
            },
            updateFiles(e) {
                let files = e.target.files;

                if (files.length > 0) {
                    this.act.new = files[0];
                    this.act.preview = URL.createObjectURL(files[0]);
                }

                if (this.act.old) {
                    this.files_to_delete.push(this.act.old);
                }
            },

            uploadImei() {
                this.loading = true;
                this.imei.message = [];

                if (this.imei.new !== null) {
                    formData = new FormData();
                    formData.append('api_token', '{{Auth::user()->api_token}}');
                    formData.append('id', '{{ $order->contract->id }}');
                    formData.append('imei', this.imei.new);

                    axios.post('/api/v1/contracts/upload-imei', formData, { headers: { 'Content-Language': '{{app()->getLocale()}}' } })
                        .then(response => {
                            if (response.data.status === 'success') {
                                this.loading = false;
                                window.location.href = `{{localeRoute('billing.orders.index')}}`;
                            } else {
                                this.imei.new = null;
                                this.imei.message = response.data.response.message;
                            }

                            this.loading = false;
                            app.$forceUpdate();
                        })
                        .catch(error => {
                            this.imei.message.push({
                                'type': 'danger',
                                'text': error.message,
                            });
                        })
                } else {
                    this.imei.message.push({
                        'type': 'danger',
                        'text': '{{__('app.btn_choose_file')}}',
                    });
                }

                this.loading = false;


            },
            updateImeiFiles(event) {
                let files = event.target.files;

                if (files.length > 0) {
                    this.imei.new = files[0];
                    this.imei.preview = URL.createObjectURL(files[0]);
                }

                if (this.imei.old) {
                    this.files_to_delete.push(this.imei.old);
                }
            },



            uploadClientPhoto() {
                this.loading = true;
                this.client_photo.message = [];

                if (this.client_photo.new !== null) {
                    formData = new FormData();
                    formData.append('api_token', '{{Auth::user()->api_token}}');
                    formData.append('id', '{{ $order->contract->id }}');
                    formData.append('client_photo', this.client_photo.new);

                    axios.post('/api/v1/contracts/upload-client-photo', formData, { headers: { 'Content-Language': '{{app()->getLocale()}}' } })
                        .then(response => {
                            if (response.data.status === 'success') {
                                this.loading = false;
                                window.location.href = `{{localeRoute('billing.orders.index')}}`;
                            } else {
                                this.client_photo.new = null;
                                this.client_photo.message = response.data.response.message;
                            }

                            this.loading = false;
                            app.$forceUpdate();
                        })
                        .catch(error => {
                            this.client_photo.message.push({
                                'type': 'danger',
                                'text': error.message,
                            });
                        })
                } else {
                    this.client_photo.message.push({
                        'type': 'danger',
                        'text': '{{__('app.btn_choose_file')}}',
                    });
                }

                this.loading = false;


            },
            updateClientFiles(event) {
                let files = event.target.files;

                if (files.length > 0) {
                    this.client_photo.new = files[0];
                    this.client_photo.preview = URL.createObjectURL(files[0]);
                }

                if (this.client_photo.old) {
                    this.files_to_delete.push(this.client_photo.old);
                }
            },


            async sendCancelSms() {
                try {
                    this.loading = true;
                    const response = await axios.post('/api/v1/contracts/send-cancel-sms', {
                        contract_id: {{ $order->contract->id }},
                    }, {
                        headers: {
                            'Content-Language': '{{app()->getLocale()}}',
                            Authorization: 'Bearer {{ Auth::user()->api_token }}',
                        },
                    });

                    if (response.data.result.status === 1) {
                        this.hasSmsSent = true;
                        this.hasError = false;
                    }

                    this.loading = false;

                } catch (e) {
                    this.loading = false;
                    this.message.push({
                        type: 'danger',
                        text: e.message,
                    });
                }
            },

            async checkCancelSms() {
                try {
                    this.loading = true;
                    const response = await axios.post('/api/v1/contracts/check-cancel-sms', {
                        contract_id: '{{ $order->contract->id }}',
                        code: this.smsCode,
                    }, {
                        headers: {
                            'Content-Language': '{{app()->getLocale()}}',
                            Authorization: 'Bearer {{ Auth::user()->api_token }}',
                        },
                    });

                    if (response.data.result.status === 1) {
                        this.hasSmsSent = true;
                        this.hasError = false;
                        window.location.href = `{{localeRoute('billing.orders.index')}}`;
                    }

                    if(response.data.result.status === 0){
                        this.hasError = true;
                    }


                    this.loading = false;

                } catch (e) {
                    this.loading = false;
                    this.message.push({
                        type: 'danger',
                        text: e.message,
                    });
                }
            },

        },
    });
    </script>

@endsection
