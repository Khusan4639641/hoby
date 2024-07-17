@extends('templates.frontend.app')

@section('title', __('frontend/register.h1'))
@section('class', 'register buyer')

@section('content')

    <section class="block welcome">
        <div class="inner">
            <div class="container">
                <h1>{!!__('frontend/register.txt_how_to_buy')!!}</h1>
                <div class="text">{!! __('frontend/register.welcome_text') !!}</div>
                <div class="controls">
                    <a href="#" data-toggle="modal" data-target="#auth" class="btn btn-lg btn-light btn-arrow">{{__('frontend/register.btn_register')}}</a>
                    <a class="btn btn-outline-light btn-lg btn-arrow" data-fancybox="how-it-works" data-src="#video" href="javascript:;" class="play">{{__('frontend/register.txt_how_it_works')}}</a>
                </div>
            </div><!-- /.container -->
        </div><!-- /.inner -->
    </section><!-- /.welcome -->


    <section class="block how">
       <div class="container">
           <div class="h1">{{__('frontend/register.section_how_title')}}</div>
           <div class="description">{{__('frontend/register.section_how_text')}}</div>
           <div class="steps">
               <div class="row align-items-stretch">
                    <div class="col-12 col-sm-6 col-lg-3 step-wrapper">
                        <div class="step">
                            <div class="step-header">
                                <div class="number">1</div>
                                <img width="36px" height="auto" src="{{asset('images/icons/icon_passport_white.svg')}}">
                            </div>
                            <div class="step-body">
                                <div class="name">
                                    {!! __('frontend/register.section_how_step1_name') !!}
                                </div>
                                <div class="text">
                                    {!! __('frontend/register.section_how_step1_text') !!}
                                </div>
                            </div>
                        </div><!-- /.step -->
                    </div>

                   <div class="col-12 col-sm-6 col-lg-3 step-wrapper">
                       <div class="step">
                           <div class="step-header">
                               <div class="number">2</div>
                               <img width="60px" height="auto" src="{{asset('images/icons/icon_card_white.svg')}}">
                           </div>
                           <div class="step-body">
                               <div class="name">
                                   {!! __('frontend/register.section_how_step2_name') !!}
                               </div>
                               <div class="text">
                                   {!! __('frontend/register.section_how_step2_text') !!}
                               </div>
                           </div>
                       </div><!-- /.step -->
                   </div>

                   <div class="col-12 col-sm-6 col-lg-3 step-wrapper">
                       <div class="step">
                           <div class="step-header">
                               <div class="number">3</div>
                               <img width="36px" height="auto" src="{{asset('images/icons/icon_phone_white.svg')}}">
                           </div>
                           <div class="step-body">
                               <div class="name">
                                   {!! __('frontend/register.section_how_step3_name') !!}
                               </div>
                               <div class="text">
                                   {!! __('frontend/register.section_how_step3_text') !!}
                               </div>
                           </div>
                       </div><!-- /.step -->
                   </div>

                   <div class="col-12 col-sm-6 col-lg-3 step-wrapper">
                       <div class="step finish">
                           <div class="step-header">
                               <img height="40px" width="auto" src="{{asset('images/icons/icon_z_white.svg')}}">
                           </div>
                           <div class="step-body">
                               <div class="name">
                                   {!! __('frontend/register.section_how_step4_name') !!}
                               </div>
                               <div class="amount">
                                   9 000 000
                               </div>
                               <div class="currency">{{__('app.currency')}}</div>
                               <div class="text">
                                   {!! __('frontend/register.section_how_step4_text') !!}
                               </div>
                               <div class="go">
                                   <a href="#" data-toggle="modal" data-target="#auth">{{__('frontend/register.btn_register')}}</a>
                               </div>
                           </div>
                       </div><!-- /.step -->
                   </div>

               </div><!-- /.row -->
           </div><!-- /.steps -->
       </div><!-- /.container -->
    </section><!-- /.now -->

    <section class="block video">
        <div class="inner">
            <div class="container">
                <div class="h1">{{__('frontend/register.section_video_title')}}</div>
                <div class="description">{!! __('frontend/register.section_video_text') !!}</div>
                <a data-fancybox="video" data-src="#video" href="javascript:;" class="play">
                    <img src="{{asset('images/icons/icon_play_white.svg')}}"><br/>
                    {{__('frontend/register.section_video_play')}}
                </a>
            </div><!-- /.container -->
        </div><!-- /.inner -->
    </section><!-- /.video -->

    <div id="video">
        <video class="embed-responsive-item" controls="">
            <source src="{{asset('video/buyer.mp4')}}" type="video/mp4">
        </video>
    </div><!-- /#video -->

    <section class="block how mt-5">
        <div class="container">
            <div class="h1">{{__('frontend/register.section_how_to_buy_title')}}</div>
            <div class="steps">
                <div class="row align-items-stretch">
                    <div class="col-12 col-sm-6 col-lg-3 step-wrapper">
                        <div class="step">
                            <div class="step-header">
                                <div class="number">1</div>
                            </div>
                            <div class="step-body">
                                <div class="name">
                                    {!! __('frontend/register.section_how_to_buy_step1_name') !!}
                                </div>
                                <div class="text">
                                    {!! __('frontend/register.section_how_to_buy_step1_text') !!}
                                </div>
                            </div>
                        </div><!-- /.step -->
                    </div>

                    <div class="col-12 col-sm-6 col-lg-3 step-wrapper">
                        <div class="step">
                            <div class="step-header">
                                <div class="number">2</div>
                            </div>
                            <div class="step-body">
                                <div class="name">
                                    {!! __('frontend/register.section_how_to_buy_step2_name') !!}
                                </div>
                                <div class="text">
                                    {!! __('frontend/register.section_how_to_buy_step2_text') !!}
                                </div>
                            </div>
                        </div><!-- /.step -->
                    </div>

                    <div class="col-12 col-sm-6 col-lg-3 step-wrapper">
                        <div class="step">
                            <div class="step-header">
                                <div class="number">3</div>
                            </div>
                            <div class="step-body">
                                <div class="name">
                                    {!! __('frontend/register.section_how_to_buy_step3_name') !!}
                                </div>
                                <div class="text">
                                    {!! __('frontend/register.section_how_to_buy_step3_text') !!}
                                </div>
                            </div>
                        </div><!-- /.step -->
                    </div>

                    <div class="col-12 col-sm-6 col-lg-3 step-wrapper">
                        <div class="step">
                            <div class="step-header">
                                <div class="number">4</div>
                            </div>
                            <div class="step-body">
                                <div class="name">
                                    {!! __('frontend/register.section_how_to_buy_step4_name') !!}
                                </div>
                                <div class="text">
                                    {!! __('frontend/register.section_how_to_buy_step4_text') !!}
                                </div>
                            </div>
                        </div><!-- /.step -->
                    </div>

                </div><!-- /.row -->
            </div><!-- /.steps -->
        </div><!-- /.container -->
    </section><!-- /.now -->

    <section class="block contact">
        <div class="container">
            <div class="inner">
                <div class="h1 text-center">{{__('frontend/register.section_contact')}}</div>
                <div class="row">
                    <div class="col-12 col-md-6 order-2 order-md-1">
                        <img src="{{asset('images/media/girl_zm.png')}}">
                    </div>
                    <div class="col-12 col-md-6 text-center text-md-left order-1 order-md-2">
                        <div class="mb-4">
                            <div class="subtitle">{{__('frontend/register.section_contact_center')}}</div>
                            <div class="phone">
                                +99871 207-71-10
                            </div>
                        </div>

                        <div class="support">
                            <div class="subtitle">{{__('frontend/register.section_contact_support')}}</div>
                            <p>
                                <a href="{{localeRoute('faq.index')}}" class="btn btn-light">{{__('frontend/register.section_contact_btn')}}</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if(count($populateProducts) > 0)
        <section class="block populate-products list">
            <div class="container">
                <div class="h1 text-center">{{__('frontend/index.populate_products_title')}}</div>
                <div class="products row">
                    @foreach($populateProducts as $product)
                        <div class="item col-12 col-sm-6 col-md-4 col-lg-3 col-xl-2">

                                <div class="product">
                                    <a class="image" href="{{localeRoute('catalog.product.show', ['slug' => $product->locale->slug, 'id' => $product->id])}}">
                                        @if($product->images->first())
                                            <img src="{{$product->images->first()->preview}}" alt="">
                                        @else
                                            <div class="no-image"></div>
                                        @endif
                                    </a>
                                    <div class="price">{{preg_replace("/(\d{1,3}(?=(\d{3})+(?:\.\d|\b)))/","$1".' ', $product->price)}} {{__('app.currency')}}</div>
                                    <div class="product-title"><a href="{{localeRoute('catalog.product.show', ['slug' => $product->locale->slug, 'id' => $product->id])}}">{{$product->locale->title}}</a></div>
                                    {{--<div class="credit-from">{{__('frontend/catalog.credit_from')}} <span>{{preg_replace("/(\d{1,3}(?=(\d{3})+(?:\.\d|\b)))/","$1".' ', $product->credit_from)}} {{__('app.currency')}} {{__('frontend/catalog.to_month')}}</span></div>
                                    <div class="product-controls">
                                        <button type="button" class="btn btn-success add-to-cart" data-product="{{$product->id}}">{{__('app.btn_buy')}}</button>
                                    </div>--}}
                                </div>

                            </div><!-- /.item -->

                    @endforeach
                </div><!-- /.products -->
            </div><!-- /.container -->
        </section><!-- /.news -->


    @endif

@endsection
