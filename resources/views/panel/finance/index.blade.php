@extends('templates.panel.app')

@section('title', __('panel/finance.header'))
@section('class', 'finances list')

@section('center-header-control')
    <div class="dataTablesSearch" id="dataTablesSearch">
        <div class="input-wrapper search-text">
            <div class="input-grouped">
                <input type="text" class="form-control" v-model="search.title">
                <div class="input-icon" v-on:click="updateList()"><img src="/images/icons/icon_search_orange.svg" alt="Search"></div>
            </div>
        </div>
        <div class="input-wrapper">
            <div class="icon-calendar"><img src="/images/icons/icon_calendar_green.svg"></div>
            <div class="input-grouped date">
                <date-picker v-model="search.date" value-type="format" type="date" range editable="false"
                             format="DD.MM.YYYY" v-on:change="updateList()"></date-picker>
            </div>
        </div>

    </div>
@endsection

@section('content')


    <table class="table finances-list">
        <thead>
        <tr>
            <th>{{__('panel/finance.order_date')}}</th>
            <th>{{__('panel/finance.order_number')}}</th>
            <th>{{__('panel/finance.contract_number')}}</th>
            <th>{{__('panel/finance.partner')}}</th>
            <th>{{__('panel/finance.buyer')}}</th>
            <th>{{__('panel/finance.entry_sum')}}</th>
            <th>{{__('panel/finance.offs_sum')}}</th>
            <th>{{__('panel/finance.delinquency_sum')}}</th>
            <th>{{__('panel/finance.partner_credit')}}</th>
            <th>{{__('panel/finance.partner_debt')}}</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
    <div class="loading"><img src="{{asset('images/media/loader.svg')}}"></div>
@include('panel.finance.parts.list')

@endsection
