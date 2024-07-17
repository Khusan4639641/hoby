@extends('templates.cabinet.app')

@section('h1', __('cabinet/card.header'))
@section('title', __('cabinet/card.header'))
@section('center-header-prefix')
    <a class="link-back" href="{{localeRoute('cabinet.index')}}"><img src="{{asset('images/icons/icon_arrow_orange.svg')}}"></a>
@endsection

@section('class', 'cards list')

@section('content')
    <div class="" id="card">
        <div v-if="loading" class="loader">
            <img src="{{asset('images/media/loader.svg')}}">
        </div>
        <div v-else>
            <div class="cards" v-if="user.cards != null">
                <div class="list">
                    <div class="row">
                        <div class="col-12 col-md-4" v-for="item in user.cards" :key="item.id">
                            <div :class="'item ' + item.type">
                                <div class="number">@{{ item.card_number }}</div>
                                <div class="date">@{{ item.card_valid_date }}</div>
                            </div><!-- /.item -->
                        </div>

                        <div class="col-12 col-md-4">
                            <div v-on:click="openFormAddCard" type="submit" class="item add btn btn-success">
                                <div class="number">{{__('card.btn_add')}}</div>
                                <div class="types">{{__('card.uzcard_humo')}}</div>
                            </div>
                        </div>
                    </div><!-- /.row -->
                </div><!-- /.list -->
            </div><!-- .cards -->
            <div v-else>
                {{__('panel/buyer.txt_empty_card_list')}}
            </div>


            <div class="buyer-card__add" v-if="showFormAddCards">
                <hr>

                <div class="lead">{{__('card.btn_add')}}</div>

                <div class="form-row">
                    <div class="form-group col-6 col-md-4 col-lg-3">
                        <label for="inputCardNumber">{{__('panel/buyer.card_number')}}</label>
                        <input v-model="user.card.number" type="text" :class="'form-control' + (errors.cardNumber?' is-invalid':'')"
                               id="inputCardNumber"
                               v-mask="'#### #### #### ####'">
                        <div class="error" v-for="item in errors.cardNumber">@{{ item }}</div>
                    </div>
                    <div class="form-group col-6 col-md-4 col-lg-3">
                        <label for="inputCardExp">{{__('panel/buyer.card_expired_date')}}</label>
                        <input v-mask="'##/##'" v-model="user.card.exp" type="text" :class="'form-control' + (errors.exp?' is-invalid':'')"
                               id="inputCardExp">
                        <div class="error" v-for="item in errors.exp">@{{ item }}</div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-6 col-md-4 col-lg-3">
                        <input :disabled="!showInputSMSCode" v-model="user.smsCode" type="text" :class="(errors.smsCode?'is-invalid':'') + ' form-control'" v-mask="'######'">
                        <div class="error" v-for="item in errors.smsCode">@{{ item }}</div>
                    </div>
                    <div class="form-group col-6 col-md-4 col-lg-3">
                        <button v-if="showInputSMSCode" v-on:click="checkSmsCode" type="submit"
                                class="btn btn-success mr-4">{{__('panel/buyer.btn_card_save')}}</button>
                        <a class="btn btn-success" href="javascript:" v-on:click="sendSmsCode"
                           v-if="showInputSMSCode===false">{{__('app.btn_get_sms')}}</a>
                    </div>
                    <div class="form-group col-12 col-md-12 col-lg-12" v-if="showInputSMSCode===true">{{__('panel/buyer.txt_expire_sms_code')}}: @{{ timers }}</div>
                    <button v-if="showInputSMSCode===true" ref="resendSms" :disabled="!resend.indicator" v-on:click="sendSmsCode"
                            class="btn-resend-sms" type="button">{{__('app.btn_resend_sms')}}
                        <template v-if="!resend.indicator">({{__('auth.label_from')}} @{{ resend.interval }} {{__('auth.label_seconds')}})</template>
                    </button>
                </div>

                <div class="error" v-for="item in errors.phone">@{{ item }}</div>

            </div>
        </div>
    </div>

    <script>
        let card = new Vue({
            el: '#card',
            data: {
                errors: {},
                messages: [],
                showInputSMSCode: false,
                hashedSmsCode: '',
                showFormAddCards: false,
                loading: false,
                locale: '{{ucfirst(app()->getLocale())}}',

                user: {
                    api_token: '{{$buyer->api_token}}',
                    buyer_id: '{{$buyer->id}}',
                    phone: '{{$buyer->phone}}',
                    name: '',
                    verify: null,
                    cards: null,
                    card: {
                        number: '',
                        exp: ''
                    },
                },
                resend: {
                    interval: 60,
                    indicator: false,
                    timer: null
                },
                timer: 120
            },
            computed: {
                timers: function(){
                    return new Date(this.timer * 1000).toISOString().substr(14, 5);
                }
            },
            methods: {
                cardAdd: function(){
                    let formData = new FormData();

                    formData.append('card_number', this.user.card.number);
                    formData.append('card_valid_date', this.user.card.exp);

                    formData.append('api_token', this.user.api_token);
                    formData.append('buyer_id', this.user.buyer_id);
                    if(this.user.verify !== null)
                        formData.append('verify', this.user.verify);
                    // if(this.user.verifyHumo !== null)
                    //     formData.append('verify_humo', this.user.verifyHumo);


                    axios.post('/api/v1/buyer/card/add', formData,
                        {headers: {'Content-Language': '{{app()->getLocale()}}'}})
                        .then(response => {
                            if (response.data.status === 'success') {
                                this.errors = {};
                                this.messages = [];
                                this.updateList();
                                this.messages.push('{{__('panel/buyer.txt_card_added')}}');
                                this.resetStep();
                            } else {
                                this.errors = response.data.response.errors;
                            }
                            card.$forceUpdate();
                        })
                        .catch(e => {
                            this.errors.system = [];
                            this.errors.system.push(e);
                        })

                },
                openFormAddCard: function (e) {
                    this.showFormAddCards = true;
                },
                sendSmsCode: function (e) {

                    this.errors = {};
                    this.messages = [];
                    this.errors.phone = [];
                    let type = 2;
                    let url = '/api/v1/buyer/send-sms-code-humo',
                        obj = {
                            phone: this.user.phone,
                            api_token: this.user.api_token,
                            card: this.user.card.number,
                            exp: this.user.card.exp,
                            //buyer_id: this.user.buyer_id
                        };
                    if(/^8600/.test(this.user.card.number)) {
                        url = '/api/v1/buyer/send-sms-code-uz';
                        obj.card = this.user.card.number;
                        obj.exp = this.user.card.exp;
                        //obj.buyer_id = this.user.buyer_id;
                        type = 1;
                    }
                    if (this.user.card.number && this.user.card.exp) {

                        axios.post(url, obj,
                            {headers: {'Content-Language': '{{app()->getLocale()}}'}})
                            .then(response => {

                                console.log(response);
                                err = false;
                                if (response.data.status === 'error_phone_not_equals') {
                                    err=true;
                                    this.errors.phone.push('{{__('panel/buyer.error_phone_not_equals_')}}');
                                }
                                if (response.data.status === 'error_card_equal') {
                                    this.errors.phone.push('{{__('panel/buyer.error_card_equal')}}');
                                    err=true;
                                }
                                if (response.data.status === 'error_scoring') {
                                    this.errors.phone.push('{{__('panel/buyer.error_scoring')}}');
                                    err=true;
                                }
                                if (response.data.status === 'error_card_exp') {
                                    this.errors.phone.push('{{__('panel/buyer.error_card_exp')}}');
                                    err=true;
                                }
                                if (response.data.status === 'error_card_scoring') {
                                    this.errors.phone.push('{{__('panel/buyer.error_card_scoring')}}');
                                    err=true;
                                }

                                if(!err) {
                                    this.hashedSmsCode = response.data.hash;

                                    /*if(type === 1) {
                                        this.hashedSmsCode = response.data;
                                    }else if(type == 2){
                                        this.hashedSmsCode = response.data;
                                        //this.hashedSmsCode = response.data.hash;
                                        //this.user.phone = response.data.phone;
                                        //this.user.name = response.data.name;
                                    }*/

                                    this.resend.interval = 60;
                                    this.resend.timer = setInterval(() => {
                                        if (this.resend.interval > 0) {
                                            this.resend.interval--;
                                            this.resend.indicator = false;
                                        } else {
                                            this.resend.indicator = true;
                                            clearInterval(this.resend.timer);
                                        }
                                    }, 1000);
                                    this.showInputSMSCode = true;
                                    this.timer = 120;
                                    this.countDownTimer();
                                }
                                card.$forceUpdate();

                            })
                            .catch(e => {
                                this.errors.smsCard = [];
                                this.errors.smsCard.push(e);
                            })

                    }
                    if (!this.user.card.number) {
                        this.errors.cardNumber = [];
                        this.errors.cardNumber.push('{{__('panel/buyer.error_card_number_empty')}}');
                    }
                    if (!this.user.card.exp) {
                        this.errors.exp = [];
                        this.errors.exp.push('{{__('panel/buyer.error_card_exp_empty')}}');
                    }


                },
                checkSmsCode: function (e) {
                    this.errors = {};
                    this.messages = [];
                    let url = '/api/v1/buyer/check-sms-code-humo';
                    if(/^8600/.test(this.user.card.number))
                        url = '/api/v1/buyer/check-sms-code-uz';

                    console.log('check-sms');

                    if (this.user.smsCode) {
                        axios.post(url, {
                            code: this.user.smsCode,
                            hashedCode: this.hashedSmsCode,
                            phone: this.user.phone,
                            name: this.user.name,
                            api_format: this.api_format,
                            api_token: this.user.api_token,
                            card_number: this.user.card.number,
                            card_valid_date: this.user.card.exp,
                            buyer_id: this.user.buyer_id
                        },
                            {headers: {'Content-Language': '{{app()->getLocale()}}'}})
                            .then(response => {
                                if (response.data.status === 'success') {
                                    this.user.verify = response.data.data;
                                    //this.user.verifyHumo = response.data.data.humo;
                                    this.cardAdd();
                                } else {
                                    this.errors.smsCode = [];
                                    response.data.response.message.forEach(element => this.errors.smsCode.push(element.text));
                                }
                                card.$forceUpdate();
                            })
                            .catch(e => {
                                this.errors.system = [];
                                this.errors.system.push(e);
                            })
                    }

                    if (!this.user.smsCode) {
                        this.errors.smsCode = [];
                        this.errors.smsCode.push('{{__('auth.error_code_empty')}}');
                    }

                },
                updateList: function () {
                    if (!this.loading) {
                        this.loading = true;

                        axios.post('/api/v1/buyer/card/list', {
                            api_token: this.user.api_token,
                            user_id: this.user.buyer_id
                        },
                            {headers: {'Content-Language': '{{app()->getLocale()}}'}}).then(response => {
                            if (response.data.status === 'success') {
                                this.user.cards = response.data.data;

                                if(Object.keys(this.user.cards).length > 0){
                                    this.showFormAddCards = false;
                                } else {
                                    this.showFormAddCards = true;
                                }
                            }
                            this.loading = false;
                            card.$forceUpdate();
                        })
                    }
                },
                resetStep: function (){
                    this.user.card.number = null;
                    this.user.card.exp = null;
                    this.user.smsCode = null;
                    this.showInputSMSCode = false;
                },
                countDownTimer: function(){
                    if(this.timer > 0) {
                        setTimeout(() => {
                            this.timer --;
                            this.countDownTimer()
                        }, 1000)
                    }else if(this.timer == 0){
                        this.showInputSMSCode = false;
                    }
                }

            },
            mounted: function() {
            },
            created: function () {
                this.updateList();
            },
        })
    </script>
@endsection
