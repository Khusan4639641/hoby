@extends('templates.panel.app')

@section('title', __('panel/partner.header_partner_view'))
@section('class', 'partners show')

@section('center-header-prefix')
    <a class="link-back" href="{{localeRoute('panel.partners.index')}}"><img src="{{asset('images/icons/icon_arrow_green.svg')}}"></a>
@endsection


@section('content')


    <div class="organization">
        @if($saller->logo != null)
            <div class="preview" style="background-image: url({{$saller->logo->preview}})"></div>
        @else
            <div class="preview dummy"></div>
        @endif

        <div class="info">
            <div class="id">ID {{$saller->id}}</div>
            <div class="name">{{$saller->brand??$saller->name}}</div>
            <div class="brand">{!! $saller->brand?$saller->name:"" !!}</div>
            <div class="description">{!! $saller->description !!}</div>
        </div>
    </div><!-- /.organization -->


    <div class="row params">

        <div class="col-12 col-sm part">
            <td><div class="caption">{{__('panel/partner.markup_3')}}</div></td>
            <td><div class="value">{{@$saller->settings->markup_3}}%</div></td>
        </div>
        <div class="col-12 col-sm part">
            <td><div class="caption">{{__('panel/partner.markup_6')}}</div></td>
            <td><div class="value">{{@$saller->settings->markup_6}}%</div></td>
        </div>
        <div class="col-12 col-sm part">
            <td><div class="caption">{{__('panel/partner.markup_9')}}</div></td>
            <td><div class="value">{{@$saller->settings->markup_9}}%</div></td>
        </div>
        <div class="col-12 col-sm part">
            <td><div class="caption">{{__('panel/partner.markup_12')}}</div></td>
            <td><div class="value">{{@$saller->settings->markup_12}}%</div></td>
        </div>
        <div class="col-12 col-sm part">
            <td><div class="caption">{{__('panel/partner.nds')}}</div></td>
            <td><div class="value">{{@$saller->settings->use_nds}}</div></td>
        </div>

        <div class="col-12 col-sm part">
            <td><div class="caption">{{__('panel/partner.discount_3')}}</div></td>
            <td><div class="value">
                    {{@$saller->settings->discount_3}}%
                </div></td>
        </div>
        <div class="col-12 col-sm part">
            <td><div class="caption">{{__('panel/partner.discount_6')}}</div></td>
            <td><div class="value">
                    {{@$saller->settings->discount_6}}%
                </div></td>
        </div>
        <div class="col-12 col-sm part">
            <td><div class="caption">{{__('panel/partner.discount_9')}}</div></td>
            <td><div class="value">
                    {{@$saller->settings->discount_9}}%
                </div></td>
        </div>
        <div class="col-12 col-sm part">
            <td><div class="caption">{{__('panel/partner.discount_12')}}</div></td>
            <td><div class="value">
                    {{@$saller->settings->discount_12}}%
                </div></td>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-md-6">
            <div class="lead">{{__('panel/partner.txt_law_info')}}</div>
            <table class="table">
                <tr>
                    <td><div class="caption">{{__('panel/partner.company_inn')}}</div></td>
                    <td><div class="value">{{$saller->inn}}</div></td>
                </tr>
                <tr>
                    <td><div class="caption">{{__('panel/partner.company_address')}}</div></td>
                    <td><div class="value">{{$saller->address}}</div></td>
                </tr>
                <tr>
                    <td><div class="caption">{{__('panel/partner.company_legal_address')}}</div></td>
                    <td><div class="value">{{$saller->legal_address}}</div></td>
                </tr>
                <tr>
                    <td><div class="caption">{{__('panel/partner.company_bank_name')}}</div></td>
                    <td><div class="value">{{$saller->bank_name}}</div></td>
                </tr>
                <tr>
                    <td><div class="caption">{{__('panel/partner.company_payment_account')}}</div></td>
                    <td><div class="value">{{$saller->payment_account}}</div></td>
                </tr>
            </table>
        </div><!-- /.col-12 col-md-6 -->

        <div class="col-12 col-md-6">
            <div class="lead">{{__('panel/partner.txt_contact_info')}}</div>
            <table class="table">
                <tr>
                    <td><div class="caption">{{__('panel/partner.fio')}}</div></td>
                    <td><div class="value">{{@$saller->user->fio}}</div></td>
                </tr>
                <tr>
                    <td><div class="caption">{{__('panel/partner.phone')}}</div></td>
                    <td><div class="value">{{@$saller->user->phone}}</div></td>
                </tr>
            </table>
        </div><!-- /.col-12 col-md-6 -->
    </div><!-- /.row -->




    <div id="confirm">
        {{--<div v-if="messages.length">
            <div class="small alert alert-success" v-for="message in messages">@{{ message }}</div>
        </div>
        <div v-if="errors.length">
            <div class="small alert alert-danger" v-for="error in errors">@{{ error }}</div>
        </div>--}}

        @can('modify', $saller)

            <div class="form-controls">
                <button type="button" v-if="status == 0" v-on:click="confirm()" class="btn btn-success">{{__('app.btn_confirm')}}</button>
                <button type="button" v-if="status == 1" v-on:click="block()" class="btn btn-outline-danger">{{__('app.btn_block')}}</button>
               {{-- <input v-model.trim="phone" name="phone" type="text" >--}}
                <button type="button" v-if="status == 1" v-on:click="resend()" class="btn btn-outline-success">{{__('panel/partner.btn_resend_password')}}</button>

                <a href="{{localeRoute('panel.partners.edit', $saller)}}" class="btn btn-primary ml-lg-auto">
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
                partner_id: '{{$saller->id}}',
                status: '{{$saller->status}}'
            },
            methods: {
                confirm: function () {
                    this.errors = [];
                    this.messages = [];

                    axios.post('/api/v1/employee/partners/action/confirm', {
                        api_token: this.api_token,
                        partner_id: this.partner_id
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
                        partner_id: this.partner_id
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
                        partner_id: this.partner_id
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
