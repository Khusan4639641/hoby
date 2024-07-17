@extends('templates.panel.app')

@section('title', __('panel/menu.cron'))
@section('class', 'cron')

@section('content')

    <style>
        a.set-status{
            pointer: cursor;
        }
    </style>

    <div class="container">

        <div class="row">

            <div class="col-12">
                <div class="lead">Информация о клиентах</div>
                <table>
                    <tr>
                        <th>Дата инициализации:</th><td align="right"> {{$cronInit->updated_at}}</td>
                    </tr>
                    <tr>
                        <th>Всего просрочников:</th><td align="right">{{$cronUsers}}</td>
                    </tr>
					<tr>
                        <th>Клиентов с погашенной просрочкой:</th><td align="right">{{$cronUsersReady}}</td>
                    </tr>
					<tr>
                        <th>Клиентов с частично погашенной просрочкой:</th><td align="right">{{$cronUsersReadyPart}}</td>
                    </tr>
                    <tr>
                        <th>Остаток просрочки:</th><td align="right">{{number_format($debts->balance,2,'.',' ')}}</td>
                    </tr>
                    <tr>
                        <th>Всего погашено:</th><td align="right">{{number_format($payment->balance,2,'.',' ')}}</td>
                    </tr>
                </table>
            </div>

        </div>

        <hr>
        <div class="row">

            <div class="col-12">
                <div class="lead">Информация о планировщиках</div>

                <div class="table">
                        <table>
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>От</th>
                                <th>До</th>
                               {{-- <th>Запусков</th> --}}
                                <th>Статус</th>
                                <th>Дата запуска</th>
                                <th>#</th>
                            </tr>
                            </thead>
                            <tbody>
                            @if($cronData)
                                @foreach($cronData as $cron)
                                    <tr>
                                        <td>{{ $cron->range }}</td>
                                        <td>{{ $cron->start }}</td>
                                        <td>{{ $cron->end }}</td>
                                        {{-- <td>{{ $cron->quantity }}</td> --}}
                                        <td id="status">{{ $cron->status }}</td>
                                        <td>{{ $cron->updated_at }}</td>
                                        <td>
{{--                                            <a href="#" class="set-status" data-status="{{$cron->status}}" data-id="{{$cron->id}}">@if($cron->status==1) Запустить @else Остановить @endif</a>--}}
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                            </tbody>
                        </table>
                    </div>
                    <div class="chart">
                        <canvas id="chartOrders"></canvas>
                    </div>
                </div>

        </div>

    </div>

    <script>
        $(document).ready(function () {
            $('.set-status').click(function (e) {
                e.preventDefault();

                if(!confirm('Продолжить')) return false;

                var id = $(this).data('id');
                var status = $(this).data('status')==1 ? 0 : 1;
                var btn = $(this);
                var btn_status = $(this).parent().parent().find('#status')

                console.log(status + ' = ' + $(btn_status).text() )

                $.ajax({
                    headers: {
                        'Content-Language': '{{app()->getLocale()}}',
                        'Accept': 'application/json',
                    },
                    'url': '/ru/panel/system/set-cron-status',
                    'type': 'post',
                    data: {'id': id, 'status': status, 'api_token': '{{Auth::user()->api_token}}','_token': '{{ csrf_token() }}',},
                    success: function (result) {
                        if (result.status == 'success') {
                            if (result.data.status == 1) {
                                $(btn).text('Запустить');
                                $(btn).data('status',1);
                                $(btn_status).text(1);
                            } else {
                                $(btn).text('Остановить');
                                $(btn).data('status',0);
                                $(btn_status).text(0);
                            }
                        }
                    },
                    error: function (e) {
                        alert('error');
                    }
                })
            })

        })

    </script>
@endsection()
