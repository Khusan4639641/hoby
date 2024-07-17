@extends('templates.panel.app')
@section('title', __('Списания не соответствующие пополнению'))
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
                <th class="sorting_disabled" rowspan="1" colspan="1" style="width: 343px;">{{ __('Пользователь') }}</th>
                <th class="sorting_disabled" rowspan="1" colspan="1" style="width: 343px;">{{ __('Покупатель') }}</th>
                <th class="sorting_disabled text-right" rowspan="1" colspan="1"
                    style="width: 343px;">{{ __('Личный счёт') }}</th>
                <th class="sorting_disabled text-right" rowspan="1" colspan="1"
                    style="width: 343px;">{{ __('Разница') }}</th>
                <th class="sorting_disabled text-right" rowspan="1" colspan="1"
                    style="width: 342px;">{{ __('Кол. актив. договоров') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($users as $user)
                <tr role="row" class="odd">
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->user }}</td>
                    <td><a target="_blank" href="{{ localeRoute( 'panel.monitoring.user', $user->id ) }}">{{ __('Посмотреть') }}</a></td>
                    <td><a target="_blank" href="{{ localeRoute( 'panel.buyers.show', $user->id ) }}">{{ __('Посмотреть') }}</a></td>
                    <td class="text-right">{{ number_format($user->account, 2, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format($user->different, 2, ',', ' ') }}</td>
                    <td class="text-right">{{ $user->count }}</td>
                </tr>
            @endforeach

            </tbody>
        </table>

        {{ $users->links() }}

    </div>

    {{--    @include('panel.monitoring.parts.list')--}}

@endsection
