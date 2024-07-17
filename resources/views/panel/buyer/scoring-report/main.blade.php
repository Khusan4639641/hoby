<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{__('Отчёт по скорингу')}}</title>
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
</head>
<body class="container pt-5 pb-5">

<div>

    <h3 class="text-center font-weight-bold">{{__('Отчёт по скорингу')}}</h3>
    <h4 class="text-center text-muted">{{__('ID покупателя: :id', ['id' => $report['id']])}}</h4>
    <h4 class="text-center">{{$report['name']}}</h4>
    <table class="table table mt-5 mb-5">
        <thead>
        <tr>
            <th class="text-center">{{__('Процесс')}}</th>
            <th class="text-center">{{__('Результат')}}</th>
        </tr>
        </thead>
        <tbody>
        @foreach($report['rows'] as $reportItem)
            @include('panel.buyer.scoring-report.row',
    [
    'key' => $reportItem['key'],
    'status' => $reportItem['state'],
    'message' => $reportItem['state_text'],
    'error_message' => $reportItem['state_error_message'],
    ])
        @endforeach
        </tbody>
    </table>

    @if($report['total_state'] === \App\Models\ScoringResult::STATE_USER_INFO_SUCCESS && $report['final_limit'] !== null)
        <h4 class="text-right">{{__('Итоговый лимит: :limit', ['limit' => number_format($report['final_limit'], 0, '', ' ')])}}</h4>
    @endif

    @if($report['total_state'] === \App\Models\ScoringResult::STATE_USER_INFO_SUCCESS)
        <h4 class="text-right text-success">{{__('Успешное завершение скоринга')}}</h4>
    @elseif($report['total_state'] === \App\Models\ScoringResult::STATE_USER_INFO_NOT_SUCCESS)
        <h4 class="text-right text-danger">{{__('Покупатель не прошёл скоринг')}}</h4>
    @elseif($report['total_state'] === \App\Models\ScoringResult::STATE_FAILED_RESPONSE)
        <h4 class="text-right text-danger">{{__('Ответ от сервиса не удовлетворителен')}}</h4>
        {{--        @if($report['error_message'])--}}
        {{--            <h6 class="text-right"><span class="pb-1 badge-pill badge-danger">{{ $report['error_message'] }}</span>--}}
        {{--            </h6>--}}
        {{--        @endif--}}
    @elseif($report['total_state'] === \App\Models\ScoringResult::STATE_AWAIT_RESPONSE)
        <h4 class="text-right text-warning">{{__('Процесс скоринга не завершён')}}</h4>
    @endif


</div>

</body>

</html>
