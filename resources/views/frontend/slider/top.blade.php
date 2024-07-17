@if(count($items) > 0)

    <section class="block welcome">
        <div class="container">
            <div id="carouselExampleSlidesOnly" class="carousel slide" data-ride="carousel">
                @php
                    $counter = 0;
                @endphp
                <ol class="carousel-indicators">
                    @foreach($items as $item)
                        <li data-target="#carouselExampleIndicators" data-slide-to="{{$counter}}" class="@if($counter == 0)active @endif"></li>
                        @php
                            $counter ++;
                        @endphp
                    @endforeach
                </ol>

                @php
                    $counter = 0;
                @endphp
                <div class="carousel-inner">
                    @foreach($items as $item)
                        <div style="background-image: url(/storage/{{$item->image->path}});" class="carousel-item @if($counter == 0)active @endif">
                            <div class="inner">
                                <h1>{!!$item->title!!}</h1>
                                <div class="text">{!! $item->text !!}</div>

                                @if($item->link != null)
                                    <a href="{{$item->link}}" class="btn btn-lg btn-light btn-arrow">{{$item->label??__('app.btn_readmore')}}</a>
                                @endif

                            </div>
                        </div>

                        @php
                            $counter ++;
                        @endphp
                    @endforeach
                </div><!-- /.carousel-inner -->
            </div><!-- /.slide -->
        </div><!-- /.container -->
    </section>
@endif
