<!-- Modal -->
<div class="modal fade" id="delayModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" id="payment-method">
            <div class="modal-header">
                <h5 class="modal-title">{{__('cabinet/order.header_delay')}}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div v-if="!delaySuccess" class="modal-body">


                    <div v-if="messages">
                        <div v-for="item in messages" :class="'alert alert-' + item.type">@{{ item.text }}</div>
                    </div>

                    <div v-if="errors.length">
                        <div class="alert alert-danger" v-for="message in errors">@{{ message }}</div>
                    </div>

                    <div class="select-payment">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" id="paymentMethodCard" type="radio" v-model="payment.typePayment" name="type_payment" @click="setPaymentMethod('card')" value="card" checked/>
                            <label class="form-check-label" for="paymentMethodCard">{{__('frontend/order.payment_card')}}</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" id="paymentMethodAccount" type="radio" v-model="payment.typePayment" name="type_payment" @click="setPaymentMethod('account')" value="account"/>
                            <label class="form-check-label" for="paymentMethodAccount">
                                {{__('frontend/order.payment_account')}} ({{$info['buyer']->settings->personal_account}} {{__('app.currency')}})
                            </label>
                        </div>
                    </div>

                    <div class="row" v-if="payment.isPaymentCard">
                        <div class="col-12 col-md-6">
                            <div class="title">{{__('cabinet/index.my_cards')}}</div>
                            <table class="table cards-list">
                                @foreach($info['buyer']->cards as $index => $item)
                                    <tr>
                                        <td>
                                            <div class="form-check">
                                                <input v-model="payment.card_id" name="refill_card" @if($index == 0) checked @endif value="{{$item->id}}" class="form-check-input refill-card" type="radio" id="radioCard{{$index}}">
                                                <label class="form-check-label" for="radioCard{{$index}}">
                                                    {{$item->public_number}}
                                                </label>
                                            </div>
                                        </td>
                                        <td>
                                            <img src="{{asset('images/icons/icon_'.strtolower($item->type).'_grey.svg')}}" alt="">
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </div><!-- /.col-12 col-md-6 -->
                    </div><!-- /.row -->


                    <div class="form-group">
                        <label>{{__('cabinet/order.txt_enter_summ')}}</label>
                        <number-input v-model="payment.total" :max-value="{{$info['debt']}}"/>
                    </div>

                    <div class="form-group">
                        <label>{{__('cabinet/order.txt_enter_sms')}}</label>
                        <div class="sms form-row">
                            <div class="col-5 col-sm">
                                <input :disabled="hashedSmsCode == null" placeholder="{{__('billing/order.txt_sms_code')}}" class="form-control" required v-model="smsCode" type="text">
                            </div>
                            <div class="col-7 col-sm pl-0">
                                <div class="error text-danger" v-if="'code' in errors">
                                    @{{ errors.code }}
                                </div>
                                <button :disabled="payment.total<=0" v-if="hashedSmsCode == null" @click="sendSMS" type="button" class="btn btn-success">{{__('billing/order.btn_get_sms_code')}}</button>
                                <button :disabled="!resend.indicator" ref="resendSms" v-if="hashedSmsCode != null" @click="sendSMS" class="btn btn-primary btn-resend-sms" type="button">{{__('app.btn_resend_sms')}} <template v-if="!resend.indicator">({{__('auth.label_from')}} @{{ resend.interval }} {{__('auth.label_seconds')}})</template></button>
                            </div>
                        </div>
                    </div>
            </div>
            <div v-if="!delaySuccess" class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{__('app.btn_cancel')}}</button>
                <button type="button" class="btn btn-success" :disabled="smsCode == null || smsCode == ''" @click="checkSmsCode()">{{__('cabinet/order.btn_delay')}}</button>
            </div>
            <div v-if="delaySuccess" class="modal-body">
                <div v-for="item in messages" :class="'alert alert-' + item.type" v-html="item.text"></div>
            </div>
            <div v-if="delaySuccess" class="modal-footer">
                <a class="btn btn-primary" href="{{localeRoute('cabinet.index')}}">{{__('app.btn_reload_page')}}</a>
            </div>
        </div><!-- /.modal-content -->
    </div>
</div>


<template id="number-input">
    <div>
        <input
            :value="value"
            type="text"
            @input="onInput"
        >
    </div>
</template>
<script>
    Vue.component('number-input', {
        template: '#number-input',
        props: {
            value: Number,
            maxValue: Number
        },
        methods: {
            onInput(event) {
                const newValue = parseFloat(event.target.value)
                const clampedValue = Math.min(newValue, this.maxValue)
                this.$emit('input', newValue)
                this.$nextTick(()=>{
                    this.$emit('input', clampedValue)
                })
            }
        }
    });
    var payment = new Vue({
        el: '#payment-method',
        data: {
            errors: [],
            messages: [],
            loading: true,
            locale: '{{ucfirst(app()->getLocale())}}',
            smsCode: null,
            hashedSmsCode: null,
            showProcessing: true,
            orderNumber: '',
            //smsBtnEnabled: true,
            payment: {
                isPaymentAccount: false,
                isPaymentCard: true,
                typePayment: 'card',
                total: {{$info['debt']}},
                card_id: {{count($info['buyer']->cards) >0 ? $info['buyer']->cards[0]->id : 'null'}},
                personal_account: {{$info['buyer']->settings->personal_account}},
            },
            resend: {
                interval: 60,
                indicator: false,
                timer: null
            },
            delaySuccess: false
        },
        methods: {
            formatPrice: function(price = null){
                let separator = ' ';
                price = price.toString();
                return price.replace(/(\d{1,3}(?=(\d{3})+(?:\.\d|\b)))/g,"\$1"+separator);
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
            },
            pay: function () {
                let params = {
                    api_token: '{{$info['buyer']->api_token}}',
                    sms_code: this.smsCode,
                    payment: {
                        type: this.payment.typePayment,
                        card_id: this.payment.card_id,
                        order_id: this.payment.order_id,
                        total: this.payment.total
                    },
                };

                this.showProcessing = true;
                axios.post('/api/v1/order/payment/delay', params,
                    {headers: {'Content-Language': '{{app()->getLocale()}}'}}
                ).then(result => {
                    if (result.data.status === 'success') {
                        this.showProcessing = false;
                        this.delaySuccess = true;
                        this.messages = result.data.response.message;
                    }
                });
            },
            sendSMS: function () {
                let check = true;
                this.errors = [];
                if(this.payment.typePayment == 'account'){
                    if(this.payment.total > this.payment.personal_account) {
                        this.errors.push("{{__('frontend/order.payment_few_personal_account')}}");
                        check = false;
                    }
                }
                if(check) {
                    axios.post('/api/v1/orders/send-sms-code', {
                        phone: '{{$info['buyer']->phone}}',
                        api_token: '{{$info['buyer']->api_token}}',
                    }, {headers: {'Content-Language': '{{app()->getLocale()}}'}})
                        .then(response => {
                            this.loading = false;
                            this.hashedSmsCode = response.data.hash

                            this.resend.interval = 60;
                            this.resend.timer = setInterval(() => {

                                if( this.resend.interval > 0) {
                                    this.resend.interval--;
                                    this.resend.indicator = false;
                                }else {
                                    this.resend.indicator = true;
                                     clearInterval(this.resend.timer);
                                }
                            }, 1000);
                        })
                        .catch(e => {
                            this.errors.push(e);
                        })
                }
            },
            checkSmsCode: function () {
                this.errors = [];

                if (this.smsCode) {
                    this.loading = true;
                    axios.post('/api/v1/login/check-sms-code', {
                        code: this.smsCode,
                        hashedCode: this.hashedSmsCode,
                        phone: '{{$info['buyer']->phone}}',
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
        }
    });
</script>
