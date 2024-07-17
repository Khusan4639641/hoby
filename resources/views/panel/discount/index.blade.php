@extends('templates.panel.app')

@section('title', __('panel/discount.header_discounts'))
@section('class', 'discount list')

@section('center-header-control')
    <a href="{{localeRoute('panel.discounts.create')}}" class="btn btn-primary btn-plus">
        {{__('panel/discount.btn_add')}}
    </a>
@endsection

@section('content')
    <div class="discounts">


        <ul class="nav nav-tabs" id="newsStatus" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link active" data-status="" id="all" data-toggle="tab" href="#" role="tab" aria-selected="true">{{__('panel/news.txt_all')}} ({{$counter["all"]}})</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" data-status="1" id="active" data-toggle="tab" href="#" role="tab" aria-selected="false">{{__('panel/news.status_1')}} ({{$counter["active"]}})</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" data-status="0" id="draft" data-toggle="tab" href="#" role="tab" aria-selected="false">{{__('panel/news.status_0')}} ({{$counter["draft"]}})</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" data-status="8" id="archive" data-toggle="tab" href="#" role="tab" aria-selected="false">{{__('panel/news.status_8')}} ({{$counter["archive"]}})</a>
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

        <table class="table discount-list">
            <thead>
            <tr>
                <th></th>
                <th>{{__('panel/discount.dates')}}</th>
                <th>{{__('panel/discount.title')}}</th>
                <th></th>
                <th></th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            </tbody>
        </table><!-- /.news-list -->

        <div class="modal" id="modalDeleteConfirm" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <input type="hidden" id="deleteID">
                    <div class="modal-header">
                        <h5 class="modal-title">{{__('panel/discount.header_delete_confirm')}}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>{{__('panel/discount.txt_delete_confirm')}}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">{{__('app.btn_cancel')}}</button>
                        <button onclick="destroy()" type="submit" class="btn btn-sm btn-danger">{{__('app.btn_delete')}}</button>
                    </div>
                </div><!-- /.modal-content -->
            </div>
        </div><!-- /.modal -->

        <div class="loading"><img src="{{asset('images/media/loader.svg')}}"></div>

        @include('panel.discount.parts.list')

    </div><!-- /.discounts -->

@endsection
