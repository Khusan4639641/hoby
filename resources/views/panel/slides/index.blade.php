@extends('templates.panel.app')

@section('title', __('panel/slide.header_slides'))
@section('class', 'slides list')

@section('center-header-control')
    <a href="{{localeRoute('panel.slides.create', $slider->id)}}" class="btn btn-primary btn-plus">
        {{__('panel/slide.btn_add')}}
    </a>
@endsection


@section('content')
    <div class="dataTablesSearch" id="dataTablesSearch">
        <div class="input-group">
            <input type="text" class="form-control" >
            <div class="input-group-append">
                <button class="btn btn-success btn-search" type="button">{{__('app.btn_find')}}</button>
            </div>
        </div>
    </div>

    <table class="table slides-list">
        <thead>
        <tr>
            <th></th>
            <th></th>
            <th></th>
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
                    <h5 class="modal-title">{{__('panel/slide.header_delete_confirm')}}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>{{__('panel/slide.txt_delete_confirm')}}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">{{__('app.btn_cancel')}}</button>
                    <button onclick="destroy()" type="submit" class="btn btn-sm btn-danger">{{__('app.btn_delete')}}</button>
                </div>
            </div><!-- /.modal-content -->
        </div>
    </div><!-- /.modal -->

    <div class="loading"><img src="{{asset('images/media/loader.svg')}}"></div>

    @include('panel.slides.parts.list')
@endsection
