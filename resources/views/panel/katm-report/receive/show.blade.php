@extends('templates.panel.app')

@section('title', "Получаемый KATM отчёт контракта №: " . $report->contract->id)

@section('content')

    <div class="col-lg-12">
        <div class="row">
            <div class="col-lg-6">
                <h4 class="mb-5">Заявка в
                    КАТМ: @isset($report->contract->katmClaim) {{ $report->contract->katmClaim->claim }} @endisset</h4>
                <table class="table no-footer table-striped">
                    <tbody>
                    <tr>
                        <th>ID</th>
                        <td class="text-right">{{ $report->id }}</td>
                    </tr>
                    <tr>
                        <th>Тип</th>
                        <td class="text-right">{{ $report->report_type }}</td>
                    </tr>
                    <tr @if($report->status === 2) class="table-danger" @endif>
                        <th>Статус</th>
                        <td class="text-right">{{ $report->status }}</td>
                    </tr>
                    <tr>
                        <th>Токен отчёта</th>
                        <td class="text-right">{{ $report->token }}</td>
                    </tr>
                    <tr>
                        <th>Дата получения</th>
                        <td class="text-right">{{ $report->received_date }}</td>
                    </tr>
                    <tr>
                        <th>Ошибка</th>
                        <td class="text-right">{{ $report->error_response }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div class="col-lg-6">
                <h5 class="mb-5">Отчёт в json формате</h5>
                <pre class="bg-dark text-light p-4">@json(json_decode($jsonReport, true), JSON_PRETTY_PRINT)</pre>
            </div>
        </div>
    </div>

@endsection()
