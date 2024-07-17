@extends('templates.panel.app')

@section('title', "Контракты по KATM отчётам")

@section('content')


    <div class="col-lg-12">
        <table class="table table-striped no-footer">
            <tbody>
            <tr>
                <th class="text-center">ID</th>
                <th class="text-center">Статус</th>
                <th class="text-center">Дата создания</th>
                <th class="text-center">Дата подтверждения</th>
                <th class="text-center">Дата отмены</th>
                <th class="text-center">Ссылка на контракт</th>
                <th class="text-center">Ссылка на клиента</th>
                <th class="text-center">Количество отчётов</th>
                <th class="text-center">Ссылка на отчёты</th>
            </tr>
            @foreach($contracts as $contract)
                <tr>
                    <td class="text-center">{{ $contract['id'] }}</td>
                    <td class="text-center">{{ $contract['status'] }}</td>
                    <td class="text-center">{{ $contract['created_at'] }}</td>
                    <td class="text-center">{{ $contract['confirmed_at'] }}</td>
                    <td class="text-center">{{ $contract['canceled_at'] }}</td>
                    <td class="text-center">
                        <a target="_blank"
                           href="{{ localeRoute( 'panel.contracts.show', $contract['id'] ) }}">{{ __('Посмотреть') }}</a>
                    </td>
                    <td class="text-center">
                        <a target="_blank"
                           href="{{ localeRoute( 'panel.buyers.show', $contract['user_id'] ) }}">{{ __('Посмотреть') }}</a>
                    </td>
                    <td class="text-center">{{ $contract['katm_report_count'] }}</td>
                    <td class="text-center">
                        <a target="_blank"
                           href="{{ localeRoute( 'panel.katm-report.contract', $contract['id'] ) }}">{{ __('Посмотреть') }}</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        {{ $contracts->links() }}
    </div>


@endsection()
