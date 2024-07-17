<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title') | {{ config('app.name', 'Laravel') }}</title>

    <!-- Scripts -->
    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/axios.min.js') }}"></script>
    <script src="{{ asset('assets/js/moment.min.js') }}"></script>
    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/popper.min.js') }}"></script>
    <script src="{{ asset('assets/js/vue.min.js') }}"></script>
    <script src="{{ asset('assets/js/vendor.min.js') }}"></script>
    <script src="{{ asset('assets/js/app.min.js') }}"></script>

    @stack('js')

    <script>
        window.Laravel = {!! json_encode([
            'csrfToken' => csrf_token(),
            'baseUrl' => url('/'),
            'locale'    => app()->getLocale()
        ]) !!};

    </script>

    <!-- Styles -->

{{--    <link href="{{asset('assets/css/googleapis.css')}}" rel="stylesheet">--}}
{{--    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@200;300;400;600;700;900&display=swap" rel="stylesheet">--}}
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/vendor.min.css')}}" rel="stylesheet">
    <link href="{{ asset('assets/css/frontend.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/frontend-edited.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/generatedFontSizes.css') }}" rel="stylesheet">
</head>
<body>
    <div id="app" class="@yield('class')">

        @include('templates.common.parts.edited.auth-header')

        <main class="content">
            @if(Session::has('message'))
                <div class="container">
                    @foreach(Session::get('message') as $message)
                        <div class="alert alert-{{$message['type']}}"> {{$message['text']}} </div>
                    @endforeach
                </div>
            @endif

            @yield('content')
        </main><!-- /.content -->

{{--        @include('templates.common.parts.footer')--}}

    </div><!-- /#app -->
    @extends('templates.frontend.parts.login')
</body>
</html>
