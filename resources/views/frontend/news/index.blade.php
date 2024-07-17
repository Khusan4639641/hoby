@extends('templates.frontend.app')
@section('class', 'news list')
@section('title', __('panel/news.header_news'))

@php
    $perRow = [4, 4];//[2, 4];
    $class = ['small', 'small'];['big', 'small'];
    $row = 0;
    $count = 1;
@endphp

@section('content')

        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{localeRoute('home')}}">{{__('frontend/breadcrumbs.home')}}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">
                        {{__('panel/news.header_news')}}
                    </li>
                </ol>
            </nav>

            <h1>{{__('panel/news.header_news')}}</h1>

            <div class="row">
                @foreach($news as $item)
                    @php
                        $size = 12/$perRow[$row];
                    @endphp

                    <div class="col-12 col-md-{{$size}}">
                        <div class="item {{$class[$row]}}" @if($size == 6)style="background-image: url({{$item->locale->image ? $item->locale->image->preview??'' : ''}});"@endif>
                            <div class="inner">
                                <a href="{{localeRoute('news.show', $item)}}" class="img" style="background-image: url({{$item->locale->image ? $item->locale->image->preview??'' : ''}});"><div class="overlay"></div></a>
                                <div class="date">{{$item->dateFormat}}</div>
                                <div class="title"><a href="{{localeRoute('news.show', $item)}}">{{$item->locale->title}}</a></div>
                                <div class="preview">{!! $item->locale->preview_text !!}</div>
                            </div>
                        </div><!-- /.item -->
                    </div><!-- /.col -->

                    @php
                        $count ++;
                        if($count > $perRow[$row]) {
                            $row = abs($row-1);
                            $count = 1;
                        }
                    @endphp
                @endforeach
            </div>
        </div><!-- /.container -->

@endsection
