@extends('templates.billing.app')

@section('class', 'buyer create')

@section('center-header-prefix')
    <a class="link-back" href="{{localeRoute('billing.contracts_for_cancellation')}}">
        <img src="{{asset('images/icons/icon_arrow_orange.svg')}}">
    </a>

@endsection

@section('content')

    <div>
        <div>
            <h2 class="mb-3">{{$company->brand}}</h2>
            <p>
                <span style="font-weight: 700">{{__('billing/order.address')}}:</span>
                {{$company->address}}
            </p>
            <p>
                <span style="font-weight: 700">{{__('billing/order.contract')}} ID:</span>
                {{ $contract_id }}
            </p>
            <p>

            </p>
            <p style="font-size: 18px">
                <span style="font-weight: 700">
                    {{__('billing/order.phone')}}:
                </span>
                +{{$company->phone}}
            </p>
        </div>
        @if((substr($fileUrl, (strlen($fileUrl) - 3), strlen($fileUrl))) == 'pdf')
        <embed src="{{$fileUrl}}" style="width:100%; height:1000px; overflow: hidden; margin: 0 auto;"/>
        @else
        <img class="mt-3 col-10" src="{{$fileUrl}}" alt="{{$company->name}}">
        @endif
    </div>


@endsection
