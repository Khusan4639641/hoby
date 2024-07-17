@extends('templates.frontend.app')
@section('class', 'faq list')
@section('title', __('panel/faq.header_faq'))


@section('content')
    <div class="container">
        <h1>{{__('frontend/faq.title_faq')}}</h1>

        @if(count($faq) > 0)
            <div class="accordion" id="accordionFaq">
                @foreach($faq as $index => $item)
                    <div class="card">
                        <div class="card-header" id="heading{{$index}}">

                            <button class="btn btn-link btn-block text-left @if($index >0) collapsed @endif" type="button" data-toggle="collapse" data-target="#collapse{{$index}}" aria-expanded="@if($index >0) false @else true @endif" aria-controls="collapse{{$index}}">
                               {{$item->locale->title}}
                            </button>

                        </div>

                        <div id="collapse{{$index}}" class="collapse @if($index == 0) show @endif" aria-labelledby="heading{{$index}}" data-parent="#accordionFaq">
                            <div class="card-body">
                                {{$item->locale->text}}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div><!-- /.container -->

@endsection
