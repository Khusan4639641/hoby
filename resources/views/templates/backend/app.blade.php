<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title') | {{ config('app.name', 'Laravel') }}</title>
    <script>
      window.globalApiToken = @json(Auth::user()->api_token)
    </script>
    @include('billing.bridges.i18n')
    <!-- Scripts -->
    <script src="{{ asset('assets/js/helpers.js') }}"></script>
    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/axios.min.js') }}"></script>
    <!-- polipop popup notifications scripts start -->
    <script src="{{ asset('assets/js/polipop.min.js') }}"></script>
    <!-- polipop popup notifications scripts end -->
    <script src="{{ asset('assets/js/moment.min.js') }}"></script>
    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/popper.min.js') }}"></script>
    <script src="{{ asset('assets/js/vue.min.js') }}"></script>

    <script src="{{ asset('assets/js/vendor.min.js') }}"></script>
    <script src="{{asset('assets/js/datepicker.min.js') }}"></script>
    <script src="{{asset('assets/js/datepicker.locale.'.app()->getLocale().'.js') }}"></script>

    <script src="{{ asset('assets/js/app.min.js') }}"></script>
    <script src="{{ asset('assets/js/chart.min.js') }}"></script>
    <script src="{{ asset('assets/js/chartjs-plugin-datalabels.min.js') }}"></script>
    <script src="{{ asset('assets/js/veeValidate.js') }}"></script>
    <script src="{{ asset('assets/js/veeValidateRules.js') }}"></script>
    <script src="{{ asset('assets/js/validationRules.js') }}"></script>


    @stack('js')

    <script>
        window.Laravel = {!! json_encode([
            'csrfToken' => csrf_token(),
            'baseUrl' => url('/'),
            'locale'    => app()->getLocale()
        ]) !!};
    </script>

    <!-- Styles -->
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@200;300;400;600;700;900&display=swap"
          rel="stylesheet">
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <!-- polipop popup notifications styles start -->
    <link href="{{ asset('assets/css/polipop.core.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/polipop.default.min.css') }}" rel="stylesheet">
    <!-- polipop popup notifications styles end -->
    <link href="{{ asset('assets/css/vendor.min.css')}}" rel="stylesheet">
    <link href="{{ asset('assets/css/datepicker.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/backend.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/backend-edited.css') }}" rel="stylesheet">


    <link href="{{ asset('assets/css/photoviewer.min.css') }}" rel="stylesheet">
    <script src="{{ asset('assets/js/photoviewer.min.js') }}"></script>

    <script src="{{ asset('assets/js/lottie-player.js') }}"></script>

    <link href="{{ asset('assets/css/select2.min.css') }}" rel="stylesheet">
    <script src="{{ asset('assets/js/select2.min.js') }}"></script>

    <link href="{{ asset('assets/css/select2-bootstrap4.min.css') }}" rel="stylesheet">



{{--    --}}
{{--    <link href="https://cdn.jsdelivr.net/npm/photoviewer@3.5.8/dist/photoviewer.min.css" rel="stylesheet">--}}
{{--    <script src="https://cdn.jsdelivr.net/npm/photoviewer@3.5.8/dist/photoviewer.min.js"></script>--}}

{{--    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>--}}
{{--    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />--}}
{{--    <link rel="stylesheet"--}}
{{--          href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@x.x.x/dist/select2-bootstrap4.min.css">--}}
{{--    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>--}}

    @stack('css')
</head>
<body>
<div id="mypolipop">
</div>
<div class="@yield('class')">
    {{--    @include('templates.common.parts.edited.header')--}}
    @yield('header')
    <main id="app" class="content">

        <div class="container-fluid px-0">
            <div class="
                    container-row
                    d-flex
                    {{ \Illuminate\Support\Facades\Auth::user()->getRoles()[0] ?? '' }}
                "
            >
                <div
                    class="
                        left-menu
                        bg-white
                        px-0
                        position-relative
                        active
                    "
                >
                    <aside class="@yield('aside-class')">
                        @yield('aside')
                    </aside>
                </div><!-- /.col-12 col-lg-3 -->

                <div class="p-md-4 p-0 w-100 min-vh-100" id="vue-app">
                    @if(Session::has('message'))
                        @foreach(Session::get('message') as $message)
                            <div class="alert alert-{{$message['type']}}"> {{$message['text']}} </div>
                        @endforeach
                    @endif

                    <div class="center">
                        @yield('content')
                    </div><!-- /.center -->
                </div><!-- /.col-12 col-lg-9 -->

            </div><!-- /.row -->
        </div><!-- /.container -->

    </main><!-- /.content -->

    @yield('mobile-bar')
    {{--@include('templates.common.parts.footer')--}}

</div><!-- /#app -->

<!-- notification settings  -->
<script src="{{ asset('assets/js/notifications.js') }}"></script>
<!-- Global error handler (depends on notifications.js)-->
<script src="{{ asset('assets/js/errorHandler.js') }}"></script>


<script>
// sidebar action (toggle)
const sidebar = document.querySelector('.left-menu');
const toggleButton = document.querySelector('#sidebar-toggle');
const content = document.querySelector('.content .container-fluid .container-row .bg-grey');
const body = document.querySelector('.content .center');

if (toggleButton) {
    toggleButton.addEventListener('click', () => {
        sidebar.classList.toggle('active');
        toggleButton.classList.toggle('active');
        body.classList.toggle('active')
    });
}
</script>
@stack('end')
</body>
</html>
