@extends('templates.frontend.app')
@section('class', 'page '.request()->name)
@section('title', __('frontend/page_installment.title'))


@section('content')
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{localeRoute('home')}}">{{__('frontend/breadcrumbs.home')}}</a></li>
                <li class="breadcrumb-item active" aria-current="page">
                    {{__('frontend/page_bonus.title')}}
                </li>
            </ol>
        </nav>

        <h1 class="text-left">{{__('frontend/page_bonus.h1')}}</h1>
        <div class="text">{!! __('frontend/page_bonus.text') !!}</div>


        @php
            $servisesController = new App\Http\Controllers\Core\PayController();

            $servises = [];
            //Mobile
            $params = [
                'status'    => 1,
                'order_by'  => 'name',
                'type'      => 1
            ];
            $services['mobile'] = $servisesController->list($params)['data'];

            //Internet
            $params = [
                'status'    => 1,
                'order_by'  => 'name',
                'type'      => 0
            ];
            $services['internet'] = $servisesController->list($params)['data'];
        @endphp


        @if(count($services) > 0)
           <section class="block zpay">
               @foreach($services as $type => $list)
                   <div class="lead"><div>{{__('cabinet/zpay.type_'.$type)}}</div></div>
                   <div class="list">
                       @foreach($list as $item)
                           <div data-img="{{$item['img']}}" data-name="{{$item['name']}}" data-type="{{$type}}" data-service_id="{{$item['service_id']}}" data-id="{{$item['id']}}" class="item">
                               <img src="{{$item['img']}}">
                               <div class="name">{{$item['name']}}</div>
                           </div><!-- /.item -->
                       @endforeach
                   </div><!-- /.list -->
               @endforeach
           </section>
        @endif

    </div>
@endsection
