@extends('templates.cabinet.app')

@section('h1', __('cabinet/payment.header_payments'))
@section('title', __('cabinet/payment.header_payments'))
@section('center-header-prefix')
    <a class="link-back" href="{{localeRoute('cabinet.index')}}"><img src="{{asset('images/icons/icon_arrow_green.svg')}}"></a>
@endsection

@section('class', 'payments list')

@section('center-header-control')
    <div class="total-payment">
        <span class="text">{{__('cabinet/payment.sum_payments')}}</span> - {{__('app.month_' . date('m'))}}
        <div class="sum">{{$total}}</div>
    </div>
@endsection

@section('content')
    <ul class="nav nav-tabs" id="paymentStatus" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link active" data-status="#current" id="current" data-toggle="tab" href="#" role="tab" aria-selected="true">{{__('cabinet/payment.tab_status_0')}} ({{$counter[0]}})</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" data-status="#expired" id="expired" data-toggle="tab" href="#" role="tab" aria-selected="false">{{__('cabinet/payment.tab_status_2')}} ({{$counter[2]}})</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" data-status="#completed" id="completed" data-toggle="tab" href="#" role="tab" aria-selected="false">{{__('cabinet/payment.tab_status_1')}} ({{$counter[1]}})</a>
        </li>
    </ul>

    <table class="table payments-list mt-4">
        <thead>
        <tr>
            <th>{{__('cabinet/payment.date')}}</th>
            <th>{{__('cabinet/payment.order')}}</th>
            <th>{{__('cabinet/payment.contract')}}</th>
            <th>{{__('cabinet/payment.installment')}}</th>
            <th>{{__('cabinet/payment.payment_sum')}}</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        </tbody>
    </table>

    <div class="loading"><img src="{{asset('images/media/loader.svg')}}"></div>
    @include('cabinet.payment.parts.list')
@endsection
