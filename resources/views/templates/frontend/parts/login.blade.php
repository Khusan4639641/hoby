<!-- Modal -->
<div class="modal fade" id="auth" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content" id="login">
            <div class="modal-header justify-content-center">
                <img src="{{asset('images/logo_white.svg')}}">
            </div>
            <div class="modal-body">
                    <form method="post" @submit="checkForm" ref="form" action="{{localeRoute('auth')}}">
                        <input type="hidden" v-model="user.token" name="_token">
                        <input type="hidden" v-model="hashedSmsCode" name="hashedCode">

                        <div class="phone">
                            <div class="after-input" v-if="showViewPhone">
                                <img src="{{asset('images/icons/icon_user_white_circle.svg')}}" />
                                <div class="number">@{{ user.phone }}</div>
                            </div>
                            <div class="before-input" v-else>
                                <label>{{__('auth.label_input_phone')}}</label>
                                <input class="form-control" v-mask="'+(998) ## ###-##-##'" id="inputPhone" v-model="user.phone" name="phone" type="text" autocomplete="off">
                                <div class="error" v-if="'phone' in errors">
                                    @{{ errors.phone }}
                                </div>
                            </div>
                        </div><!-- /.phone -->

                        <div v-if="showNextBtn" class="form-group">
                            <button v-on:click="checkPhone" type="button" class="btn btn-outline-light btn-arrow">{{__('app.btn_continue')}}</button>
                            <div class="alert alert-light">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                                {!!  __('auth.alert_phone_balance')!!}
                            </div>
                        </div>

                        <div class="sms" v-if="showInputSMSCode">
                            <div class="input">
                                <label for="inputSMSCode">{{__('auth.label_input_sms_code')}}</label>
                                {{--<pay-password ref="smsCode" :length="4" name="code" type="text" v-model="user.smsCode"></pay-password>--}}
                                <input ref="smsCode" class="sms-code" type="text" maxlength="4" name="code" v-model="user.smsCode">

                                <div>
                                    <div class="error" v-if="'code' in errors">
                                        @{{ errors.code }}
                                    </div>
                                    <div class="error" v-if="'msg' in errors">
                                        @{{ errors.msg }}
                                    </div>
                                </div>
                            </div>
                            <button v-on:click="checkSmsCode" type="button" class="btn btn-outline-light">{{__('app.btn_continue')}}</button><br/>
                            <button ref="resendSms" :disabled="!resend.indicator" v-on:click="checkPhone" class="btn-resend-sms" type="button">{{__('app.btn_resend_sms')}} <template v-if="!resend.indicator">({{__('auth.label_from')}} @{{ resend.interval }} {{__('auth.label_seconds')}})</template></button>
                        </div>
                    </form>

                    <a class="change-phone" v-if="showViewPhone" v-on:click="changePhoneNumber">{{__('auth.label_change_phone')}}</a>


            </div><!-- /.modal-body -->

            <div v-if="loading" class="loading active"><img src="{{asset('images/media/loader.svg')}}"></div>

        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script>
    function initBuyerData(){
        return {
            loading: false,
            errors: {},
            showInputSMSCode: false,
            showViewPhone: false,
            showNextBtn: true,
            hashedSmsCode: null,
            api_format: 'object',
            user: {
                phone: '+(998) ',
                password: '',
                smsCode: '',
                token: '{{ csrf_token() }}'
            },
            resend: {
                interval: 60,
                indicator: false,
                timer: null
            }
        }
    }
    var loginBuyer = new Vue({
        el: '#login',
        data: initBuyerData,
        computed: {
            hasName(value) {
                return this.containsKey(this.errors, value);
            }
        },
        mounted: function(){
            $('.link-user', '.dropdown-menu-right').click(function(){
                Object.assign(loginBuyer.$data, initBuyerData());
            });
        },
        methods: {
            containsKey(obj, key ) {
                return Object.keys(obj).includes(key);
            },
            checkPhone: function () {
                this.errors = {};
                if (this.user.phone) {
                    this.loading = true;

                    axios.post('/api/v1/login/validate-form', {
                        phone: this.user.phone,
                        role: 'buyer'
                    })
                        .then(response => {
                            if(response.data.status === 'success') {

                                if(response.data.response.auth_type == 'sms'){
                                    this.showInputSMSCode = true;
                                    this.showInputPassword = false;
                                    this.showViewPhone = true;
                                    this.sendSmsCode();
                                }else{
                                    this.showInputSMSCode = false;
                                    this.showInputPassword = true;
                                    this.showViewPhone = true;
                                }
                                this.showNextBtn = false;
                            } else {
                                this.errors = parseErrors(response);
                            }
                            this.loading = false;
                        })
                        .catch(e => {
                            Object.assign(this.errors, {msg: e});
                        })
                }

                if (!this.user.phone) {
                    Object.assign(this.errors, {phone: '{{__('auth.error_phone_is_empty')}}'});
                }

            },
            sendSmsCode: function () {
                this.loading = true;
                axios.post('/api/v1/login/send-sms-code', {
                    phone: this.user.phone
                })
                    .then(response => {
                        this.hashedSmsCode = response.data.hash
                        this.loading = false;

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
            },
            checkSmsCode: function () {
                this.errors = {};

                if (this.user.smsCode) {
                    this.loading = true;
                    axios.post('/api/v1/login/check-sms-code', {
                        code: this.user.smsCode,
                        hashedCode: this.hashedSmsCode,
                        phone: this.user.phone,
                        role: 'buyer',
                        api_format: this.api_format
                    }).then(response => {
                        if(response.data.status === 'success') {
                            this.login();
                        } else {
                            /*loginBuyer.$refs.smsCode.clear();*/
                            this.user.smsCode = '';
                            if(response.data.response.message != '')
                                response.data.response.message.forEach(element => Object.assign(this.errors, {code: element.text}));
                            loginBuyer.$forceUpdate();
                        }
                        this.loading = false;
                    }).catch(e => {
                        Object.assign(this.errors, {msg: e});
                    })
                }
                if (!this.user.smsCode) {
                    Object.assign(this.errors, {code: '{{__('auth.error_code_is_empty')}}'});
                }

            },

            checkForm: function(e){
                e.preventDefault();

                if(this.user.phone !== '' && this.user.code !== '')
                    return true;

                e.preventDefault();
            },
            changePhoneNumber: function(){
                Object.assign(this.$data, initBuyerData());
            },
            login: function () {
                this.errors = {};
                let data = {};
                data.phone = this.user.phone;
                data.role = 'buyer';
                data.api_format = this.api_format;
                data._token = this.user.token;
                if(this.showInputSMSCode){
                    data.hashedCode = this.hashedSmsCode;
                    data.code = this.user.smsCode;
                }
                if(this.showInputPassword)
                    data.password = this.user.password;

                this.loading = true;
                axios.post(`{{localeRoute('auth', app()->getLocale())}}`, data)
                    .then(response => {
                        if(response.data.status === 'success') {
                            location.href = `{{localeRoute('cabinet.index')}}`;
                        }else {
                            response.data.response.message.forEach(element => Object.assign(this.errors, {msg: element.text}));
                            loginBuyer.$forceUpdate();
                        }
                        this.loading = false;
                    }).catch(e => {
                        Object.assign(this.errors, {msg: e});
                });
            }
        }
    });

</script>
