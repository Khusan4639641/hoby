@extends('templates.panel.app')
@section('title', __('monitoring.title'))
@section('class', 'accounts list')

@section('content')

    <div class="dataTablesSearch" id="dataTablesSearch">
        <div class="input-group">
            <input type="text" class="form-control">
            <div class="input-group-append">
                <button class="btn btn-success btn-search" type="button">{{__('app.btn_find')}}</button>
            </div>
        </div>
    </div>

    <b>{{ __('Количество записей') }}:</b> {{ $count }}

    <div id="DataTables_Table_0_wrapper" class="dataTables_wrapper no-footer">
        <table class="table accounts-list dataTable no-footer" id="DataTables_Table_0" role="grid"
               style="width: 1795px;">
            <thead>
            <tr role="row">
                <th class="sorting_disabled" rowspan="1" colspan="1" style="width: 343px;">{{ __('ID') }}</th>
                <th class="sorting_disabled" rowspan="1" colspan="1" style="width: 343px;">{{ __('Ф.И.О.') }}</th>
                <th class="sorting_disabled" rowspan="1" colspan="1" style="width: 343px;"></th>
                <th class="sorting_disabled" rowspan="1" colspan="1" style="width: 343px;">{{ __('Депозит по контракту') }}</th>
                <th class="sorting_disabled" rowspan="1" colspan="1" style="width: 343px;">{{ __('Депозит по приходу/списанию') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($deposits as $deposit)
                <tr role="row" class="odd">
                    <td>{{ $deposit->contract_id }}</td>
                    <td>{{ $deposit->user_name }}</td>
                    <td><a target="_blank" href="{{ localeRoute('panel.monitoring.user', $deposit->user_id) }}">{{ __('Посмотреть') }}</a></td>
                    <td class="text-right">{{ number_format($deposit->contract_deposit, 2, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format($deposit->payments_deposit, 2, ',', ' ') }}</td>
                </tr>
            @endforeach

            </tbody>
        </table>

        {{ $deposits->links() }}

    </div>

    {{--    @include('panel.monitoring.parts.list')--}}

@endsection
