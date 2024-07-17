@extends('templates.billing.app')

@section('title', __('billing/catalog.header_catalog'))
@section('center-header-control')
    <a href="{{localeRoute('billing.catalog.products.category-export')}}" class="btn btn-orange">
        {{__('billing/catalog.category_export')}}
    </a>
    <a href="{{localeRoute('billing.catalog.products.import')}}" class="btn btn-orange">
        {{__('billing/catalog.product_import')}}
    </a>
    <a href="{{localeRoute('billing.catalog.products.export')}}" class="btn btn-orange">
        {{__('billing/catalog.product_export')}}
    </a>
    <a href="{{localeRoute('billing.catalog.products.create')}}" class="btn btn-orange">
        {{__('billing/catalog.product_create')}}
    </a>
@endsection
@section('class', 'catalog list')

{{--@section('center-header-prefix')--}}
{{--    <a class="link-back" href="{{localeRoute('billing.index')}}"><img src="{{asset('images/icons/icon_arrow_green.svg')}}"></a>--}}
{{--@endsection--}}

@section('content')

    <div class="dataTablesSearch" id="dataTablesSearch">
        <div class="input-modified">
            <input type="text" class="form-control" placeholder="{{__('billing/catalog.title')}}">
            <div class="input-group-append">
                <button class="btn bg-transparent btn-search" type="button"></button>
            </div>
        </div>
    </div>

    <table class="table table-borderless product-list mt-md-5">
        <thead>
        <tr>
            {{--<th>{{__('billing/catalog.photo')}}</th>--}}
            <th>{{__('billing/catalog.vendor_code')}}</th>
            <th>{{__('billing/catalog.title')}}</th>
            <th>{{__('billing/catalog.quantity')}}</th>
            <th>{{__('billing/catalog.price')}}</th>
            {{--<th>{{__('billing/catalog.price_discount')}}</th>--}}
            <th></th>
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
                <div class="p-3 text-center position-relative">
                    <p class="modal-title font-weight-bold font-size-32">
                        {{__('app.header_delete_confirm')}}
                    </p>
                    <button type="button" class="close close-button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body-modified">
                    <p>{{__('billing/catalog.txt_delete_confirm')}}</p>
                </div>
                <div class="modal-footer modified">
                    <button type="button"
                            class="btn btn-peach text-orange"
                            data-dismiss="modal">
                        {{__('app.btn_cancel')}}
                    </button>
                    <button onclick="destroy()"
                            type="submit"
                            class="btn btn-red">
                        {{__('app.btn_delete')}}
                    </button>
                </div>
            </div><!-- /.modal-content -->
        </div>
    </div><!-- /.modal -->

    <div class="loading"><img src="{{asset('images/media/loader.svg')}}"></div>

    @include('billing.catalog.parts.list')

@endsection
