@extends('templates.frontend.app')

@section('title', __('frontend/partner.welcome_title'))
@section('class', 'register partner')

@section('content')

    <section class="block welcome">
        <div class="inner">
            <div class="container">
                <h1>{!!__('frontend/partner.welcome_title')!!}</h1>
                <div class="text">{!! __('frontend/partner.welcome_text') !!}</div>
                <div class="controls">
                    <a href="{{localeRoute('partners.register')}}" class="btn btn-lg btn-light btn-arrow">{{__('frontend/partner.btn_register')}}</a>
                </div>
            </div><!-- /.container -->
        </div><!-- /.inner -->
    </section><!-- /.welcome -->


    <section class="block how">
        <div class="container">
            <div class="h1">{{__('frontend/partner.section_how_title')}}</div>
            <div class="steps">
                <div class="row align-items-stretch">
                    <div class="col-12 col-sm-6 col-lg-3 step-wrapper">
                        <div class="step">
                            <div class="step-header">
                                <img width="36px" height="auto" src="{{asset('images/icons/icon_user_circle.svg')}}">
                            </div>
                            <div class="step-body">
                                <div class="name">
                                    <div class="inner">
                                        <div class="number">1</div>
                                        {!! __('frontend/partner.section_how_step1_name') !!}
                                    </div>
                                </div>
                                <div class="text">
                                    {!! __('frontend/partner.section_how_step1_text') !!}
                                </div>
                            </div>
                        </div><!-- /.step -->
                    </div><!-- /.step-wrapper -->

                    <div class="col-12 col-sm-6 col-lg-3 step-wrapper">
                        <div class="step">
                            <div class="step-header">
                                <img width="36px" height="auto" src="{{asset('images/icons/icon_marketing_circle.svg')}}">
                            </div>
                            <div class="step-body">
                                <div class="name">
                                    <div class="inner">
                                        <div class="number">2</div>
                                        {!! __('frontend/partner.section_how_step2_name') !!}
                                    </div>
                                </div>
                                <div class="text">
                                    {!! __('frontend/partner.section_how_step2_text') !!}
                                </div>
                            </div>
                        </div><!-- /.step -->
                    </div><!-- /.step-wrapper -->

                    <div class="col-12 col-sm-6 col-lg-3 step-wrapper">
                        <div class="step">
                            <div class="step-header">
                                <img width="36px" height="auto" src="{{asset('images/icons/icon_graph_circle.svg')}}">
                            </div>
                            <div class="step-body">
                                <div class="name">
                                    <div class="inner">
                                        <div class="number">3</div>
                                        {!! __('frontend/partner.section_how_step3_name') !!}
                                    </div>
                                </div>
                                <div class="text">
                                    {!! __('frontend/partner.section_how_step3_text') !!}
                                </div>
                            </div>
                        </div><!-- /.step -->
                    </div><!-- /.step-wrapper -->

                    <div class="col-12 col-sm-6 col-lg-3 step-wrapper">
                        <div class="step">
                            <div class="step-header">
                                <img width="36px" height="auto" src="{{asset('images/icons/icon_world_circle.svg')}}">
                            </div>
                            <div class="step-body">
                                <div class="name">
                                    <div class="inner">
                                        <div class="number">4</div>
                                        {!! __('frontend/partner.section_how_step4_name') !!}
                                    </div>
                                </div>
                                <div class="text">
                                    {!! __('frontend/partner.section_how_step4_text') !!}
                                </div>
                            </div>
                        </div><!-- /.step -->
                    </div><!-- /.step-wrapper -->

                </div><!-- /.row -->
            </div><!-- /.steps -->
        </div><!-- /.container -->
    </section><!-- /.now -->

    <div class="container">
        <section class="block video">
            <div class="inner">
                <div class="h1">{{__('frontend/partner.section_video_title')}}</div>
                <div class="description">{!! __('frontend/partner.section_video_text') !!}</div>
                <a data-fancybox="video" data-src="#video" href="javascript:;" class="play">
                    <img src="{{asset('images/icons/icon_play_white.svg')}}"><br/>
                    {{__('frontend/partner.section_video_play')}}
                </a>
            </div><!-- /.inner -->
        </section><!-- /.video -->
    </div><!-- /.container -->

    <div id="video">
        <video class="embed-responsive-item" controls="">
            <source src="{{asset('video/partner.mp4')}}" type="video/mp4">
        </video>
    </div><!-- /#video -->

    <!-- #todo: Блок магазинов партнеров -->
    <section class="partner list">
        <div class="container">
            <div class="h1">{{__('frontend/partner.header_our_partners')}}</div>
            @include('frontend.partner.parts.list')

            <div class="text-center mt-4">
                <a href="{{localeRoute('partners.index')}}" class="btn btn-success btn-lg btn-arrow">{{__('frontend/partner.btn_all_partners')}}</a>
            </div>
        </div><!-- /.container -->
    </section>
    <!-- /.partner list -->

    <!-- #todo: Блок с картой -->

@endsection
