@extends('templates.billing.app')

@section('title', __('billing/affiliate.create_header'))
@section('class', 'affiliates show')

@section('center-header-prefix')
    <a class="link-back" href="{{localeRoute('billing.affiliates.index')}}"><img src="{{asset('images/icons/icon_arrow_orange.svg')}}"></a>
@endsection

@section('content')


    <div class="organization">
        @if($affiliate->logo != null)
            <div class="preview" style="background-image: url({{$affiliate->logo->preview}})"></div>
        @else
            <div class="preview dummy"></div>
        @endif

        <div class="info">
            <div class="id">ID {{$affiliate->id}}</div>
            <div class="name">{{$affiliate->name}}</div>
            <div class="description">{!! $affiliate->short_description !!}</div>
        </div>
    </div><!-- /.organization -->


    <div class="row params">
        <div class="col-12 col-sm part">
            <td><div class="caption">{{__('panel/partner.markup_3')}}</div></td>
            <td><div class="value">{{$affiliate->settings->markup_3}}%</div></td>
        </div>
        <div class="col-12 col-sm part">
            <td><div class="caption">{{__('panel/partner.markup_6')}}</div></td>
            <td><div class="value">{{$affiliate->settings->markup_6}}%</div></td>
        </div>
        <div class="col-12 col-sm part">
            <td><div class="caption">{{__('panel/partner.markup_9')}}</div></td>
            <td><div class="value">{{$affiliate->settings->markup_9}}%</div></td>
        </div>
        <div class="col-12 col-sm part">
            <td><div class="caption">{{__('panel/partner.markup_12')}}</div></td>
            <td><div class="value">{{$affiliate->settings->markup_12}}%</div></td>
        </div>
        <div class="col-12 col-sm part">
            <td><div class="caption">{{__('panel/partner.nds')}}</div></td>
            <td><div class="value">{{$affiliate->settings->use_nds}}</div></td>
        </div>
    </div><!-- /.params -->

    <div class="row">
        <div class="col-12 col-md-6">
            <div class="lead">{{__('billing/affiliate.txt_law_info')}}</div>
            <table class="table">
                <tr>
                    <td><div class="caption">{{__('billing/affiliate.company_inn')}}</div></td>
                    <td><div class="value">{{$affiliate->inn}}</div></td>
                </tr>
                <tr>
                    <td><div class="caption">{{__('billing/affiliate.company_address')}}</div></td>
                    <td><div class="value">{{$affiliate->address}}</div></td>
                </tr>
                <tr>
                    <td><div class="caption">{{__('billing/affiliate.company_legal_address')}}</div></td>
                    <td><div class="value">{{$affiliate->legal_address}}</div></td>
                </tr>
                <tr>
                    <td><div class="caption">{{__('billing/affiliate.company_bank_name')}}</div></td>
                    <td><div class="value">{{$affiliate->bank_name}}</div></td>
                </tr>
                <tr>
                    <td><div class="caption">{{__('billing/affiliate.company_payment_account')}}</div></td>
                    <td><div class="value">{{$affiliate->payment_account}}</div></td>
                </tr>
                <tr>
                    <td><div class="caption">{{__('billing/affiliate.company_phone')}}</div></td>
                    <td><div class="value">{{$affiliate->phone}}</div></td>
                </tr>
                <tr>
                    <td><div class="caption">{{__('billing/affiliate.company_website')}}</div></td>
                    <td><div class="value">{{$affiliate->affiliate}}</div></td>
                </tr>
            </table>
        </div><!-- /.col-12 col-md-6 -->

        <div class="col-12 col-md-6">
            <div class="lead">{{__('billing/affiliate.txt_contact_info')}}</div>
            <table class="table">
                <tr>
                    <td><div class="caption">{{__('billing/affiliate.fio')}}</div></td>
                    <td><div class="value">{{$affiliate->user->fio}}</div></td>
                </tr>
                <tr>
                    <td><div class="caption">{{__('billing/affiliate.phone')}}</div></td>
                    <td><div class="value">{{$affiliate->user->phone}}</div></td>
                </tr>
            </table>
        </div><!-- /.col-12 col-md-6 -->
    </div><!-- /.row -->

    @can('modify', $affiliate)
        @if($affiliate->status == "0")
            <div class="form-controls">

                <a href="{{localeRoute('billing.affiliates.edit', $affiliate)}}" class="btn btn-primary ml-lg-auto">
                    {{__('app.btn_edit')}}
                </a>

            </div>
        @endif
    @endcan



@endsection
