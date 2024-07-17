@extends('templates.frontend.app')
@section('class', 'news detail')
@section('title', $news->locale->title)

@section('content')
    <div class="news-header" style="background-image: url(/storage/{{$news->locale->image->path ?? ''}})">
        <div class="inner">
            <div class="container">
                <div class="date">{{$news->date}}</div>
                <h1>{{$news->locale->title}}</h1>
                <div class="preview-text">
                    {!! $news->locale->preview_text !!}
                </div>
            </div><!-- /.container -->
        </div><!-- /.inner -->
    </div><!-- /.news-header -->

    <div class="detail-text">
        <div class="container">
            {!! $news->locale->detail_text !!}
        </div>
    </div><!-- /.detail-text -->

    @if($related)

        <div class="container">
            <div class="news list owl-carousel ">
                @foreach($related as $item)
                    <div class="item small">
                        <div class="inner">
                            <a style="background-image: url({{$item->locale->image->preview ?? ''}});" class="img" href="{{localeRoute('news.show', $item)}}"><span class="overlay"></span></a>
                            <div class="date">{{$item->date}}</div>
                            <div class="title"><a href="{{localeRoute('news.show', $item)}}">{{$item->locale->title}}</a></div>
                            <div class="preview">{!! $item->locale->preview_text !!}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div><!-- /.container -->

        <script>
            $(document).ready(function(){
                $('.owl-carousel').owlCarousel({

                    margin: 30,
                    nav:true,
                    responsive:{
                        0:{
                            items:1
                        },
                        600:{
                            items:2
                        },
                        1000:{
                            items:3
                        },
                        1200:{
                            items:4
                        }
                    }
                })
            })
        </script>
    @endif
@endsection
