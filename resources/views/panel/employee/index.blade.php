@extends('templates.panel.app')
@section('title', __('panel/employee.header_employees'))
@section('class', 'employee list')

@section('center-header-control')
    <a href="{{localeRoute('panel.employees.create')}}" class="btn btn-primary btn-plus">
        {{__('panel/employee.btn_add')}}
    </a>
@endsection


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
    <ul class="nav nav-tabs" id="employeeStatus" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link active" data-status="" id="all" data-toggle="tab" href="#" role="tab" aria-selected="true">{{__('panel/employee.txt_all')}} ({{$counter["all"]}})</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" data-status="1" id="active" data-toggle="tab" href="#" role="tab" aria-selected="false">{{__('panel/employee.status_1')}} ({{$counter["active"]}})</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" data-status="0" id="inactive" data-toggle="tab" href="#" role="tab" aria-selected="false">{{__('panel/employee.status_0')}} ({{$counter["inactive"]}})</a>
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


    <table class="table employee-list">
        <thead>
            <tr>
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
                    <h5 class="modal-title">{{__('panel/employee.header_delete_confirm')}}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <p>{{__('panel/employee.txt_delete_confirm')}}</p>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{__('app.btn_cancel')}}</button>
                    <button type="submit" onclick="destroy()" class="btn btn-primary">{{__('app.btn_delete')}}</button>
                </div>

            </div>
        </div>
    </div>

    <div class="loading"><img src="{{asset('images/media/loader.svg')}}"></div>

    @include('panel.employee.parts.list')

@endsection
