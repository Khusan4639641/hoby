@extends('templates.panel.app')

@section('title', __('panel/contract.header_contracts'))
@section('class', 'contracts list')

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
    <ul class="nav nav-tabs" id="contractStatus" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link active" id="all" data-status="" data-toggle="tab" href="#" role="tab" aria-selected="true">{{__('panel/contract.all')}}</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="active" data-status="1" data-toggle="tab" href="#" role="tab" aria-selected="false">{{__('panel/contract.tab_status_1')}}</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="debt" data-status="4" data-toggle="tab" href="#" role="tab" aria-selected="false">{{__('panel/contract.tab_status_4')}}</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="cancel" data-status="5" data-toggle="tab" href="#" role="tab" aria-selected="false">{{__('panel/contract.tab_status_5')}}</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="cancel_act_verify" data-cancel_act_status="[1,2]" data-toggle="tab" href="#" role="tab" aria-selected="false">{{__('panel/contract.tab_cancel_act_verify')}}</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="complete" data-status="9" data-toggle="tab" href="#" role="tab" aria-selected="false">{{__('panel/contract.tab_status_9')}}</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="act_verify" data-act_status="1" data-status="[1, 3, 4]" data-toggle="tab" href="#" role="tab" aria-selected="false">{{__('panel/contract.tab_act_verify')}}</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="act_need" data-act_status="[0, 2]" data-status="[1, 3, 4]" data-toggle="tab" href="#" role="tab" aria-selected="false">{{__('panel/contract.tab_act_need')}}</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="cancel_act_verify" data-cancel_act_status="[1,3]" data-toggle="tab" href="#" role="tab" aria-selected="false">{{__('panel/contract.tab_cancel_act_verify')}}</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="imei_verify" data-imei_status="3" data-toggle="tab" href="#" role="tab" aria-selected="false">{{__('panel/contract.tab_imei_verify')}}</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="imei_need" data-imei_status="2" data-toggle="tab" href="#" role="tab" aria-selected="false">{{__('panel/contract.tab_imei_need')}}</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="check_client_photo" data-client_status="3" data-toggle="tab" href="#" role="tab" aria-selected="false">{{__('panel/contract.check_client_photo')}}</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="upload_client_photo" data-client_status="0" data-status__not="0" data-toggle="tab" href="#" role="tab" aria-selected="false">{{__('panel/contract.upload_client_photo')}}</a>
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
                <th>{{__('panel/contract.insurance')}}</th>
                <th>{{__('panel/contract.law')}}</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
        {!! '<b>Всего сумма просрочки:</b> '. $debts !!}

    </table><!-- /.contract-list -->


    <div class="loading"><img src="{{asset('images/media/loader.svg')}}"></div>

    @include('panel.contract.parts.list')

@endsection
