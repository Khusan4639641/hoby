@extends('templates.frontend.app')

@section('title', __('frontend/index.meta_title'))
@section('class', 'home')

@section('content')

    <div class="input-search d-block d-md-none mb-3">
        <div class="container-fluid">
            <form action="{{localeRoute('search')}}">
                <div class="input-group">
                    <input type="text" required name="q" class="form-control" value="{{request()->get('q')}}" autocomplete="off">
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-success btn-search"></button>
                    </div>
                </div>
            </form>
        </div>
    </div>


    {{App\Helpers\SliderHelper::render('frontend', 'top')}}

    {{--<div class="container">
        <section class="block welcome">
            <div class="inner">
                <h1>{!!__('frontend/index.welcome_title')!!}</h1>
                <div class="text">{!! __('frontend/index.welcome_text') !!}</div>
                <a href="{{localeRoute('register')}}" class="btn btn-lg btn-light btn-arrow">{{__('app.btn_readmore')}}</a>
            </div>
        </section><!-- /.welcome -->
    </div><!-- /.container -->--}}


    <section class="block registration">
        <div class="container">
            <div class="row">

                <div class="col-12 col-lg-6 part partner">
                    <div class="inner-wrap">
                        <div class="inner">
                            <div class="title">{{__('frontend/index.registration_title_become_partner')}}</div>
                            <div class="text">{{__('frontend/index.registration_text_partner')}}</div>
                        </div>
                        <a href="{{localeRoute('partners.welcome')}}" class="btn btn-light btn-arrow">{{__('frontend/index.registration_btn_register')}}</a>
                    </div><!-- /.inner-wrap -->
                </div><!-- ./part.partner -->

                <div class="col-12 col-lg-6 part buyer">
                    <div class="inner-wrap">
                        <div class="inner">
                            <div class="title">{{__('frontend/index.registration_title_become_buyer')}}</div>
                            <div class="text">{{__('frontend/index.registration_text_buyer')}}</div>
                        </div>
                        <a href="{{localeRoute('register')}}" class="btn btn-light btn-arrow">{{__('frontend/index.registration_btn_register')}}</a>
                    </div><!-- /.inner-wrap -->
                </div><!-- ./part.buyer -->

            </div><!-- /.row -->
        </div><!-- /.container -->
    </section><!-- /.registration -->

    @if(count($partners) > 0)
        <section class="block partner list">
            <div class="container">
                <div class="text-center title">{{__('frontend/index.partners_title')}}</div>
                @include('frontend.partner.parts.list')

                <div class="text-center">
                    <a class="btn btn-lg btn-arrow btn-success" href="{{localeRoute('partners.index')}}">{{__('frontend/index.partners_all')}}</a>
                </div>
            </div>
        </section><!-- /.partner list -->
    @endif

    {{--@if(count($newProducts) > 0)
        <section class="block new-products products list">
            <div class="container">
                <div class="text-center title">{{__('frontend/index.new_products_title')}}</div>
                <div class="products owl-carousel">
                    @foreach($newProducts as $product)
                        <div class="product">
                            <div class="top">
                                <div class="badge badge-info">{{__('frontend/catalog.badge_new')}}</div>
                            </div><!-- /.top -->

                            <a class="image blink" href="{{localeRoute('catalog.product.show', ['slug' => $product->locale->slug, 'id' => $product->id])}}">
                                @if($product->images->first())
                                    <img src="{{$product->images->first()->preview}}" alt="">
                                @else
                                    <div class="no-image"></div>
                                @endif
                            </a>

                            <div class="price">{{preg_replace("/(\d{1,3}(?=(\d{3})+(?:\.\d|\b)))/","$1".' ', $product->price)}} {{__('app.currency')}}</div>
                            <div class="product-title"><a href="{{localeRoute('catalog.product.show', ['slug' => $product->locale->slug, 'id' => $product->id])}}">{{$product->locale->title}}</a></div>
                            <div class="credit-from">{{__('frontend/catalog.credit')}} <span>- {{__('frontend/catalog.lbl_3_payments')}}</span></div>
                            <div class="product-controls">
                                <button type="button" class="btn btn-success add-to-cart" data-product="{{$product->id}}">
                                    {{__('frontend/catalog.lbl_from')}} {{preg_replace("/(\d{1,3}(?=(\d{3})+(?:\.\d|\b)))/","$1".' ', $product->credit_from)}} {{__('frontend/catalog.to_month_short')}}
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div><!-- /.products -->

                <div class="text-center mt-5">
                    <a class="btn btn-success btn-lg btn-arrow" href="{{localeRoute('catalog.index')}}">{{__('frontend/catalog.btn_all_products_category')}}</a>
                </div>
            </div><!-- /.container -->
        </section><!-- /.news -->

        <script>
            $(window).on('load', function(){
                let tM = 0;
                $('.new-products .products .owl-item.active').each(function(){

                    let iH = $('.product-title', $(this)).height();
                    if(iH > tM) tM = iH;

                });
                $('.new-products .product .product-title').height(tM);
            });

            $(document).ready(function(){

                $('.new-products .owl-carousel').owlCarousel({
                    margin: 15,
                    dots: false,
                    loop: true,
                    autoplay: true,
                    autoplayTimeout: 3000,
                    responsive:{
                        0:{
                            items:1
                        },
                        470:{
                            items:2
                        },
                        800:{
                            items:4
                        },
                    }
                })
            })
        </script>
    @endif--}}

    {{--@if(count($categories) > 0)
        <section class="block categories list">
            @php
                $i = 0;
            @endphp

            @foreach($categories as $category)

                @if(count($category['products']) >= 5)
                    <div class="text-center category">
                        <div class="container">
                            <div class="title">{{$category['category']->locale->title}}</div>

                            @if(count($category['sub']) >= 2)
                                @php
                                    $elem = [
                                        2 => [6, 6],
                                        3 => [3, 3, 6]
                                    ];
                                    $display = $elem[count($category['sub'])];
                                    shuffle($display);
                                    $i = 0;
                                @endphp

                                <div class="sub">
                                    <div class="container">
                                        <div class="row">
                                            @foreach($category['sub'] as $item)

                                                <div class="item-wrapper col-12 col-sm-6 col-md col-md-{{$display[$i]}}">
                                                    <div onclick="window.location.href='{{localeRoute('catalog.category.show', ['slug' => $item->language->slug, 'id' => $item->id])}}';" class="item @if($item->image != null) with-image @endif" style="@if($item->image != null) background-image: url(/storage/{{$item->image->path}}); @endif">
                                                        <div class="inner">
                                                            <div class="name">{{$item->language->title}}</div>
                                                            <div class="preview-text">{!! $item->language->preview_text !!}</div>
                                                        </div>
                                                    </div><!-- /.item -->
                                                </div><!-- /.item-wrapper -->
                                                @php
                                                    $i ++;
                                                @endphp
                                            @endforeach
                                        </div><!-- /.row -->
                                    </div>
                                </div><!-- /.sub -->
                            @endif

                            <div class="owl-carousel products category-{{$category['category']->id}}">
                                @foreach($category['products'] as $product)
                                    <div class="product">
                                        <div class="top">
                                            <div class="badge badge-info">{{__('frontend/catalog.badge_new')}}</div>
                                        </div><!-- /.top -->

                                        <a class="image blink" href="{{localeRoute('catalog.product.show', ['slug' => $product->locale->slug, 'id' => $product->id])}}">
                                            @if($product->images->first())
                                                <img src="{{$product->images->first()->preview}}" alt="">
                                            @else
                                                <div class="no-image"></div>
                                            @endif
                                        </a>

                                        <div class="price">{{preg_replace("/(\d{1,3}(?=(\d{3})+(?:\.\d|\b)))/","$1".' ', $product->price)}} {{__('app.currency')}}</div>
                                        <div class="product-title"><a href="{{localeRoute('catalog.product.show', ['slug' => $product->locale->slug, 'id' => $product->id])}}">{{$product->locale->title}}</a></div>
                                        <div class="credit-from">{{__('frontend/catalog.credit')}} <span>- {{__('frontend/catalog.lbl_3_payments')}}</span></div>
                                        <div class="product-controls">
                                            <button type="button" class="btn btn-success add-to-cart" data-product="{{$product->id}}">
                                                {{__('frontend/catalog.lbl_from')}} {{preg_replace("/(\d{1,3}(?=(\d{3})+(?:\.\d|\b)))/","$1".' ', $product->credit_from)}} {{__('frontend/catalog.to_month_short')}}
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                        </div><!-- /.container -->

                        <script>
                            $(window).on('load', function(){
                                let tM = 0;
                                $('.category .category-{{$category['category']->id}} .owl-item.active').each(function(){
                                    let iH = $('.product-title', $(this)).height();
                                    if(iH > tM) tM = iH;

                                });
                                $('.category .category-{{$category['category']->id}} .product .product-title').height(tM);
                            });

                            $(document).ready(function(){

                                $('.category .category-{{$category['category']->id}}.owl-carousel').owlCarousel({
                                    margin: 15,
                                    dots: false,
                                    loop: true,
                                    @if($i%2 !== 0)
                                        autoplay: true,
                                        autoplayTimeout: 3000,
                                    @endif
                                    responsive:{
                                        0:{
                                            items:1
                                        },
                                        470:{
                                            items:2
                                        },
                                        800:{
                                            items:4
                                        },
                                        1000: {
                                            items: 5
                                        }
                                    }
                                })
                            })
                        </script>
                    </div><!-- /.category -->

                    @php
                        $i ++;
                    @endphp
                @endif
            @endforeach

        </section><!-- /.discounts -->
    @endif--}}

    @if(count($news) > 0)
        <section class="block news list">
            <div class="container">
                <div class="title text-center">{{__('frontend/index.news_title')}}</div>

                <div class="owl-carousel">
                    @foreach($news as $item)
                            <div class="item small">
                                <div class="inner">
                                    <a href="{{localeRoute('news.show', $item)}}" class="img" style="background-image: url({{$item->locale->image->preview??''}});"><div class="overlay"></div></a>
                                    <div class="date">{{$item->dateFormat}}</div>
                                    <div class="title"><a href="{{localeRoute('news.show', $item)}}">{{$item->locale->title}}</a></div>
                                    <div class="preview">{!! $item->locale->preview_text !!}</div>
                                </div>
                            </div><!-- /.item -->
                    @endforeach
                </div><!-- /.row -->

                <div class="all">
                    <a class="btn btn-lg btn-arrow btn-success" href="{{localeRoute('news.index')}}">{{__('frontend/index.news_all')}}</a>
                </div>
            </div><!-- /.container -->
        </section><!-- /.news -->


        <script>
            $(document).ready(function(){
                $('.news .owl-carousel').owlCarousel({
                    margin: 20,
                    dots: false,
                    responsive:{
                        0:{
                            items:1
                        },
                        600:{
                            items:2
                        },
                        800:{
                            items:3
                        },
                        1000:{
                            items:4
                        }
                    }
                })
            })
        </script>
    @endif


    @if(count($discounts) > 0)
        <section class="block discounts list">
            <div class="container">
                <div class="title text-center">{{__('frontend/index.discounts_title')}}</div>

                <div class="owl-carousel">
                    @foreach($discounts as $item)

                        <div class="item">

                            <a href="{{localeRoute('discounts.show', $item->id)}}" class="img" style="background-image: url({{$item->locale->image_list->preview}});"></a>
                            <a href="{{localeRoute('discounts.show', $item->id)}}" class="name">{{$item->locale->title}}</a>

                        </div><!-- /.item -->

                    @endforeach
                </div><!-- /.owl-carousel -->

            </div><!-- /.container -->
        </section><!-- /.discounts -->

        <script>
            $(document).ready(function(){
                $('.discounts .owl-carousel').owlCarousel({
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
                        }
                    }
                })
                if($('.discounts.list .owl-carousel .owl-nav').hasClass('disabled'))
                    $('.discounts.list .owl-carousel').css('padding', "0");
                else
                    $('.discounts.list .owl-carousel').css('padding', "0 70px");
            })
        </script>
    @endif


    @include('frontend.index.parts.registration_info')
@endsection
