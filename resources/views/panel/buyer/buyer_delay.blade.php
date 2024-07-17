@extends('templates.panel.app')

@section('title', __('panel/buyer.buyer_delay'))
@section('class', 'buyers list')

@section('content')
    <style>

        .hide{
            display: none;
        }
        .show{
            cursor: pointer;
        }

        .icon.open{
            -webkit-transform: rotate(90deg);
            -moz-transform: rotate(90deg);
            -o-transform: rotate(90deg);
            -ms-transform: rotate(90deg);
            transform: rotate(90deg);
        }
        .icon{
            width: 24px;
            height: 24px;
            transform: rotate(0);
        }
        .dropdown-container{
            overflow-y: auto;
            min-height:250px;
            max-height: 500px;"
        }

        td.dt-control {
            background: url('{{ asset('assets/icons/chevron-right.svg') }}') no-repeat center center;
            cursor: pointer;
        }
        tr.shown td.dt-control {
            background: url('{{ asset('assets/icons/chevron-down.svg') }}') no-repeat center center;
        }

    </style>

    <div id="error-alert" class="alert alert-danger" style="display: none"></div>

    <div class="dataTablesSearch" id="dataTablesSearch">
        <div class="input-group">
            <input type="search" class="form-control search-id" placeholder="{{__('panel/buyer.search_id')}}">
            <input type="search" class="form-control search-phone" placeholder="{{__('panel/buyer.search_phone')}}">
            <div class="input-group-append">
                <button class="btn btn-success btn-search" type="button">{{__('app.btn_find')}}</button>
            </div>
        </div>
    </div>
    <table class="table buyers-list">
        <thead>
            <tr >
                <th>Expand</th>
                <th>{{__('app.status')}}</th>
                <th>{{__('panel/buyer.buyer_id')}}</th>
                <th>{{__('panel/buyer.buyer_fio')}}</th>
                <th>{{__('panel/buyer.phone')}}</th>
                <th>{{__('panel/buyer.debt')}}</th>
                <th></th>
            </tr>

        </thead>
        <tbody>
        </tbody>
    </table>
    <hr>
    <div>
        <button type="submit" onclick="addCardsHumo()" class="btn btn-primary">{{__('app.btn_add_cards_humo')}}</button>
    </div>
    {{--<div >
        <button type="submit" onclick="addCardsHumo()" class="btn btn-primary">{{__('app.btn_add_cards_humo')}}</button>
    </div>--}}
{{--    <div class="loading"><img src="{{asset('images/media/loader.svg')}}"></div>--}}
    @include('panel.buyer.parts.list_delay')

@endsection
