@extends('templates.panel.app')

@section('title', __('panel/contract.header_contracts'))
@section('class', 'contracts list')

@section('content')

    <ul class="nav nav-tabs" id="contractRecovery" role="tablist">

        <li class="nav-item" role="presentation">
            <a class="nav-link" id="for-send" data-status="0" data-action="0" data-toggle="tab" href="#" role="tab"
               aria-selected="true">{{__('panel/contract.tab_recover_30')}} ({{$counter["all_30"]}})</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="for-send" data-status="0" data-action="3" data-toggle="tab" href="#" role="tab"
               aria-selected="true">{{__('panel/contract.tab_recover_45')}} ({{$counter["all_45"]}})</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="send-letter" data-status="1" data-action="1" data-toggle="tab" href="#" role="tab"
               aria-selected="true">{{__('panel/contract.tab_recover_60')}} ({{$counter["all_60"]}})</a>
        </li>

    </ul>

    <div class="dataTablesSearch" id="dataTablesSearch">
        <div class="input-group">
            <input type="text" class="form-control" id="contract_id">
            <div class="input-group-append">
                <button class="btn btn-success btn-search" type="button">{{__('app.btn_find')}}</button>
            </div>
        </div>
    </div>

    <table class="table contract-list">
        <thead>
            <tr>
                <th>{{__('panel/contract.date')}}</th>
                <th>{{__('panel/contract.contract_id')}}</th>
                <th>{{__('panel/contract.partner')}}</th>
                <th>{{__('panel/contract.client')}}</th>
                <th>{{__('cabinet/profile.gender_title')}}</th>
                <th>{{__('cabinet/profile.birthday')}}</th>
                <th>{{__('panel/contract.phone')}}</th>
                <th>{{__('panel/contract.sum')}}</th>
                <th>{{__('panel/contract.paid_off')}}</th>
                <th>{{__('panel/contract.debt')}}</th>
                <th>{{__('panel/contract.day')}}</th>
                <th>{{__('panel/contract.status')}}</th>

{{--                <th>Status</th>--}}
{{--                <th>{{__('panel/contract.insurance')}}</th>--}}
{{--                <th>{{__('panel/contract.law')}}</th>--}}
            </tr>
        </thead>
        <tbody>
        </tbody>
        {!! '<b>Всего сумма просрочки:</b> '. $debts !!}

    </table><!-- /.contract-list -->


    <div class="loading"><img src="{{ asset('images/media/loader.svg') }}"></div>

    @include('panel.recovery.parts.list')

@endsection
