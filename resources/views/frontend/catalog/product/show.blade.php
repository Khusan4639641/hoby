@extends('templates.frontend.app')
@section('class', 'catalog product show')

@section('title', $product->locale->title)

@section('content')

    <div class="container">
        <div class="row no-gutters">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{localeRoute('home')}}">{{__('frontend/breadcrumbs.home')}}</a></li>
                        <li class="breadcrumb-item" aria-current="page">
                            <a href="{{localeRoute('catalog.index')}}">{{__('frontend/breadcrumbs.catalog')}}</a>
                        </li>
                        @if($product->categories->count() > 0)
                            @if($product->categories[0]->language != null)
                                <li class="breadcrumb-item" aria-current="page">
                                    <a href="{{localeRoute('catalog.category.show', ['slug' => $product->categories[0]->language->slug, 'id' => $product->categories[0]->id])}}">{{$product->categories[0]->language->title}}</a>
                                </li>
                            @endif
                        @endif
                        <li class="breadcrumb-item active" aria-current="page">
                            {{$product->locale->title}}
                        </li>
                    </ol>
                </nav>
            </div>

            <div class="col-sm-12 col-md-7 col-lg-8 col-xl-9">
                <div class="block info">
                    <div class="row">
                        <div class="col-12 col-lg-12 col-xl-7 mb-4 mb-xl-0">
                            <div class="gallery">
                                @if(count($product->images) > 0)
                                    <div class="image-container">
                                        <img src="/storage/{{$product->images->first()->path}}" alt="" class="">
                                    </div>
                                    @if(count($product->images) > 1)
                                        <div class="owl-carousel">
                                            @foreach($product->images as $image)
                                                <img src="/storage/{{$image->path}}" alt="" class="" data-path="/storage/{{$image->path}}">
                                            @endforeach
                                        </div>
                                    @endif
                                @else

                                @endif
                            </div>
                        </div>
                        <div class="col-12 col-lg-12 col-xl-5">
                            <h1>{{$product->locale->title}}</h1>
                            @if($product->locale->preview_text != null && $product->locale->preview_text != '-')
                                <div class="preview-text">{!! $product->locale->preview_text !!}</div>
                            @else
                                @php
                                    $i = 0;
                                @endphp

                                <table class="table short-fields">
                                    @foreach($product['category_fields'] as $field)


                                        @if($product->getFieldAttr($field->id) != null && $i < 5)
                                            <tr>
                                                <td>{{$field->title}}</td>
                                                <td>{{$product->getFieldAttr($field->id)}}</td>
                                            </tr>

                                            @php
                                                $i++;
                                            @endphp
                                        @endif


                                    @endforeach
                                </table>
                            @endif

                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-md-5 col-lg-4 col-xl-3">
                <div class="block sidebar">
                    <div>{{__('frontend/catalog.product_price')}}</div>
                    <div class="price">{{preg_replace("/(\d{1,3}(?=(\d{3})+(?:\.\d|\b)))/","$1".' ', $product->price)}} {{__('frontend/catalog.sum')}}</div>

                    @if($plans)
                        <div class="mt-4">{{__('frontend/catalog.product_price_credit')}}</div>
                        <table class="table">
                            @foreach($plans as $month => $percent)
                                <tr>
                                    <td>
                                        {{$month}} {{($month == 3?__('app.months_1'):__('app.months'))}}
                                    </td>
                                    <td>
                                        {{__('frontend/catalog.lbl_from')}} {{preg_replace("/(\d{1,3}(?=(\d{3})+(?:\.\d|\b)))/","$1".' ', round(($product->price + $product->price*($percent/100))/$month, 2))}}  {{__('app.currency')}}
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    @endif

                    <div>
                        <a class="btn btn-success btn-arrow add-to-cart" data-product="{{$product->id}}">{{__('frontend/catalog.btn_add_to_cart')}}</a>
                    </div>
                </div><!-- /.sidebar -->

                <div class="block sidebar credit">
                    <div class="title">{{__('frontend/catalog.zcoin_bonus')}}</div>
                    <div class="zcoin">
                        <div class="amount">{{Config::get('test.zcoin')['bonus']}} zCoin</div>
                        <div class="text">{{__('frontend/catalog.credit_text')}}</div>
                    </div>
                    <div class="for-payment">{{__('frontend/catalog.zcoin_bonus_for_payment')}}</div>
                </div><!-- /.sidebar -->
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="block partner">
                    <div class="logo-name">
                        <div class="logo">
                            @if(isset($product->partner->company->logo))
                                <img src="{{$product->partner->company->logo->preview}}" alt="Logo">
                            @else
                                <img class="no-image" src="{{asset('/images/media/noimage.svg')}}" alt="no-image">
                            @endif
                        </div>
                        <div class="name">{{$product->partner->company->brand??$product->partner->company->name}}</div>
                    </div>

                    <div class="description">
                        {!! $product->partner->company->short_description!!}
                    </div>

                    <div class="link">
                        <a href="{{localeRoute('partners.show', $product->partner->company->id)}}">{{__('frontend/catalog.partner_all_goods')}} <img src="{{asset('/images/icons/icon_arrow_green_circle.svg')}}" alt=""></a>
                    </div>

                </div><!-- /.partner -->

                <div class="block mt-4">
                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active" id="home-tab" data-toggle="tab" href="#home" role="tab" aria-controls="home" aria-selected="true">{{__('frontend/catalog.product_description')}}</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="profile-tab" data-toggle="tab" href="#profile" role="tab" aria-controls="profile" aria-selected="false">{{__('frontend/catalog.product_fields')}}</a>
                        </li>
                    </ul>
                    <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
                            <div class="detail-text">{!! $product->locale->detail_text !!}</div>
                        </div>
                        <div class="tab-pane fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                            <table class="table">
                                @foreach($product['category_fields'] as $field)


                                    @if($product->getFieldAttr($field->id) != null)
                                        <tr>
                                            <td>{{$field->title}}</td>
                                            <td>{{$product->getFieldAttr($field->id)}}</td>
                                        </tr>
                                    @endif

                                @endforeach
                            </table>
                        </div>
                    </div>


                </div>


                <div class="mt-4">
                    @if(count($relatedProducts) > 0)
                        <h3 class="title">{{__('frontend/catalog.related_products_title')}}</h3>
                        <section class="mt-2 related-products list">
                            <div class="products">
                                <div class="owl-carousel">
                                    @foreach($relatedProducts as $product)
                                        <div class="item">
                                            <div class="product">
                                                <a class="image" href="{{localeRoute('catalog.product.show', ['slug' => $product->locale->slug, 'id' => $product->id])}}">
                                                    @if($product->images->first())
                                                        <img src="{{$product->images->first()->preview}}" alt="">
                                                    @else
                                                        <div class="no-image"></div>
                                                    @endif
                                                </a>
                                                <div class="price">{{$product->price}} {{__('frontend/catalog.sum')}}</div>
                                                <div class="product-title"><a href="{{localeRoute('catalog.product.show', ['slug' => $product->locale->slug, 'id' => $product->id])}}">{{$product->locale->title}}</a></div>
                                            </div>
                                        </div><!-- /.item -->
                                    @endforeach
                                </div>

                            </div><!-- /.products -->
                        </section>
                    @endif
                </div>
            </div>
        </div>

    </div>

    <script>
        $(document).ready(function(){

            $('.gallery .owl-carousel').owlCarousel({
                autoPlay: false, //Set AutoPlay to 3 seconds
                responsive:{
                    0:{
                        items:4
                    },
                    1000:{
                        items:5
                    }
                },
                lazyLoad : true,
                nav: true,
                dots: false,
                rewind: true,
                mouseDrag:false,
                pagination:false,
                onInitialized: function (event){

                    $('.gallery .owl-item:first-child').addClass('selected');

                    $('.gallery .owl-item').click(function (){
                        let path = $('img', this).data('path');

                        $('.gallery .owl-item').removeClass('selected');
                        $(this).addClass('selected');

                        $('.gallery .image-container img').attr('src', path);
                    });
                }
            });

            $('.related-products .owl-carousel').owlCarousel({
                margin: 15,
                dots: false,
                nav: false,
                lazyLoad : true,
                mouseDrag:false,
                pagination:false,
                responsive:{
                    0:{
                        items:1,
                        rewind: true,
                    },
                    600:{
                        items:2
                    },
                    800:{
                        items:4
                    },
                    1000:{
                        items:6,
                        rewind: false
                    }
                }
            });

            /*$('.customNextBtn').click(function() {
                owl.trigger('next.owl.carousel');
            });

            $('.customPrevBtn').click(function() {
                owl.trigger('prev.owl.carousel');
            });*/

        })
    </script>

@endsection
