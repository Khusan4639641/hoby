<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{__('tickets.title')}}</title>

    <!-- Scripts -->
    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/axios.min.js') }}"></script>
    <script src="{{ asset('assets/js/moment.min.js') }}"></script>
    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/popper.min.js') }}"></script>
    <script src="{{ asset('assets/js/vue.min.js') }}"></script>
    <script src="{{ asset('assets/js/vendor.min.js') }}"></script>
    <script src="{{asset('assets/js/datepicker.min.js') }}"></script>
    <script src="{{asset('assets/js/datepicker.locale.'.app()->getLocale().'.js') }}"></script>
    <script src="{{ asset('assets/js/app.min.js') }}"></script>

    @stack('js')


    <!-- Styles -->
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@200;300;400;600;700;900&display=swap" rel="stylesheet">
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/vendor.min.css')}}" rel="stylesheet">
    <link href="{{ asset('assets/css/datepicker.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/backend.min.css') }}" rel="stylesheet">

    @if($u->hasRole('buyer'))
        <link href="{{ asset('assets/css/cabinet.min.css') }}" rel="stylesheet">
    @endif
    @if($u->hasRole('partner'))
        <link href="{{ asset('assets/css/billing.min.css') }}" rel="stylesheet">
    @endif
    @if($u->hasRole('employee'))
        <link href="{{ asset('assets/css/panel.min.css') }}" rel="stylesheet">
    @endif

</head>
<body>
<div class="tickets">
    @include('templates.common.parts.header')

    <main id="app" class="content">

        <div class="container">
            <div class="row">
                <div class="col-12 col-lg-3">
                    <aside>
                        @if($u->hasRole('partner') && Session::get('role') == 'partner')
                            {{-- billing --}}
                            {{App\Http\Controllers\Web\Billing\ProfileController::card()}}
                            <a href="{{localeRoute('billing.orders.create')}}" class="btn btn-primary btn-plus">{{__('billing/order.btn_create_order')}}</a>
                            {{App\Helpers\MenuHelper::render('billing', 'left')}}
                        @endif
                        @if($u->hasRole('buyer') && Session::get('role') == 'buyer')
                            {{-- cabinet --}}
                            {{App\Http\Controllers\Web\Cabinet\ProfileController::card()}}
                            {{App\Helpers\MenuHelper::render('cabinet', 'left')}}
                            @include('templates.cabinet.parts.help')
                        @endif
                        @if($u->hasRole('employee') && Session::get('role') == 'employee')
                            {{-- panel --}}
                            {{App\Http\Controllers\Web\Panel\ProfileController::card()}}
                            {{App\Helpers\MenuHelper::render('panel', 'left')}}
                        @endif
                    </aside>
                </div><!-- /.col-12 col-lg-3 -->

                <div class="col-12 col-lg-9 pl-lg-0">
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
@yield('footer')
</body>
</html>

