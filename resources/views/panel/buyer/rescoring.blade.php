@extends('templates.panel.app')

@section('title', __('panel/menu.rescoring'))

@section('content')

    <div class="catalog category" id="catalog">

        <table class="table buyers-list">
            <thead>
                <tr>
                    <th>{{__('panel/buyer.scoring_ball')}}</th>
                    <th>{{__('panel/buyer.scoring_clients')}}</th>
                    <th>{{__('panel/buyer.scoring_send')}}</th>
                </tr>
            </thead>
            <tbody>
            @php
                $cnt = 0;
                $sum = 0;
            @endphp
            @foreach($data as $item)
                <tr>
                    <td>{{ $item->scoring }}</td>
                    <td>{{ $item->cnt }}</td>
                    <td>{{ $item->sum }}</td>
                </tr>
                @php
                    $cnt += $item->cnt;
                    $sum += $item->sum;
                @endphp

            @endforeach
            <tr>
                <th>{{ __('cabinet/order.lbl_total') }}</th>
                <th>{{ $cnt }}</th>
                <th>{{ $sum }}</th>
            </tr>
            </tbody>
        </table>

        <div class="loading"><img src="{{asset('images/media/loader.svg')}}"></div>

        <form class="" method="POST" action="{{localeRoute('panel.buyer.rescoring')}}">
            @csrf
            <button class="btn btn-orange">{{__('app.btn_check')}}</button>
        </form>

    </div>

@endsection
