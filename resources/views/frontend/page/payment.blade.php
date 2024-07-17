@extends('templates.frontend.app')
@section('class', 'page '.request()->name)
@section('title', __('frontend/page_payment.title'))


@section('content')
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{localeRoute('home')}}">{{__('frontend/breadcrumbs.home')}}</a></li>
                <li class="breadcrumb-item active" aria-current="page">
                    {{__('frontend/page_payment.title')}}
                </li>
            </ol>
        </nav>

        <h1>{{__('frontend/page_payment.h1')}}</h1>
        <div class="description">{{__('frontend/page_payment.description')}}</div>


        <section class="block how">
            <div class="container">
                <div class="steps">
                    <div class="row align-items-stretch">
                        <div class="col-12 col-sm-6 col-lg-4 step-wrapper">
                            <div class="step">
                                <div class="step-header">
                                    <div class="number">1</div>
                                    <img width="60px" height="auto" src="{{asset('images/icons/icon_user_circle.svg')}}">
                                </div>
                                <div class="step-body">
                                    <div class="name">
                                        <div class="inner">

                                            {!! __('frontend/page_payment.section_how_step1_name') !!}
                                        </div>
                                    </div>
                                    <div class="text">
                                        {!! __('frontend/page_payment.section_how_step1_text') !!}
                                    </div>
                                </div>
                            </div><!-- /.step -->
                        </div><!-- /.step-wrapper -->

                        <div class="col-12 col-sm-6 col-lg-4 step-wrapper">
                            <div class="step">
                                <div class="step-header">
                                    <div class="number">2</div>
                                    <img width="60px" height="auto" src="{{asset('images/icons/icon_card_white.svg')}}">
                                </div>
                                <div class="step-body">
                                    <div class="name">
                                        <div class="inner">

                                            {!! __('frontend/page_payment.section_how_step2_name') !!}
                                        </div>
                                    </div>
                                    <div class="text">
                                        {!! __('frontend/page_payment.section_how_step2_text') !!}
                                    </div>
                                </div>
                            </div><!-- /.step -->
                        </div><!-- /.step-wrapper -->

                        <div class="col-12 col-sm-6 col-lg-4 step-wrapper">
                            <div class="step">
                                <div class="step-header">
                                    <div class="number">3</div>
                                    <img width="36px" height="auto" src="{{asset('images/icons/icon_phone_white.svg')}}">
                                </div>
                                <div class="step-body">
                                    <div class="name">
                                        <div class="inner">

                                            {!! __('frontend/page_payment.section_how_step3_name') !!}
                                        </div>
                                    </div>
                                    <div class="text">
                                        {!! __('frontend/page_payment.section_how_step3_text') !!}
                                    </div>
                                </div>
                            </div><!-- /.step -->
                        </div><!-- /.step-wrapper -->



                    </div><!-- /.row -->
                </div><!-- /.steps -->
            </div><!-- /.container -->
        </section><!-- /.now -->


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

    </div>
@endsection
