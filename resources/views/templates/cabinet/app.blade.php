@extends('templates.backend.app')

@push('css')
    <link href="{{ asset('assets/css/cabinet.min.css') }}" rel="stylesheet">
@endpush

@section('aside')
    {{App\Http\Controllers\Web\Cabinet\ProfileController::card()}}

    {{App\Helpers\MenuHelper::render('cabinet', 'left')}}

    @include('templates.cabinet.parts.help')
@endsection


@section('content')

    <div class="center-header">

        @if(Auth::user()->status <= 1)
            <div class="alert alert-success">
                {!! __('cabinet/cabinet.txt_please_verify') !!}
                <a href="{{localeRoute('cabinet.profile.verify')}}" class="btn btn-outline-light">{{__('panel/buyer.btn_verify')}}</a>
            </div>
        @elseif(Auth::user()->status == 3)
            <div class="alert alert-warning">
                {!! __('cabinet/cabinet.txt_please_edit_verify', ['reason' => Auth::user()->verify_message]) !!}
                <a href="{{localeRoute('cabinet.profile.verify')}}" class="btn btn-warning">{{__('app.btn_edit')}}</a>
            </div>
        @elseif(Auth::user()->status == 8)
            <div class="alert alert-danger">
                {!! __('cabinet/cabinet.txt_you_blocked') !!}
            </div>
        @else
            {{App\Http\Controllers\Web\Cabinet\ProfileController::panel()}}
        @endif

        @if(View::hasSection('h1'))
            <div class="title">
                @yield('center-header-prefix')

                <h1>@yield('h1')</h1>

                <div class="title-right">
                    @yield('center-header-control')
                </div>
            </div>
        @endif

        @yield('center-header-custom')

    </div><!-- /.content-header -->

    <div class="center-body">
        @if(!View::hasSection('h1') && View::hasSection('title'))
            <h1>@yield('title')</h1>
        @endif
        @yield('content')
    </div><!-- /.content-body -->

@overwrite


@section('mobile-bar')
    @include('templates.cabinet.parts.mobile_bar')
@endsection
