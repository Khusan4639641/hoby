@extends('templates.panel.app')

@section('title', __('panel/pay-sys.header'))
@section('class', 'pay-system list')


@section('center-header-control')
    <a href="{{localeRoute('panel.pay-system.create')}}" class="btn btn-primary btn-plus">
        {{__('panel/pay-sys.header_create')}}
    </a>
@endsection

@section('center-header-prefix')
    <a class="link-back" href="{{localeRoute('panel.index')}}"><img src="{{asset('images/icons/icon_arrow_green.svg')}}"></a>
@endsection


@section('content')

    {{--<ul class="nav nav-tabs" id="partnerStatus" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link active" id="all" data-status="" data-toggle="tab" href="#" role="tab" aria-selected="true">{{__('panel/pay-sys.all')}} ({{$counter["all"]}})</a>
        </li>
        <li class="nav-item"  role="presentation">
            <a class="nav-link" id="verification" data-status="0" data-toggle="tab" href="#" role="tab" aria-selected="false">{{__('panel/pay-sys.tab_status_0')}} ({{$counter["verification"]}})</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="verified" data-status="1" data-toggle="tab" href="#" role="tab" aria-selected="false">{{__('panel/pay-sys.tab_status_1')}} ({{$counter["verified"]}})</a>
        </li>
    </ul>--}}

   {{-- <div class="dataTablesSearch" id="dataTablesSearch">
        <div class="input-group">
            <input type="text" class="form-control" >
            <div class="input-group-append">
                <button class="btn btn-success btn-search" type="button">{{__('app.btn_find')}}</button>
            </div>
        </div>
    </div>--}}

    <table class="table paysystem-list mt-4">
        <thead>
        <tr>
            <th>{{__('panel/pay-sys.id')}}</th>
            <th>{{__('panel/pay-sys.title')}}</th>
            <th>{{__('panel/pay-sys.url')}}</th>
            <th>{{__('panel/pay-sys.status')}}</th>

        </tr>
        </thead>
        <tbody>
        </tbody>
    </table>

    {{--<div class="loading"><img src="{{asset('images/media/loader.svg')}}"></div>--}}

    @include('panel.pay-system.parts.list')


@endsection
