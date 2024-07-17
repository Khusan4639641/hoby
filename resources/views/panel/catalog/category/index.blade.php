@extends('templates.panel.app')

@section('title', __('panel/catalog.category.list'))
@section('center-header-control')
    <a href="{{localeRoute('panel.catalog.categories.create')}}" class="btn btn-primary btn-plus">
        {{__('panel/catalog.category.create')}}
    </a>
@endsection
@section('class', 'catalog category list')

@section('center-header-prefix')
    <a class="link-back" href="{{localeRoute('panel.index')}}"><img src="{{asset('images/icons/icon_arrow_green.svg')}}"></a>
@endsection

@section('content')

    <div class="dataTablesSearch" id="dataTablesSearch">
        <div class="input-group">
            <input type="text" class="form-control" placeholder="{{__('panel/catalog.category.title')}}">
            <div class="input-group-append">
                <button class="btn btn-success btn-search" type="button">{{__('app.btn_find')}}</button>
            </div>
        </div>
    </div>

    <table class="table table-borderless category-list">
        <thead>
        <tr>
            <th>{{__('panel/catalog.category.title')}}</th>
            <th>{{__('panel/catalog.category.preview_text')}}</th>
            <th></th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        </tbody>
    </table>


    <div class="modal" id="modalDeleteConfirm" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <input type="hidden" id="deleteID">
                <div class="modal-header">
                    <h5 class="modal-title">{{__('app.header_delete_confirm')}}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>{{__('panel/catalog.category.txt_delete_confirm')}}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">{{__('app.btn_cancel')}}</button>
                    <button onclick="destroy()" type="submit" class="btn btn-sm btn-danger">{{__('app.btn_delete')}}</button>
                </div>
            </div><!-- /.modal-content -->
        </div>
    </div><!-- /.modal -->

    <div class="loading"><img src="{{asset('images/media/loader.svg')}}"></div>

    @include('panel.catalog.parts.list')

@endsection
