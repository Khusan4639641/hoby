@extends('templates.backend.app')

@push('css')
    <link href="{{ asset('assets/css/billing.min.css') }}" rel="stylesheet">
@endpush

@section('aside')

@endsection


@section('content')

    <div class="center-header">
        <div class="title">
            @yield('center-header-prefix')
            <h1>
                @if(View::hasSection('h1'))
                    @yield('h1')
                @else
                    @yield('title')
                @endif

                @yield('title-info')
            </h1>

            @if(View::hasSection('center-header-control'))
                <div class="title-right">
                    @yield('center-header-control')
                </div>
            @endif
        </div><!-- /.title -->
    </div><!-- /.content-header -->

    <div class="center-body">
        @yield('content')
    </div><!-- /.content-body -->
@overwrite
