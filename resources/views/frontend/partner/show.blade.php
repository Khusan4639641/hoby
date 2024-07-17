@extends('templates.frontend.app')

@section('title', $partner->name)
@section('class', 'partner show')


@section('content')

    <div class="container">

        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{localeRoute('home')}}">{{__('frontend/breadcrumbs.home')}}</a></li>
                <li class="breadcrumb-item"><a href="{{localeRoute('partners.index')}}">{{__('frontend/breadcrumbs.partners')}}</a></li>
                <li class="breadcrumb-item active" aria-current="page">
                    {{$partner->brand??$partner->name}}
                </li>
            </ol>
        </nav>

        <h1>{{$partner->brand??$partner->name}}</h1>
        <div class="row">
            <div class="col-12 col-md-3">
                @if($partner->logo)
                    <img class="preview" src="{{$partner->logo->preview}}">
                @else
                    <div class="preview dummy"></div>
                @endif
            </div>
            <div class="col-12 col-md-9">

                <div class="description">
                    {!! $partner->description!!}
                </div>

                <div class="addresses">
                    <div class="lead">{{__('frontend/partner.shop_addresses')}}</div>
                    <div class="row">
                        <div class="col-12 col-md-4">
                            <div class="item">

                                <div class="line">
                                    @if($partner->brand != "")
                                        {{$partner->brand}}
                                    @elseif($partner->name != "")
                                        {{$partner->name}}
                                    @endif
                                </div><!-- /.line -->

                                @if($partner->phone != "")
                                    <div class="line phone">
                                        <div class="title">{{__('frontend/partner.phone')}}</div>
                                        <div class="value">+ {{$partner->phone}}</div>
                                    </div><!-- /.line -->
                                @endif
                                @if($partner->address != "")
                                    <div class="line address">
                                        <div class="title">{{__('frontend/partner.address')}}</div>
                                        <div class="value">{{$partner->address}}</div>
                                    </div><!-- /.line -->
                                @endif
                                <div class="line hours">
                                    <div class="title">{{__('frontend/partner.hours')}}</div>
                                    <div class="value">{{__('frontend/partner.hours_text')}}</div>
                                </div><!-- /.line -->
                                @if($partner->website)
                                    <div class="line website">
                                        <div class="title">{{__('frontend/partner.website')}}</div>
                                        <div class="value"><a target="_blank" href="{{$partner->website}}">{{$partner->website}}</a></div>
                                    </div><!-- /.line -->
                                @endif
                            </div><!-- /.item -->
                        </div>

                        @if(count($partner->affiliates) > 0)
                            @foreach($partner->affiliates as $affiliate)
                                <div class="col-12 col-md-4">
                                    <div class="item">
                                        @if($partner->name != "")
                                            <div class="line">
                                                {{$affiliate->name}}
                                            </div><!-- /.line -->
                                        @endif
                                        @if($affiliate->phone != "")
                                            <div class="line phone">
                                                <div class="title">{{__('frontend/partner.phone')}}</div>
                                                <div class="value">+ {{$affiliate->phone}}</div>
                                            </div><!-- /.line -->
                                        @endif
                                        @if($affiliate->address != "")
                                            <div class="line address">
                                                <div class="title">{{__('frontend/partner.address')}}</div>
                                                <div class="value">{{$affiliate->address}}</div>
                                            </div><!-- /.line -->
                                        @endif
                                        <div class="line hours">
                                            <div class="title">{{__('frontend/partner.hours')}}</div>
                                            <div class="value">{{__('frontend/partner.hours_text')}}</div>
                                        </div><!-- /.line -->
                                        @if($affiliate->website)
                                            <div class="line website">
                                                <div class="title">{{__('frontend/partner.website')}}</div>
                                                <div class="value"><a target="_blank" href="{{$affiliate->website}}">{{$partner->website}}</a></div>
                                            </div><!-- /.line -->
                                        @endif
                                    </div><!-- /.item -->
                                </div>
                            @endforeach
                        @endif
                    </div><!-- /.row -->
                </div><!-- /.addresses -->
            </div>
        </div><!-- /.row -->

    </div><!-- /.container -->


    @if(count($products) > 0)
        <section class="block products list">
            <div class="container">
                <div class="text-center title">{{__('frontend/partner.company_products')}}</div>
                <div class="row">
                    @foreach($products as $product)
                        <div class="product-wrapper col-12 col-sm-6 col-md-4 col-lg-3">
                            <div class="product">
                                <a class="image blink" href="{{localeRoute('catalog.product.show', ['slug' => $product->locale->slug, 'id' => $product->id])}}">
                                    @if($product->images->first())
                                        <img src="{{$product->images->first()->preview}}" alt="">
                                    @else
                                        <div class="no-image"></div>
                                    @endif
                                </a>
                                <div class="price">{{preg_replace("/(\d{1,3}(?=(\d{3})+(?:\.\d|\b)))/","$1".' ', $product->price)}} {{__('app.currency')}}</div>
                                <div class="product-title"><a href="{{localeRoute('catalog.product.show', ['slug' => $product->locale->slug, 'id' => $product->id])}}">{{$product->locale->title}}</a></div>
                                <div class="credit-from">{{__('frontend/catalog.credit_from')}} <span>{{preg_replace("/(\d{1,3}(?=(\d{3})+(?:\.\d|\b)))/","$1".' ', $product->credit_from)}} {{__('app.currency')}} {{__('frontend/catalog.to_month_short')}}</span></div>
                                <div class="product-controls">
                                    <button type="button" class="btn btn-success add-to-cart" data-product="{{$product->id}}">{{__('app.btn_buy')}}</button>
                                </div>
                            </div><!-- /.product -->
                        </div>
                    @endforeach
                </div><!-- /.row -->
            </div><!-- /.container -->
        </section><!-- /.news -->

        <script>
            $(window).on('load', function(){
                let tM = 0;
                $('.products.list .product-wrapper').each(function(){

                    let iH = $('.product-title', $(this)).height();
                    if(iH > tM) tM = iH;

                });
                $('.products .product .product-title').height(tM);
            });

        </script>
    @endif

    <div class="home my-5" style="background: transparent;">
        <section class="block registration">
            <div class="container">
                <div class="row">

                    <div class="col-12 col-lg-6 part buyer">
                        <div class="inner-wrap">
                            <div class="inner">
                                <div class="title">{{__('frontend/page_about.how_to_buy')}}</div>
                                <div class="text">{{__('frontend/index.registration_text_buyer')}}</div>
                            </div>
                            <a href="{{localeRoute('register')}}" class="btn btn-light btn-arrow">{{__('frontend/index.registration_btn_register')}}</a>
                        </div><!-- /.inner-wrap -->
                    </div><!-- ./part.buyer -->

                    <div class="col-12 col-lg-6 part partner">
                        <div class="inner-wrap">
                            <div class="inner">
                                <div class="title">{{__('frontend/index.registration_title_become_partner')}}</div>
                                <div class="text">{{__('frontend/index.registration_text_partner')}}</div>
                            </div>
                            <a href="{{localeRoute('partners.welcome')}}" class="btn btn-light btn-arrow">{{__('frontend/index.registration_btn_register')}}</a>
                        </div><!-- /.inner-wrap -->
                    </div><!-- ./part.partner -->

                </div><!-- /.row -->
            </div><!-- /.container -->
        </section><!-- /.registration -->
    </div>

@endsection
