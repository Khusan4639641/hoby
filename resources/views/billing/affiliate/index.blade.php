@extends('templates.billing.app')

@section('title', __('billing/affiliate.header'))
@section('class', 'affiliates list')

@section('center-header-control')
    <a href="{{localeRoute('billing.affiliates.create')}}" class="btn btn-primary btn-plus">
        {{__('billing/affiliate.btn_create_affiliate')}}
    </a>
@endsection

@section('center-header-prefix')
    <a class="link-back" href="{{localeRoute('billing.index')}}"><img src="{{asset('images/icons/icon_arrow_orange.svg')}}"></a>
@endsection

@section('content')

    <ul class="nav nav-tabs" id="affiliateStatus" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link active" id="all" data-status="" data-toggle="tab" href="#" role="tab" aria-selected="true">{{__('panel/partner.all')}} ({{$counter["all"]}})</a>
        </li>
        <li class="nav-item"  role="presentation">
            <a class="nav-link" id="verification" data-status="0" data-toggle="tab" href="#" role="tab" aria-selected="false">{{__('panel/partner.tab_status_0')}} ({{$counter["verification"]}})</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="verified" data-status="1" data-toggle="tab" href="#" role="tab" aria-selected="false">{{__('panel/partner.tab_status_1')}} ({{$counter["verified"]}})</a>
        </li>
    </ul>

    <div class="dataTablesSearch" id="dataTablesSearch">
        <div class="input-group">
            <input type="text" class="form-control" >
            <div class="input-group-append">
                <button class="btn btn-success btn-search" type="button">{{__('app.btn_find')}}</button>
            </div>
        </div>
    </div>

    <table class="table affiliates-list mt-4">
        <thead>
        <tr>
            <th></th>
            <th></th>
            <th></th>
            <th>{{__('billing/affiliate.title')}}</th>
            <th>{{__('billing/affiliate.fio')}}</th>
            <th>{{__('billing/affiliate.phone')}}</th>
            <th></th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        </tbody>
    </table>

    <div class="loading"><img src="{{asset('images/media/loader.svg')}}"></div>

    @include('billing.affiliate.parts.list')


@endsection
