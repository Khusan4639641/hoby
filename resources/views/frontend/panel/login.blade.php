@extends('templates.frontend.app')
@section('class', 'panel login')
@section('title', __('frontend/panel.header_login'))

@section('content')
    <style>
        main {
            height: calc(100vh - 215px);;
        }
    </style>

    <div id="employee-auth">

        <div class="auth-body">

            <form method="post" @submit="checkForm" ref="form" action="{{localeRoute('auth')}}">
                <input type="hidden" v-model="user.token" name="_token">
                <input type="hidden" v-model="hashedSmsCode" name="hashedCode">
                <div class="phone">
                    <div class="form-group">
                        <label>{{__('auth.label_input_phone')}}</label>
                        <input class="form-control modified" v-mask="'+998#########'" id="inputPhone" v-model="user.phone" name="phone" type="text" autocomplete="off">
                        <div class="error" v-if="'phone' in errors">
                            @{{ errors.phone }}
                        </div>
                    </div>
                </div><!-- /.phone -->

                <div v-if="showNextBtn" class="form-group">
                    <button v-on:click="checkPhone" type="button" class="btn btn-outline-light btn-arrow">{{__('app.btn_continue')}}</button>
                </div>


                <div class="sms" v-if="showInputSMSCode">

                    <div class="input">
                        <label for="inputSMSCode">{{__('auth.label_input_sms_code')}}</label>
                        <pay-password :length="4" name="code" type="text" v-model="user.smsCode"></pay-password>

                        <div>
                            <div class="error" v-if="'code' in errors">
                                @{{ errors.code }}
                            </div>
                        </div>
                    </div>
                    <button v-on:click="checkSmsCode" type="button" class="btn btn-outline-light">{{__('app.btn_send')}}</button>

                </div>

                <div class="" v-if="showInputPassword">
                    <div class="form-group">
                        <label for="inputPassword">{{__('auth.label_input_password')}}</label>
                        <input v-model="user.password" type="password" name="password" class="form-control modified" id="inputPassword">
                        <div class="error" v-if="'password' in errors">
                            @{{ errors.password }}
                        </div>
                        <div class="error" v-if="'msg' in errors">
                            @{{ errors.msg }}
                        </div>
                    </div>
                    <div class="form-group">
                        <button v-on:click="checkPhone" type="submit" class="btn btn-orange btn-block">{{__('app.btn_enter')}}</button>
                    </div>
                </div>
            </form>
        </div><!-- /.auth-body -->
    </div><!-- /#employee-auth -->


    <script>
        function initEmployeeData(){
            return {
                errors: {},
                showInputSMSCode: false,
                showInputPassword: true,
                showNextBtn: false,
                hashedSmsCode: null,
                api_format: 'object',
                user: {
                    phone: '',
                    password: '',
                    smsCode: '',
                    token: '{{ csrf_token() }}'
                },
                resend: {
                    start: 60,
                    interval: null
                }
            }
        }
        var loginEmployee = new Vue({
            el: '#employee-auth',
            data: initEmployeeData,
            computed: {
                hasName(value) {
                    return this.containsKey(this.errors, value);
                }
            },
            methods: {
                containsKey(obj, key ) {
                    return Object.keys(obj).includes(key);
                },
                checkPhone: function () {
                    this.errors = {};
                    if (this.user.phone) {

                        axios.post('/api/v1/login/validate-form', {
                            phone: this.user.phone,
                            role: 'employee',
                        },
                            {headers: {'Content-Language': '{{app()->getLocale()}}'}})
                            .then(response => {
                                if(response.data.status === 'success') {
                                    console.dir(response.data.response);
                                    if(response.data.response.auth_type == 'sms'){
                                        this.showInputSMSCode = true;
                                        this.showInputPassword = false;
                                        this.sendSmsCode();
                                    }else{
                                        this.showInputSMSCode = false;
                                        this.showInputPassword = true;
                                        this.checkPassword();
                                    }
                                    this.showNextBtn = false;
                                } else {
                                    //this.errors = parseErrors(response);
                                    response.data.response.message.forEach(element => Object.assign(this.errors, {msg: element.text}));
                                    loginEmployee.$forceUpdate();
                                }

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
                    axios.post('/api/v1/login/send-sms-code', {
                        phone: this.user.phone,
                        role: 'employee'
                    },
                        {headers: {'Content-Language': '{{app()->getLocale()}}'}})
                        .then(response => {
                            this.hashedSmsCode = response.data.hash
                        })
                        .catch(e => {
                            this.errors.push(e);
                        })
                },
                checkSmsCode: function () {
                    this.errors = {};

                    if (this.user.smsCode) {
                        axios.post('/api/v1/login/check-sms-code', {
                            code: this.user.smsCode,
                            hashedCode: this.hashedSmsCode,
                            phone: this.user.phone,
                            role: 'employee',
                            api_format: this.api_format
                        },
                            {headers: {'Content-Language': '{{app()->getLocale()}}'}}).then(response => {
                            if(response.data.status === 'success') {
                                this.login();
                            } else {
                                response.data.response.message.forEach(element => Object.assign(this.errors, {code: element.text}));
                                loginEmployee.$forceUpdate();
                            }
                        }).catch(e => {
                            Object.assign(this.errors, {msg: e});
                        })
                    }
                    if (!this.user.smsCode) {
                        Object.assign(this.errors, {code: '{{__('auth.error_code_is_empty')}}'});
                    }

                },
                checkPassword: function(){
                    this.errors = {};

                    if (this.user.password){
                        axios.post('/api/v1/login/check-password',{
                            phone: this.user.phone,
                            password: this.user.password,
                            role: 'employee',
                            api_format: this.api_format
                        },
                            {headers: {'Content-Language': '{{app()->getLocale()}}'}}).then(response => {
                            if(response.data.status === 'success') {
                                this.login();
                            } else {
                                response.data.response.message.forEach(element => Object.assign(this.errors, {password: element.text}));
                                loginEmployee.$forceUpdate();
                            }
                        }).catch(e => {
                            Object.assign(this.errors, {msg: e});
                        })
                    }
                    if (!this.user.password){
                        Object.assign(this.errors, {msg: '{{__('auth.error_password_is_empty')}}'});
                    }
                },
                checkForm: function(e){
                    e.preventDefault();
                    if(this.user.phone !== '' && this.user.password !== ''){
                        return true;
                    }
                    if(this.user.phone !== '' && this.user.code !== ''){
                        return true;
                    }
                },
                login: function () {
                    this.errors = {};
                    let data = {};
                    data.phone = this.user.phone;
                    data.role = 'employee';
                    data.api_format = this.api_format;
                    data._token = this.user.token;
                    if(this.showInputSMSCode){
                        data.hashedCode = this.hashedSmsCode;
                        data.code = this.user.smsCode;
                    }
                    if(this.showInputPassword)
                        data.password = this.user.password;
                    axios.post('{{localeRoute('auth')}}', data,
                        {headers: {'Content-Language': '{{app()->getLocale()}}'}})
                        .then(response => {
                            if(response.data.status === 'success') {
                                location.href = `{{ route('panel.index')}}`;
                            }else {
                                response.data.response.message.forEach(element => Object.assign(this.errors, {msg: element.text}));
                                loginEmployee.$forceUpdate();
                            }
                        }).catch(e => {
                        Object.assign(this.errors, {msg: e});
                    });
                }
            }
        });

    </script>
@endsection
