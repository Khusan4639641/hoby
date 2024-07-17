@extends('templates.frontend.app')
@section('class', 'page '.request()->name)
@section('title', __('frontend/page_about.title'))


@section('content')
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{localeRoute('home')}}">{{__('frontend/breadcrumbs.home')}}</a></li>
                <li class="breadcrumb-item active" aria-current="page">
                    {{__('frontend/page_about.title')}}
                </li>
            </ol>
        </nav>

        <h1>{{__('frontend/page_about.h1')}}</h1>

        <div class="text">
            {!! __('frontend/page_about.text') !!}
        </div>
    </div><!-- /.container -->

    <div class="home my-5" style="background: transparent;">
        <section class="block registration">
            <div class="container">
                <div class="row">

                    <div class="col-12 col-lg-6 part buyer">
                        <div class="inner-wrap">
                            <div class="inner">
                                <div class="title">с</div>
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
