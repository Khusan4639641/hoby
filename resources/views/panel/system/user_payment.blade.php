@extends('templates.panel.app')

@section('title', 'Повторные списания')

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

                <div class="table">
                        <table>
                            <thead>
                            <tr>
                                <th>Payment ID</th>
                                <th>User_id</th>
                                <th>Карты</th>
                                <th>Сумма</th>
                                <th>Дата</th>
                                <th>Статус</th>
                                {{--<th>#</th>--}}
                            </tr>
                            </thead>
                            <tbody>
                            @if($payments)
                                @foreach($payments as $payment)
                                    <tr>
                                        <td>{{ $payment->id }}</td>

                                        <td><a href="/ru/panel/buyers/{{ $payment->user_id }}" target="_blank">{{ $payment->user_id }}</a></td>
                                        <td>
                                            @if($payment->buyer->cards)
                                            @foreach($payment->buyer->cards as $card)
                                                {{ \App\Helpers\EncryptHelper::decryptData($card->card_number) }}<br>
                                            @endforeach
                                            @endif
                                        </td>
                                        <td>{{ $payment->amount }}</td>
                                        <td>{{ $payment->created_at }}</td>
                                        @if($payment->status==2)
                                            <td>Повтор <a href="">Вернуть</a></td>
                                        @elseif($payment->status==3)
                                            <td>Вернули</td>
                                        @endif
                                        {{--<td><a href="#" class="set-status" data-status="{{$cron->status}}" data-id="{{$cron->id}}">@if($cron->status==1) Запустить @else Остановить @endif</a></td>--}}
                                    </tr>
                                @endforeach
                            @endif
                            </tbody>
                        </table>
                    </div>
                </div>

        </div>

    </div>

@endsection()
