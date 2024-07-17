@extends('templates.panel.app')

@section('title', __('panel/partner.header'))
@section('class', 'partners list')


@section('center-header-control')
    <a href="{{localeRoute('panel.sallers.create')}}" class="btn btn-primary btn-plus">
        {{__('panel/sallers.header_create')}}
    </a>
@endsection

@section('center-header-prefix')
    <a class="link-back" href="{{localeRoute('panel.index')}}"><img src="{{asset('images/icons/icon_arrow_green.svg')}}"></a>
@endsection


@section('content')
<style>
    .nav-link:not(.active) a {
        color: #787878;
    }
    a {
        color: var(--orange);
        outline: none;
    }
    a:hover, a:focus, a:visited {
        color: #4807b0;
        outline: none;
    }
    .first.paginate_button, .last.paginate_button {
        display: none !important;
    }
    .previous.paginate_button, .next.paginate_button {
        height: 40px;
        background: #F6F6F6;
        border-radius: 8px;
        border: 1px solid transparent;
        transition: 0.4s;
        font-size: 16px;
        display: inline-flex !important;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        border-radius: 8px !important;
    }
    .previous.paginate_button{
        background-position: left 4px center !important;
        padding: 0.15rem 1rem 0.15rem 2rem !important;
        margin-left: 0 !important;
    }
    .next.paginate_button {
        background-position: right 4px center !important;
        padding: 0.15rem 2rem 0.15rem 1rem !important;
    }
    .previous.paginate_button:hover, .next.paginate_button:hover {
        border-color: transparent !important;
        background-color: var(--peach) !important;
    }
    .previous.paginate_button:active, .next.paginate_button:active {
        border-color: transparent !important;
        background-color: #6610f530 !important;
        box-shadow: none !important;
    }

    .paginate_button.disabled{
        filter: grayscale(1);
        opacity: .5;
        cursor: not-allowed !important;
    }
    input.paginate_input {
        max-width: 100px;
        padding: 8px 12px;
        margin: 0 8px;
        height: 40px;
        display: inline-flex;
        align-items: center;
        font-size: 16px;
        line-height: 24px;
        letter-spacing: 0.01em;
        color: #1e1e1e;
        box-sizing: border-box;
        background: #F6F6F6;
        border-radius: 8px;
        border: 1px solid transparent;
        transition: 0.4s;
    }
    input.paginate_input:hover {
        border: 1px solid #d1d1d1;
    }
    input.paginate_input:focus {
        border: 1px solid var(--orange);
        outline: none;
        color: #1e1e1e;
        box-shadow: none;
    }
</style>
    {{--<ul class="nav nav-tabs" id="partnerStatus" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link active" id="all" data-status="" data-toggle="tab" href="#" role="tab" aria-selected="true">{{__('panel/partner.all')}} ({{$counter["all"]}})</a>
        </li>
        <li class="nav-item"  role="presentation">
            <a class="nav-link" id="verification" data-status="0" data-toggle="tab" href="#" role="tab" aria-selected="false">{{__('panel/partner.tab_status_0')}} ({{$counter["verification"]}})</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="verified" data-status="1" data-toggle="tab" href="#" role="tab" aria-selected="false">{{__('panel/partner.tab_status_1')}} ({{$counter["verified"]}})</a>
        </li>
    </ul> --}}

    <div class="dataTablesSearch" id="dataTablesSearch">
        <div class="row">
            <div class="col-md-3">
                <div class="input-group">
                    <input id="seller_id" type="text" class="form-control" placeholder="{{ __('panel/buyer.search_id') }}">
                    <div class="input-group-append">
                        <button class="btn btn-success btn-search" type="button">{{__('app.btn_find')}}</button>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="input-group">
                    <input id="seller_fio" type="text" class="form-control" placeholder="{{ __('cabinet/profile.fio') }}">
                    <div class="input-group-append">
                        <button class="btn btn-success btn-search" type="button">{{__('app.btn_find')}}</button>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="input-group">
                    <input id="brand_name" type="text" class="form-control" placeholder="brand">
                    <div class="input-group-append">
                        <button class="btn btn-success btn-search" type="button">{{__('app.btn_find')}}</button>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="input-group">
                    <input id="seller_phone" type="text" class="form-control" placeholder="phone...">
                    <div class="input-group-append">
                        <button class="btn btn-success btn-search" type="button">{{__('app.btn_find')}}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <table class="table partners-list mt-4">
        <thead>
        <tr>
            {{--<th></th>
            <th></th>--}}
            <th>{{__('panel/partner.title')}}</th>
            <th></th>
            <th>{{__('panel/partner.company_brand')}}</th>
            <th>{{__('panel/partner.phone')}}</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        </tbody>
    </table>

    <div class="loading"><img src="{{asset('images/media/loader.svg')}}"></div>

    @include('panel.saller.parts.list')


@endsection
