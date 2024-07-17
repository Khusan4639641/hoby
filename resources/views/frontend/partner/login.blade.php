@extends('templates.frontend.app')
@section('class', 'partner auth')
@section('title', __('frontend/partner.header_login'))

@section('content')
    <style>
        main {
            height: calc(100vh - 205px);;
        }
    </style>

    <div id="partner-auth" class="bg-transparent">

        <div class="auth-body login">
            <p class="font-size-40 font-weight-bold mb-4">{{__('frontend/partner.header_login')}}</p>

            <form method="post"
                  @submit="checkForm"
                  ref="form"
                  action="{{localeRoute('auth')}}">
                <input type="hidden" v-model="user.token" name="_token">
                <input type="hidden" v-model="hashedSmsCode" name="hashedCode">

                <div class="form-group">
                    <div class="text-left">
                        <label for="inputPartnerId">{{__('auth.label_partner_id')}}</label>
                        <input class="form-control modified"
                               v-mask="'#####################'"
                               id="inputPartnerId"
                               v-model="user.partnerId"
                               name="partner_id"
                               type="text"
                               placeholder="{{ __('auth.placeholder_partner_id') }}">
                    </div>
                </div>

                <div class="form-group">
                    <div class="text-left">
{{--                        <div class="input-group-prepend">--}}
{{--                            <span class="input-group-text">{{__('auth.label_input_password')}}</span>--}}
{{--                        </div>--}}
                        <label for="inputPassword">{{__('auth.label_input_password')}}</label>
                        <input class="form-control modified"
                               v-model="user.password"
                               type="password"
                               name="password"
                               id="inputPassword"
                               placeholder="{{ __('auth.placeholder_input_password') }}">
                    </div>
                </div>

                <div v-if="errors.length">
                    <div class="error" v-for="error in errors">@{{ error }}</div>
                </div>
                <div v-if="messages.length">
                    <div class="text-success" v-for="message in messages">@{{ message }}</div>
                </div>

                <div class="form-group mt-4">
                    <button v-on:click="checkPartnerId"
                            type="submit"
                            class="btn btn-orange modern-shadow btn-block text-capitalize">
                        {{__('app.btn_enter')}}
                    </button>
                </div>

            </form>


        </div><!-- /.auth-body -->

        <div class="adv">
        </div><!-- /.adv -->

        <div v-if="loading" class="loading active"><img src="{{asset('images/media/loader.svg')}}"></div>
    </div><!-- /#partner-auth -->


    @include('frontend.partner.parts.login')
@endsection
