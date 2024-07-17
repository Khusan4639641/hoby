@extends('templates.panel.app')

@section('title', "KATM отчёты контракта №: " . $contract->id)

@section('content')

    <div class="row">

        <div class="col-lg-12">
            <h4 class="mb-5">Заявка в
                КАТМ: @isset($contract->katmClaim) {{ $contract->katmClaim->claim }} @endisset</h4>
        </div>

        <div class="col-lg-9">

            <h5 class="mb-4">Отправленные отчёты</h5>

            <table class="table table-striped no-footer">
                <tbody>
                <tr>
                    <th class="text-center">ID</th>
                    <th class="text-center">Сортировка</th>
                    <th class="text-center">Тип</th>
                    <th class="text-center">Номер</th>
                    <th class="text-center">Статус</th>
                    <th class="text-center">Дата отправки</th>
                    <th class="text-center">Отчёт</th>
                    <th class="text-center">Ошибка</th>
                </tr>
                @foreach($reports as $report)
                    <tr @if($report['status'] === 2) class="table-danger" @endif>
                        <td class="text-center">{{ $report['id'] }}</td>
                        <td class="text-center">{{ $report['order'] }}</td>
                        <td class="text-left">{{ $report['report_type'] }}</td>
                        <td class="text-center">{{ $report['report_number'] }}</td>
                        <td class="text-center">{{ $report['status'] }}</td>
                        <td class="text-center">{{ $report['sent_date'] }}</td>
                        <td class="text-center"><a target="_blank"
                                                   href="{{ localeRoute( 'panel.katm-report.sending.report', $report['id'] ) }}">{{ __('Посмотреть') }}</a>
                        </td>
                        <td>{{ \Illuminate\Support\Str::substr($report['error_response'], 0, 14) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>


        <div class="col-lg-3">

            <h5 class="mb-4 text-center">Полученные отчёты</h5>

            @foreach($receiveReports as $receiveReport)
                <div class="card mb-4">
                    <div class="card-body @if($receiveReport['status'] === 2) table-danger @endif">
                        <table class="table-sm w-100">
                            <tbody>
                            <tr>
                                <th>ID</th>
                                <td class="text-right">{{ $receiveReport['id'] }}</td>
                            </tr>
                            <tr>
                                <th>Статус</th>
                                <td class="text-right">{{ $receiveReport['status'] }}</td>
                            </tr>
                            <tr>
                                <th>Отчёт (JSON)</th>
                                <td class="text-right"><a target="_blank"
                                                          href="{{ localeRoute( 'panel.katm-report.receiving.report', $receiveReport['id'] ) }}">{{ __('Посмотреть') }}</a>
                                </td>
                            </tr>
                            <tr>
                                <th>Отчёт</th>
                                <td class="text-right"><a target="_blank"
                                                          href="{{ localeRoute( 'panel.katm-report.receiving.report.decorated', $receiveReport['id'] ) }}">{{ __('Посмотреть') }}</a>
                                </td>
                            </tr>
                            <tr>
                                <th>Ошибка</th>
                                <td class="text-right">{{ $receiveReport['error_response'] }}</td>
                            </tr>
                            <tr>
                                <th>Дата получения</th>
                                <td class="text-right">{{ \Carbon\Carbon::parse($receiveReport['received_date'])->format('Y-m-d') }}</td>
                            </tr>
                            <tr>
                                <th>Время получения</th>
                                <td class="text-right">{{ \Carbon\Carbon::parse($receiveReport['received_date'])->format('H:i:s') }}</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach

        </div>

    </div>


@endsection()
