@extends('templates.frontend.app')

@section('title', __('frontend/partner.header_our_partners'))
@section('class', 'partner list')

@section('content')

    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{localeRoute('home')}}">{{__('frontend/breadcrumbs.home')}}</a></li>
                <li class="breadcrumb-item active" aria-current="page">
                    {{__('frontend/breadcrumbs.partners')}}
                </li>
            </ol>
        </nav>

        <h1>@yield('title')</h1>

        @include('frontend.partner.parts.list')
    </div>
    <!-- /.container -->
@endsection
