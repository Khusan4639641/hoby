@extends('templates.billing.app')

@section('title', __('billing/menu.buyers'))
@section('class', 'buyer list')

@section('center-header-control')
    <a href="{{localeRoute('billing.buyers.create')}}" class="btn btn-primary btn-plus">
        {{__('billing/buyer.btn_create')}}
    </a>
@endsection


@section('center-header-prefix')
    <a class="link-back" href="{{localeRoute('billing.index')}}"><img src="{{asset('images/icons/icon_arrow_orange.svg')}}"></a>
@endsection

@section('content')


    <ul class="nav nav-tabs" id="buyerStatus" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link active" id="all" data-status="" data-toggle="tab" href="#" role="tab" aria-selected="true">{{__('billing/buyer.txt_all')}} ({{$counter["all"]}})</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="verified" data-status="4" data-toggle="tab" href="#" role="tab" aria-selected="false">{{__('billing/buyer.status_4')}} ({{$counter["verified"]}})</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="verification" data-status="2" data-toggle="tab" href="#" role="tab" aria-selected="false">{{__('billing/buyer.status_3')}} ({{$counter["verification"]}})</a>
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


    <div class="buyers">
        <div class="row">
            <div class="col-12">
                <table class="table buyers-list">
                    <thead>
                    <tr>
                        <th></th>
                        <th>{{__('panel/buyer.buyer_id')}}</th>
                        <th>{{__('panel/buyer.phone')}}</th>
                        <th>{{__('panel/buyer.buyer_fio')}}</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="loading"><img src="{{asset('images/media/loader.svg')}}"></div>

    @include('billing.buyer.parts.list')

@endsection
