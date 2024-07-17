@extends('templates.frontend.app')
@section('class', 'partner auth')
@section('title', __('frontend/partner.header_register'))

@section('content')

    <div id="register">
{{--        <div class="auth-header">--}}
{{--            <img src="{{asset('images/logo_white.svg')}}">--}}
{{--        </div><!-- /.auth-header -->--}}

        <div class="auth-body">
            <div v-if="!success">

{{--                <h1>{{__('frontend/partner.header_register')}}</h1>--}}
                <p class="font-size-40">{{__('frontend/partner.header_register')}}</p>


                <form method="post" ref="form" action="{{localeRoute('auth')}}">

{{--                    <div class="alert alert-warning">--}}
{{--                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">--}}
{{--                            <span aria-hidden="true">&times;</span>--}}
{{--                        </button>--}}
{{--                        {!!  __('auth.alert_all_fields_necessary')!!}--}}
{{--                    </div>--}}
                    <div class="form-row">
                        <div class="form-group col-md-6 pr-md-3">
                            <label for="inputCompanyName">{{__('auth.label_input_company_name')}}</label>
                            <input @blur="isEmpty" v-model="user.company.name" type="text" name="company_name" class="form-control modified" id="inputCompanyName" required>
                        </div>
                        <div class="form-group col-md-6 pl-md-3">
                            <label for="exampleFormControlSelect1">{{__('billing/catalog.catalog_categories')}}</label>
                            <select name="type_company"
                                    v-model="user.company.type"
                                    required
                                    class="form-control modified"
                                    id="exampleFormControlSelect1"
                                    type="text"
                                    @blur="isEmpty">
                                <option value="2">2</option>
                            </select>
{{--                            <label for="inputSurname">{{__('auth.label_input_surname')}}</label>--}}
{{--                            <input @blur="isEmpty" v-model="user.surname" type="text" name="surname" class="form-control modified" id="inputSurname" required>--}}
                        </div>
                        <div class="form-group col col-md-6 pr-md-3">
                            <label for="inputPhoneReg">{{__('auth.label_input_phone')}}</label>
                            <input id="inputPhoneReg"
                                   v-mask="'+998#########'"
                                   v-model="user.phone"
                                   name="phone"
                                   class="form-control modified"
                                   type="text"
                                   required>
                        </div>
                        <div class="form-group col-md-6 pl-md-3">
                            <label for="inputName">{{__('auth.label_input_name')}}</label>
                            <input @blur="isEmpty"
                                   v-model="user.name"
                                   type="text"
                                   name="name"
                                   class="form-control modified"
                                   id="inputName"
                                   required>
                        </div>

                        {{--<div class="form-group col">
                            <label for="inputName">{{__('auth.label_input_patronymic')}}</label>
                            <input @blur="isEmpty" v-model="user.patronymic" type="text" name="patronymic" class="form-control" id="inputName" required>
                        </div>--}}
                    </div><!-- /.form-row -->


{{--                    <div class="form-group">--}}
{{--                        <label for="inputCompanyName">{{__('auth.label_input_company_name')}}</label>--}}
{{--                        <input @blur="isEmpty" v-model="user.company.name" type="text" name="company_name" class="form-control" id="inputCompanyName" required>--}}
{{--                    </div>--}}

{{--                    <div class="form-row align-items-end">--}}
{{--                        <div class="form-group col col-md-6">--}}
{{--                            <label for="inputPhoneReg">{{__('auth.label_input_phone')}}</label>--}}
{{--                            <input id="inputPhoneReg" v-mask="'+998#########'" v-model="user.phone" name="phone" class="form-control" type="text" required>--}}
{{--                        </div>--}}
{{--                        <div v-if="showInputSMSCode" class="form-group col-6 col-md-3">--}}
{{--                            <label for="inputSMSCode">{{__('auth.label_input_sms_code')}}</label>--}}
{{--                            <input v-mask="'####'" v-model="user.smsCode" @keypress="isNumber" type="text" name="code" class="form-control" autocomplete="off" id="inputSMSCode" required>--}}
{{--                        </div>--}}
{{--                        <div v-if="!showInputReq" class="form-group col-6 col-md-3 controls text-right">--}}
{{--                            <button v-if="showNextBtn" v-on:click="checkPhone" type="button" class="btn btn-success">{{__('app.btn_get_sms')}}</button>--}}
{{--                            <button v-on:click="checkSmsCode" v-if="showInputSMSCode" type="button" class="btn btn-success">{{__('app.btn_check')}}</button>--}}
{{--                        </div>--}}
{{--                    </div>--}}

{{--                    <hr>--}}

                    <div class="form-row">
                        <div class="form-group col-12">
                            <div v-if="errors.length">
                                <div class="error" v-for="error in errors">@{{ error }}</div>
                            </div>
                        </div>
                        <div class="form-group col-12 text-center">
                            <button :disabled="!canSubmit"
                                    v-on:click="checkForm"
                                    type="button"
                                    :class="[ 'btn', 'btn-orange', !canSubmit ? '' : 'modern-shadow' ]">
                                {{__('app.btn_send')}}
                            </button>
                        </div>
                    </div>

                </form>
            </div>
            <div v-else class="register-success-container">
                <img src="{{ asset('assets/icons/Badge.svg') }}" alt="Success">
                <p class="font-weight-bold font-size-40">{{__('frontend/partner.header_registration_success')}}</p>
                <div class="text mb-4">
                    {!! __('frontend/partner.text_registration_success') !!}
                </div>
                <a role="button" class="btn btn-orange modern-shadow continue-button" href="{{localeRoute('home')}}">{{__('app.btn_back')}}</a>
            </div>
        </div><!-- /.auth-body -->

        <div v-if="loading" class="loading active"><img src="{{asset('images/media/loader.svg')}}"></div>

    </div><!-- /#register -->



    <script>
        var login = new Vue({
            el: '#register',
            data: {
                errors: [],
                success: false,
                company_type: '',
                showInputSMSCode: false,
                showNextBtn: true,
                showInputReq: true,
                hashedSmsCode: null,
                api_format: 'object',
                user: {
                    phone: '',
                    name: '',
                    surname: '',
                    patronymic: '',
                    smsCode: '',
                    company: {
                        name: '',
                        type: '',
                    }
                },
                loading: false
            },
            computed: {
                isValid: function(e){
                    console.dir(e.target);
                    return false;//e.target.value !== '';
                },
                canSubmit() {
                    return this.user.company.type !== ''
                        && this.user.company.name !== ''
                        && this.user.phone !== ''
                        && this.user.name !== '';
                },
            },
            methods: {
                checkPhone: function () {
                    this.errors = [];
                    if (this.user.phone) {
                        this.loading = true;
                        axios.post('/api/v1/register/validate-form', {
                            phone: this.user.phone,
                            step: 1
                        },
                            {headers: {'Content-Language': '{{app()->getLocale()}}'}})
                            .then(response => {
                                this.loading = false;
                                if(response.data.status === 'success') {
                                        this.showInputSMSCode = true;
                                        this.sendSmsCode();
                                        this.showNextBtn = false;
                                } else {
                                    this.errors = parseErrors(response);
                                }

                            })
                            .catch(e => {
                                this.errors.push(e);
                            })

                    }

                    if (!this.user.phone) {
                        this.errors.push('{{__('auth.error_phone_is_empty')}}');
                    }

                },

                sendSmsCode: function () {
                    this.loading = true;
                    axios.post('/api/v1/register/send-sms-code', {
                        phone: this.user.phone
                    },
                        {headers: {'Content-Language': '{{app()->getLocale()}}'}})
                        .then(response => {
                            this.loading = false;
                            this.hashedSmsCode = response.data.hash
                        })
                        .catch(e => {
                            this.errors.push(e);
                        })
                },

                checkSmsCode: function () {
                    this.errors = [];

                    if (this.user.smsCode) {
                        this.loading = true;
                        axios.post('/api/v1/register/check-sms-code', {
                            code: this.user.smsCode,
                            hashedCode: this.hashedSmsCode,
                            phone: this.user.phone,
                            api_format: this.api_format
                        },
                            {headers: {'Content-Language': '{{app()->getLocale()}}'}}).then(response => {
                            this.loading = false;
                            if(response.data.status === 'success') {
                                //this.checkForm();
                                response.data.response.message.forEach(element => this.errors.push(element.text));
                                this.showInputReq = true;
                            } else {
                                this.showInputReq = false;
                                response.data.response.message.forEach(element => this.errors.push(element.text));
                            }

                        }).catch(e => {
                            this.errors.push(e);
                        })
                    }
                    if (!this.user.smsCode) {
                        this.errors.push('{{__('auth.error_code_empty')}}');
                    }

                },

                checkForm: function(){
                    let isCheck = true;
                    for(var k in this.user.company)
                        if(this.user.company[k] === ''){
                            isCheck = false;
                        }
                    if(isCheck) this.register();
                    else this.errors.push('{{__('auth.err_empty_required_fields')}}');
                },

                isEmpty: function(e){
                    let isCheck = true;
                    if(e.target.value === ''){
                        isCheck = false;
                        e.target.classList.add('is-invalid');
                    }else{
                        e.target.classList.remove('is-invalid');
                    }
                    return isCheck;
                },

                isNumber: function(e) {
                    let charCode = (e.which) ? e.which : e.keyCode;
                    if ((charCode > 31 && (charCode < 48 || charCode > 57)) && charCode !== 46) {
                        e.preventDefault();
                    } else {
                        return true;
                    }
                },

                register: function () {
                    this.errors = [];
                    let data = {};
                    data.phone = this.user.phone;
                    data.partner = this.user;
                    data.api_format = this.api_format;
                    this.loading = true;
                    axios.post('/api/v1/register/add', data,
                        {headers: {'Content-Language': '{{app()->getLocale()}}'}})
                        .then(response => {
                            this.loading = false;
                            if (response.data.status === 'success') {
                                this.success = true;
                            } else {
                                response.data.response.message.forEach(element => this.errors.push(element.text));
                            }
                        }).catch(e => {
                        this.errors.push(e);
                    });
                }
            }
        })
    </script>
@endsection
