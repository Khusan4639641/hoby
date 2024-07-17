@extends('templates.frontend.app')

@section('title', __('frontend/order.processing'))
@section('class', 'order processing')

@section('content')
    <div class="container">
        <div class="" id="processing">
            <template v-if="showProcessing">
                <h1>{{__('frontend/order.processing')}}</h1>

                <div class="description">{{__('frontend/order.help_order')}}</div>

                <div v-if="loading" class="loader">
                    <img src="{{asset('images/media/loader.svg')}}">
                </div>
                <div v-else>
                    <div class="row">
                        <div class="col-12 col-lg-8">

                            <section class="shipping">
                                <div class="lead">{{__('frontend/order.header_shipping')}}</div>
                                <div class="row">
                                    <div class="col-12 col-md-6 form-group">
                                        <label>{{__('frontend/order.shipping_method')}}</label>
                                        <select v-model="shipping.value" ref="shipping" name="shipping" class="form-control" v-on:change="changeShipping()" required>
                                            <option value="">{{__('frontend/order.choose_shipping')}}</option>
                                            <option v-for="(item, index) in shipping.list" :value="item.code" :data-need-address="item.need_address">@{{ item.name}}</option>
                                        </select>
                                    </div>
                                </div><!-- /.row -->
                            </section><!-- /.shipping -->

                            <section class="shipping-address" v-if="shipping.needAddress">
                                <div class="lead">{{__('frontend/order.header_shipping_address')}}</div>

                                <div class="select-address" v-if="addressesShipping.list.length > 0">
                                    <div class="row">
                                        <div class="col-12 col-md-6 form-group">
                                            <label>{{__('frontend/order.addresses_shipping')}}</label>
                                            <select v-model="addressesShipping.value" ref="addressesShipping" name="addressesShipping" class="form-control" v-on:change="changeAddressShipping()" required>
                                                <option value="">{{__('frontend/order.choose_address_shipping')}}</option>
                                                <option :data-region="item.region" :data-area="item.area" :data-city="item.city" :data-address="item.address" v-for="(item, index) in addressesShipping.list" :value="item.id">@{{ item.string}}</option>
                                            </select>
                                        </div>
                                    </div><!-- /.row -->
                                    <hr>
                                </div>

                                <div class="new-address">
                                    <div class="form-row">
                                        <div class="col-12 col-md form-group">
                                            <label>{{__('frontend/order.address_region')}}</label>
                                            <select ref="selectRegion" v-model="user.address_region" name="address_region" type="text"
                                                    :class="'form-control' + (errors.address_region?' is-invalid':'')" v-on:change="changeRegion()">
                                                <option value="">{{__('panel/buyer.choose_region')}}</option>
                                                <option v-for="(region, index) in region.list" :value="region.regionid">@{{ region['name' + locale]}}</option>
                                            </select>
                                            <div class="error" v-for="item in errors.address_region">@{{ item }}</div>
                                        </div>
                                        <div class="col-12 col-md form-group">
                                            <label>{{__('frontend/order.address_area')}}</label>
                                            <select ref="selectArea" :disabled="area.disabled" v-model="user.address_area" name="address_area" type="text"
                                                    :class="'form-control' + (errors.address_area?' is-invalid':'')" v-on:change="changeArea()">
                                                <option value="">{{__('panel/buyer.choose_area')}}</option>
                                                <option v-for="(area, index) in area.list" :value="area.areaid">@{{ area['name' + locale]}}</option>
                                            </select>
                                            <div class="error" v-for="item in errors.address_area">@{{ item }}</div>
                                        </div>
                                        <div class="col-12 col-md form-group">
                                            <label>{{__('frontend/order.address_city')}}</label>
                                            <select ref="selectCity" :disabled="city.disabled" v-model="user.address_city" name="address_city" type="text"
                                                    :class="'form-control' + (errors.address_city?' is-invalid':'')" v-on:change="changeCity()">
                                                <option value="">{{__('panel/buyer.choose_city')}}</option>
                                                <option v-for="(city, index) in city.list" :value="city.cityid">@{{ city['name' + locale]}}</option>
                                            </select>
                                            <div class="error" v-for="item in errors.address_city">@{{ item }}</div>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="col-12 col-md-12 form-group">
                                            <label>{{__('frontend/order.address')}}</label>
                                            <input v-model="user.address" name="address" type="text" @focusout="saveSettings()"
                                                   :class="'form-control' + (errors.address?' is-invalid':'')">
                                            <div class="error" v-for="item in errors.address">@{{ item }}</div>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="col-12">
                                            <div class="checkbox with-text">
                                                <input v-model="saveCustomAddress" type="checkbox" id="saveCustomAddress" @change="saveSettings()">
                                                <label for="saveCustomAddress">{{__('frontend/order.save_address')}}</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section><!-- /.shipping-address -->

                            @include('frontend.order.parts.payment')

                        </div><!-- /.col-12 .col-md-8 -->

                        <div class="col-12 col-lg-4">
                            <section class="order-send">
                                <div class="lead">{{__('frontend/order.header_order_info')}}</div>

                                <div v-if="messages">
                                    <div v-for="item in messages" :class="'alert alert-' + item.type">@{{ item.text }}</div>
                                </div>

                                {{--<div v-if="messages.length">
                                    <div class="alert alert-success" v-for="message in messages">@{{ message }}</div>
                                </div>--}}

                                <div v-if="errors.length">
                                    <div class="alert alert-danger" v-for="message in errors">@{{ message }}</div>
                                </div>

                                <div class="totals">
                                    <div class="line">
                                        <div class="caption">{{__('frontend/order.price_shipping')}}</div>
                                        <div class="value">@{{ formatPrice(payment.shipping) }} {{__('app.currency')}}</div>
                                    </div>
                                    <div class="line total">
                                        <div class="caption">{{__('frontend/order.price_total')}}</div>
                                        <div class="value">@{{ formatPrice(payment.total) }} {{__('app.currency')}}</div>
                                    </div>
                                </div><!-- /.totals -->

                                <div class="confirm-text">{{__('frontend/order.txt_confirm_sms')}}</div>

                                <div class="sms row align-items-center">
                                    <div class="col-5 col-sm">
                                        <input :disabled="hashedSmsCode == null" placeholder="{{__('billing/order.txt_sms_code')}}" class="form-control" required v-model="smsCode" type="text">
                                    </div>
                                    <div class="col-7 col-sm pl-0">
                                        <div class="error text-danger" v-if="'code' in errors">
                                            @{{ errors.code }}
                                        </div>
                                        <button :disabled="!smsBtnEnabled" v-if="hashedSmsCode == null" @click="sendSMS" type="button" class="btn btn-success">{{__('billing/order.btn_get_sms_code')}}</button>
                                        <button :disabled="!resend.indicator" ref="resendSms" v-if="hashedSmsCode != null" @click="sendSMS" class="btn btn-primary btn-resend-sms" type="button">{{__('app.btn_resend_sms')}} <template v-if="!resend.indicator">({{__('auth.label_from')}} @{{ resend.interval }} {{__('auth.label_seconds')}})</template></button>
                                    </div>
                                </div>

                                <div class="confirm-order">
                                    <button type="button" class="btn btn-success" :disabled="smsCode == null || smsCode == ''" @click="checkSmsCode()">{{__('app.btn_send')}}</button>
                                </div>
                            </section><!-- /.order-send -->
                        </div>
                    </div> <!-- /.row -->
                </div><!-- /v-else -->
            </template>
            <template v-else>
                <h1>{{__('frontend/order.header_order_created')}}</h1>
                <div class="description">{{__('frontend/order.txt_order_created')}}</div>
                <a class="btn btn-primary" href="{{localeRoute('catalog.index')}}">{{__('frontend/order.btn_continue_shopping')}}</a>
            <a class="btn btn-outline-primary" href="{{localeRoute('cabinet.index')}}">{{__('frontend/order.btn_cabinet')}}</a>
            </template>
        </div><!-- /#processing -->
    </div><!-- /.container -->

    <script>
        var processing = new Vue({
            el: '#processing',
            data: {
                errors: {},
                messages: [],
                loading: true,
                locale: '{{ucfirst(app()->getLocale())}}',
                typeProcessing: '{{$type}}',
                saveCustomAddress: false,
                products: {!! $products ?? '[]'!!},
                smsCode: null,
                hashedSmsCode: null,
                showProcessing: true,
                orderNumber: '',
                smsBtnEnabled: false,
                resend: {
                    start: 60,
                    interval: null,
                    indicator: false
                },
                shipping: {
                    list: [],
                    value: "",
                    needAddress: false,
                },
                addressesShipping: {
                    list: JSON.parse('{!! $addressesShipping !!}'),
                    value: ""
                },
                region: {
                    list: [],
                    disabled: false
                },
                area: {
                    list: [],
                    disabled: true
                },
                city: {
                    list: [],
                    disabled: true
                },
                user: {
                    api_token: '{{Auth::user()->api_token?? null}}',
                    address_region: '',
                    address_area: '',
                    address_city: '',
                    address: '',
                },
                payment: {
                    calculate: {},
                    isPaymentAccount: false,
                    isPaymentCard: true,
                    typePayment: 'card',
                    total: 0,
                    shipping: 0,
                    totalCredit: 0,
                    paymentMonthly: 0,
                    period: 3,
                    calculating: false,
                    card_id: {{count($cards) >0?$cards[0]->id: 'null'}},
                    personal_account: {{$personal_account}},
                    config_plans: {
                        @foreach($plans as $plan => $percent)
                        {{$plan}}: {{$percent}},
                        @endforeach
                    },
                }
            },
            methods: {
                formatPrice: function(price = null){
                    let separator = ' ';
                    price = price.toString();
                    return price.replace(/(\d{1,3}(?=(\d{3})+(?:\.\d|\b)))/g,"\$1"+separator);
                },
                getShippingList: function (){
                    axios.post('/api/v1/order/shipping/list',
                        {
                            api_token: this.user.api_token,
                        },
                        { headers: { 'Content-Language': '{{app()->getLocale()}}' }}
                    ).then(result => {
                        this.shipping.list = (result.data.status === 'success') ? result.data.data : [];
                    });
                },
                changeShipping: function (){

                    this.shipping.needAddress = $('option:selected', this.$refs.shipping).data('need-address') === 1;
                    if(this.shipping.needAddress) {
                        this.getRegionList();
                    } else {
                        this.resetShippingAddress();
                    }

                    this.saveSettings();
                },
                changeAddressShipping: function (){

                    if(this.addressesShipping.value !== ''){
                        let refs = this.$refs.addressesShipping;
                        this.user.address_region = $('option:selected', refs).data('region') != null?$('option:selected', refs).data('region'): '';
                        this.user.address_area = $('option:selected', refs).data('area')!=null ?$('option:selected', refs).data('area'): '';
                        this.user.address_city = $('option:selected', refs).data('city') ?$('option:selected', refs).data('city'): '';
                        this.user.address = $('option:selected', refs).data('address') ?$('option:selected', refs).data('address'): '';

                        this.getAreaList();
                        this.getCityList();
                    } else {
                       this.resetShippingAddress();
                    }

                    this.saveSettings();

                },
                getRegionList: function () {
                    axios.post('/api/v1/regions/list', {
                            api_token: this.user.api_token,
                            orderBy: 'name{{ucfirst(app()->getLocale())}}'
                        },
                        {headers: {'Content-Language': '{{app()->getLocale()}}'}}
                    ).then(response => {
                        if (response.data.status === 'success') {
                            this.region.list = response.data.data;
                            if(this.area.list.length > 0)
                                this.area.disabled = false;
                            else
                                this.area.disabled = true;

                            if(this.city.list.length > 0)
                                this.city.disabled = false;
                            else
                                this.city.disabled = true;
                        }
                        //this.loading = false;
                    })
                },
                getAreaList: function () {
                    axios.post('/api/v1/areas/list', {
                            api_token: this.user.api_token,
                            regionid: this.user.address_region,
                            orderBy: 'name{{ucfirst(app()->getLocale())}}'
                        },
                        {headers: {'Content-Language': '{{app()->getLocale()}}'}}
                    ).then(response => {
                        if (response.data.status === 'success') {
                            this.area.list = response.data.data;
                            if(response.data.data.length === 0)
                                this.area.disabled = true;
                            else
                                this.area.disabled = false;
                        }
                        //this.loading = false;
                    })
                },
                getCityList: function () {
                    axios.post('/api/v1/cities/list', {
                            api_token: this.user.api_token,
                            regionid: this.user.address_region,
                            areaid: this.user.address_area,
                            orderBy: 'name{{ucfirst(app()->getLocale())}}'
                        },
                        {headers: {'Content-Language': '{{app()->getLocale()}}'}}
                    ).then(response => {
                        if (response.data.status === 'success') {
                            this.city.list = response.data.data;

                            if(response.data.data.length === 0)
                                this.city.disabled = true;
                            else
                                this.city.disabled = false;

                        }
                        //this.loading = false;
                    })
                },
                changeRegion: function () {

                    this.errors.address_region = null;

                    this.area.list = null;
                    this.user.address_area = '';

                    this.user.address_city = '';
                    this.city.list = null;
                    this.city.disabled = true;

                    if(this.user.address_region !== ''){
                        this.getAreaList();
                        this.area.disabled = false;
                    } else {
                        this.area.disabled = true;
                    }
                    this.saveSettings();
                },
                changeArea: function () {

                    this.errors.address_area = null;

                    this.user.address_city = '';
                    this.city.list = null;

                    if(this.user.address_area !== ''){
                        this.getCityList();
                        this.city.disabled = false;
                    } else {
                        this.city.disabled = true;
                    }
                    this.saveSettings();
                },

                changeCity: function () {
                    this.errors.address_city = null;
                    this.saveSettings();
                },

                loadSettings: function (){
                    this.getShippingList();
                    axios.post('{{localeRoute('cart.settings.load')}}',
                        {
                            api_token: this.user.api_token
                        },
                        {headers: {'Content-Language': '{{app()->getLocale()}}'}}
                    ).then(result => {
                        if(result.data.status === 'success'){
                            let loadSettings = result.data.data;
                            this.shipping.value = loadSettings.shippingCode != null ?loadSettings.shippingCode: '';
                            this.shipping.needAddress = loadSettings.needAddress !=null ?loadSettings.needAddress: '';
                            this.addressesShipping.value = loadSettings.addressesShipping!=null ?loadSettings.addressesShipping: '';

                            this.user.address_region = loadSettings.address.region.value!=null ?loadSettings.address.region.value: '';
                            this.user.address_area = loadSettings.address.area.value!=null ?loadSettings.address.area.value: '';
                            this.user.address_city = loadSettings.address.city.value!=null ?loadSettings.address.city.value: '';
                            this.user.address = loadSettings.address.address!=null ?loadSettings.address.address:'';

                            this.getRegionList();

                            this.area.disabled = loadSettings.address.area.disabled;
                            if(this.user.address_region !== '')
                                this.getAreaList();

                            this.city.disabled = loadSettings.address.city.disabled;
                            if(this.user.address_area !== '')
                                this.getCityList();

                            this.saveCustomAddress = loadSettings.saveCustomAddress;

                            if(loadSettings.payment != undefined) {
                                this.payment.typePayment = loadSettings.payment.type !=null?loadSettings.payment.type: 'card';
                                this.setPaymentMethod(this.payment.typePayment);
                                this.payment.card_id = loadSettings.payment.card_id!=null?loadSettings.payment.card_id: {{count($cards) >0?$cards[0]->id: 'null'}};
                                this.payment.period = loadSettings.payment.period!=null ?loadSettings.payment.period: 0;
                            }
                        }
                        this.calculate();
                        this.loading = false;
                    });
                },
                saveSettings: function (){

                    let settings = {
                        type: this.typeProcessing,
                        shippingCode: this.shipping.value,
                        needAddress: this.shipping.needAddress,
                        addressesShipping: this.addressesShipping.value,
                        saveCustomAddress: this.saveCustomAddress,
                        address: {
                            region: {
                                value: this.user.address_region,
                                disabled: this.region.disabled
                            },
                            area: {
                                value: this.user.address_area,
                                disabled: this.area.disabled
                            },
                            city: {
                                value: this.user.address_city,
                                disabled: this.city.disabled
                            },
                            address: this.user.address,
                        },
                        payment: {
                            period: this.payment.period,
                            type: this.payment.typePayment,
                            card_id: this.payment.card_id
                        }
                    };


                    axios.post('{{localeRoute('cart.settings.save')}}',
                        {
                            api_token: this.user.api_token,
                            settings
                        },
                        {headers: {'Content-Language': '{{app()->getLocale()}}'}}
                    ).then(result => {
                        if(result.data.status === 'success'){
                            this.calculate();
                        }
                    });


                },
                resetShippingAddress: function (){
                    this.addressesShipping.value = '';
                    this.user.address_region = '';
                    this.user.address_area = '';
                    this.user.address_city = '';
                    this.user.address = '';
                    this.saveCustomAddress = false;

                    this.area.list = [];
                    this.area.disabled = true;
                    this.city.list = [];
                    this.city.disabled = true;
                },
                calculate: function () {
                    let params = {
                        type: this.typeProcessing,
                        cart: this.products,
                        shipping: {
                            shipping_code: this.shipping.value,
                            address: {
                                region: this.user.address_region,
                                area: this.user.address_area,
                                city: this.user.address_city,
                                address: this.user.address,
                            }
                        },
                        period: this.payment.period
                    };

                    axios.post('/api/v1/order/calculate', params,
                        {headers: {'Content-Language': '{{app()->getLocale()}}'}}
                    ).then(result => {
                        if (result.data.status === 'success') {
                            //console.log(result.data.data);
                            this.payment.calculate = result.data.data;
                            this.payment.total = result.data.data.price.total;
                            this.payment.shipping = result.data.data.price.shipping;
                            this.payment.paymentMonthly = result.data.data.price.month;
                            this.smsBtnEnabled = true;
                            this.messages = [];
                        }else {
                            this.smsBtnEnabled = false;
                            this.messages = result.data.response.message;
                        }
                    });
                    //this.saveSettings();
                },
                setPaymentMethod: function (type) {
                    this.payment.typePayment = type;
                    switch (type) {
                        case 'card':
                            this.payment.isPaymentAccount = false;
                            this.payment.isPaymentCard = true;
                            break;
                        case 'account':
                            this.payment.isPaymentAccount = true;
                            this.payment.isPaymentCard = false;
                            break;
                    }
                    this.smsCode = null;
                    this.hashedSmsCode = null;
                    this.saveSettings();
                },
                pay: function () {
                    let check = true;
                    let params = {
                        api_token: '{{$api_token}}',
                        type: this.typeProcessing,
                        cart: this.products,
                        sms_code: this.smsCode,
                        shipping: {
                            shipping_code: this.shipping.value,
                            address: {
                                region: this.user.address_region,
                                area: this.user.address_area,
                                city: this.user.address_city,
                                address: this.user.address,
                            }
                        },
                        payment: {
                            type: this.payment.typePayment,
                            card_id: this.payment.card_id
                        },
                        period: this.payment.period
                    };

                    if(this.typeProcessing == 'direct' && this.payment.typePayment == 'account'){
                        if(this.payment.total > this.payment.personal_account) {
                            this.errors.push("{{__('frontend/order.payment_few_personal_account')}}");
                            check = false;
                        }
                    }
                    if(check) {
                        axios.post('/api/v1/order/payment/pay', params,
                            {headers: {'Content-Language': '{{app()->getLocale()}}'}}
                        ).then(result => {
                            if (result.data.status === 'success') {
                                //console.log(result.data.data);
                                this.showProcessing = false;
                            } else {
                                result.data.response.message.forEach(element => this.errors.push(element.text));
                            }
                        }).catch(e => {
                            this.errors.push(e);
                        });
                    }
                },
                sendSMS: function () {
                    //this.loading = true;
                    if(this.typeProcessing == 'direct') {
                        axios.post('/api/v1/orders/send-sms-code', {
                            phone: '{{$phone}}',
                        api_token: '{{$api_token}}',
                    },{headers: {'Content-Language': '{{app()->getLocale()}}'}})
                        .then(response => {
                            //this.loading = false;
                            this.hashedSmsCode = response.data.hash
                            this.resend.interval = 5;
                            this.resend.timer = setInterval(() => {
                                if (this.resend.interval > 0) {
                                    this.resend.interval = this.resend.interval - 1;
                                    this.resend.indicator = false;
                                } else {
                                    this.resend.indicator = true;
                                    clearInterval(this.resend.timer);
                                }
                            }, 1000);
                        })
                        .catch(e => {
                            this.messages.push(e);
                        })
                    } else {
                        if (this.calculate != null) {
                            axios.post('/api/v1/orders/make-preview', {
                                period: this.payment.period,
                                calculate: this.payment.calculate,
                                //company_id: this.company_id,
                                api_token: Cookies.get('api_token'),
                            }).then(response => {
                                //this.loading = false;
                                this.preview_offer = response.data.data.preview_offer;
                                this.hashedSmsCode = response.data.data.hashed;
                                this.resend.interval = 5;
                                this.resend.timer = setInterval(() => {
                                    if (this.resend.interval > 0) {
                                        this.resend.interval = this.resend.interval - 1;
                                        this.resend.indicator = false;
                                    } else {
                                        this.resend.indicator = true;
                                        clearInterval(this.resend.timer);
                                    }
                                }, 1000);
                            }).catch(e => {
                                this.messages.push(e);
                            })
                        }
                    }
                },
                checkSmsCode: function () {
                    this.errors = [];

                    if (this.smsCode) {
                        this.loading = true;
                        axios.post('/api/v1/login/check-sms-code', {
                            code: this.smsCode,
                            hashedCode: this.hashedSmsCode,
                            phone: '{{$phone}}',
                        },{headers: {'Content-Language': '{{app()->getLocale()}}'}}).then(response => {
                            this.loading = false;
                            if(response.data.status === 'success') {
                                this.pay();
                            } else {
                                response.data.response.message.forEach(element => this.errors.push(element.text));
                            }

                        }).catch(e => {
                            this.errors.push(e);
                        })
                    }
                    if (!this.smsCode) {
                        this.errors.push('{{__('auth.error_code_is_empty')}}');
                    }

                },
            },
            created: function () {
                this.loadSettings();
            }
        });
    </script>
@endsection
