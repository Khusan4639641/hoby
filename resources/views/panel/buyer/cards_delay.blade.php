@extends('templates.panel.app')

@section('title', __('panel/menu.buyer_delay'))



@section('content')

    <style>
        .bold {
            font-weight: bold;
        }

        .hide {
            display: none;
        }

        .show {
            cursor: pointer;
        }

        .icon.open {
            -webkit-transform: rotate(90deg);
            -moz-transform: rotate(90deg);
            -o-transform: rotate(90deg);
            -ms-transform: rotate(90deg);
            transform: rotate(90deg);
        }

        .icon {
            width: 24px;
            height: 24px;
            transform: rotate(0);
        }

        .dropdown-container {
            overflow-y: auto;
            min-height: 250px;
            max-height: 500px;
        "
        }

    </style>


    <div class="bold">Всего: {{ $count }}</div><br>
    <div class="catalog category" id="delayCards">
        <table class="table table-responsive-md table-hover">

            @foreach($buyers as $buyer)
                @php
                  $totalDebt = 0;
                  foreach ($buyer->contracts as $contract) {
                      foreach($contract->schedule as $schedule){
                          $payment_date = strtotime($schedule->payment_date);
                          $now = strtotime(Carbon\Carbon::now()->format('Y-m-d 23:59:59'));
                          if($schedule->status == 0 && $payment_date <= $now){
                              $totalDebt += $schedule->balance;
                          }
                      }
                  }
                @endphp

                <tbody>

                <tr class="show" data-item="dropdown_delays">
                    <td>ID {{ $buyer->id }}</td>
                    <td>{{ $buyer->fio }}</td>
                    <td>{{ $buyer->phone }}</td>
                    <td> Просрочено: {{ $totalDebt }} сум</td>
                    <td><img class="icon" src="{{asset('assets/icons/chevron-right.svg')}}"></td>
                </tr>

                <tr class="dropdown_delays show">
                    <td colspan="6">
                        <div class="dropdown-container">
                            <table class="table-responsive">
                                    <tr>
                                        <th>№</th>
                                        <th>ФИО</th>
                                        <th>Номер карты</th>
                                        <th>Телефон</th>
                                        {{--<th>Баланс</th> --}}
                                        <th>Тип карты</th>
                                        {{--<th>Смс информирование</th>--}}
                                        <th>Доступность</th>
                                    </tr>
                                @foreach($buyer->cards as $card)
                                    @if($card->status == 0)

                                    @php

                                                $card_number = App\Helpers\EncryptHelper::decryptData($card->card_number);
                                                $type = App\Helpers\EncryptHelper::decryptData($card->type);
                                                $sms_info = 'LOOK';
                                                $balance = 'UNLOCK';
                                                //$balance = '<button onclick="getBalance('.$card->id.')" class="btn btn-sm btn-archive" type="button"></button>';
                                                if($card->status == 0){
                                                    $status= 'добавить';
                                                }else{
                                                    $status = 'выключить';
                                                }


                                    @endphp

                                    <tr class="show" data-item="dropdown_delays">
                                        <td>{{ $card->id }}</td>
                                        <td>{{ $card->card_name }}</td>
                                        <td>{{ $card_number }}</td>
                                        <td>{{ $card->phone }}</td>
                                        {{-- <td><button onclick="getBalance('{{$card->id}}')" >{{ $balance }}</button></td> --}}
                                        <td>{{ $card->type }}</td>
                                        {{-- <td><button onclick="getSmsInfo('{{$card->id}}')" >{{ $sms_info }}</button></td> --}}
                                        <td  id="status"><button onclick="changeStatus('{{$card->id}}')" >{{ $status }}</button></td>

                                    </tr>

                                    @endif
                                @endforeach

                            </table>
                            <hr>
                            <div >
                                <button type="submit" onclick="addCardsHumoById('{{$buyer->id}}')" class="btn btn-primary">{{__('app.btn_add_cards_humo')}}</button>
                            </div>
                            <hr>
                        </div>
                    </td>
                </tr>
                </tbody>
                <div class="loading"><img src="{{asset('images/media/loader.svg')}}"></div>

            @endforeach
        </table>
        {{ $buyers->links() }}
    </div>


    <script>

        // ADD cards Humo by ID
        /*function getBalance(id) {

            let post = {
                api_token: '{{Auth::user()->api_token}}',
                card_id: id,
            };
            axios.post('/api/v1/employee/buyers/get-balance', post,
                { headers: { 'Content-Language': '{{app()->getLocale()}}' } })
                .then(response => {
                    console.log(response.data);
                    if (response.data.result.balance > 100) {
                       // this.balance = response.data.result.balance / 100;
                    } else {
                       // this.balance = 0;
                    }
                    this.card_name = response.data.result.owner;
                    this.card_username = response.data.result.phone;
                    scoring.$forceUpdate();
                }).catch(e => {

            });
        }*/

        // ADD cards Humo by ID
        function addCardsHumoById(buyer_id) {

            let url = '/api/v1/employee/buyers/add-humo/'  + buyer_id + '?api_token=' + Cookies.get('api_token');

            axios.get(url).then(response => {
                console.log(response.data);
                if (response.data.status === 'success') {
                    alert(response.data.status);
                    window.location.reload();
                }else{
                    alert('no cards or no result, try later');
                }
            });

        }

        //Archive
        function changeStatus(id) {

            let url = '/api/v1/employee/buyers/change-status/' + id + '?api_token=' + Cookies.get('api_token');

            axios.get(url).then(response => {
                if (response.data.status === 'success') {
                    alert(response.data.status);
                    window.location.reload();
                }else{
                    alert(response.data.data.message);
                }
            });
        }


        $('.show').click(function () {
            if ($(this).has('hide')) {
                $(this).find('.icon').toggleClass('open');
                $(this).parent().find('tr.' + $(this).data('item')).toggleClass('hide');
            }
        })

    </script>

@endsection
