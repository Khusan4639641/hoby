@extends('templates.panel.app')

@section('title', __('panel/pay_sys.header_partner_view'))
@section('class', 'partners show')

@section('center-header-prefix')
    <a class="link-back" href="{{localeRoute('panel.pay-system.index')}}"><img src="{{asset('images/icons/icon_arrow_green.svg')}}"></a>
@endsection


@section('content')


    <div class="organization">
        @if(isset($pay_system->logo) && $pay_system->logo != null)
            <div class="preview" style="background-image: url({{$pay_system->logo->preview}})"></div>
        @else
            <div class="preview dummy"></div>
        @endif

        <div class="info">
            <div class="id">ID {{$pay_system->id??$pay_system->id}}</div>
            <div class="name">{{$pay_system->title??$pay_system->title}}</div>
            <div class="brand">{!! $pay_system->url?$pay_system->url:"" !!}</div>
            <div class="description">{!! $pay_system->status !!}</div>
        </div>
    </div><!-- /.organization -->




    <div id="confirm">
        <div v-if="messages.length">
            <div class="small alert alert-success" v-for="message in messages">@{{ message }}</div>
        </div>
        <div v-if="errors.length">
            <div class="small alert alert-danger" v-for="error in errors">@{{ error }}</div>
        </div>

        @can('modify', $pay_system)

            <div class="form-controls">
                <button type="button" v-if="status == 0" v-on:click="confirm()" class="btn btn-success">{{__('app.btn_confirm')}}</button>
                <button type="button" v-if="status == 1" v-on:click="block()" class="btn btn-outline-danger">{{__('app.btn_block')}}</button>
                <button type="button" v-if="status == 1" v-on:click="resend()" class="btn btn-outline-success">{{__('panel/partner.btn_resend_password')}}</button>

                <a href="{{localeRoute('panel.partners.edit', $pay_system)}}" class="btn btn-primary ml-lg-auto">
                    {{__('panel/partner.btn_edit_data')}}
                </a>
            </div>
        @endcan
    </div>


    <script>
        var confirm = new Vue({
            el: '#confirm',
            data: {
                errors: [],
                messages: [],
                api_token: '{{Auth::user()->api_token}}',
                partner_id: '{{$pay_system->id}}',
                status: '{{$pay_system->status}}'
            },
            methods: {
                confirm: function () {
                    this.errors = [];
                    this.messages = [];

                    axios.post('/api/v1/employee/partners/action/confirm', {
                        api_token: this.api_token,
                        pay_system_id: this.pay_system_id
                    },{headers: {'Content-Language': '{{app()->getLocale()}}'}}).then(response => {
                        if (response.data.status === 'success') {
                            this.status = 1;
                            response.data.response.message.forEach(element => this.messages.push(element.text));
                        }
                    })
                },

                resend: function () {
                    this.errors = [];
                    this.messages = [];

                    axios.post('/api/v1/employee/partners/action/resend', {
                        api_token: this.api_token,
                        pay_system_id: this.pay_system_id
                    },{headers: {'Content-Language': '{{app()->getLocale()}}'}}).then(response => {
                        if (response.data.status === 'success') {
                            this.status = 1;
                            response.data.response.message.forEach(element => this.messages.push(element.text));
                        }
                    })
                },

                block: function () {
                    this.errors = [];
                    this.messages = [];

                    axios.post('/api/v1/employee/partners/action/block', {
                        api_token: this.api_token,
                        pay_system_id: this.pay_system_id
                    },{headers: {'Content-Language': '{{app()->getLocale()}}'}}).then(response => {
                        if (response.data.status === 'success') {
                            this.status = 0;
                            response.data.response.message.forEach(element => this.messages.push(element.text));
                        }
                    })
                }
            }
        });
    </script>
@endsection
