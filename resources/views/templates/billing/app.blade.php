@extends('templates.backend.app')

@section('header')
    @include('templates.common.parts.edited.header')
@endsection

@push('css')
    <link href="{{ asset('assets/css/billing-edited.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/vue-multiselect.min.css') }}" rel="stylesheet">
@endpush

@push('js')
    <script> 
        const globalApiToken = @json(Auth::user()->api_token)
    </script>
    <script src="{{ asset('assets/js/vue-multiselect.min.js') }}"></script>
    <script src="{{ asset('assets/js/frag.js') }}"></script>
    @include('billing.order.parts.components.recursiveMultiselect')
    <script>
        
        Vue.component('fragment', Frag.Fragment)
        Vue.component('multiselect', VueMultiselect.Multiselect)
    </script>
@endpush

@section('aside')

    @include('billing.parts.left-menu')

    {{--    <div class="customer-support d-flex">--}}
    {{--        <img class="mr-3" src="{{ asset('assets/icons/customer-support.svg') }}" alt="">--}}
    {{--        <div>--}}
    {{--            {!! __('cabinet/cabinet.lbl_call_center')!!}--}}
    {{--            <br>--}}
    {{--            <span class="font-weight-normal">--}}
    {{--                    {{\Illuminate\Support\Facades\Config::get('test.help_phone')}}--}}
    {{--                </span>--}}
    {{--        </div>--}}
    {{--    </div>--}}
    <div class="instruction">
        <a
            class="d-flex align-items-center"
            href="{{ asset('docs/test-instruction.pdf') }}"
            role="button"
            target="_blank"
            {{--            data-toggle="tooltip"--}}
            {{--            data-placement="right"--}}
            {{--            title="{!! __('cabinet/cabinet.lbl_instruction')!!}"--}}
        >
        <span class="mr-3">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path
                d="M15.87 6.22606L7.741 14.3551C6.96 15.1361 6.96 16.4021 7.741 17.1831C8.522 17.9641 9.788 17.9641 10.569 17.1831L18.697 9.05506C20.259 7.49306 20.259 4.96006 18.697 3.39806C17.135 1.83606 14.602 1.83606 13.04 3.39806L4.915 11.5231C2.57 13.8681 2.57 17.6701 4.915 20.0151C7.26 22.3601 11.062 22.3601 13.407 20.0151L20.589 12.8331"
                stroke="#1E1E1E" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </span>

            {{--        <img class="mr-3" src="{{ asset('assets/icons/paperclip.svg') }}" alt="">--}}
            <div class="instruction_label">
                {!! __('cabinet/cabinet.lbl_instruction')!!}
            </div>
        </a>

        <a role="button" id="sidebar-toggle" class="d-flex align-items-center">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd"
                      d="M5 3C3.89543 3 3 3.89543 3 5V19C3 20.1046 3.89543 21 5 21H19C20.1046 21 21 20.1046 21 19V5C21 3.89543 20.1046 3 19 3H5ZM9.1427 5.5708C8.59042 5.5708 8.1427 6.01852 8.1427 6.5708V17.4279C8.1427 17.9802 8.59042 18.4279 9.1427 18.4279H9.71413C10.2664 18.4279 10.7141 17.9802 10.7141 17.4279V6.5708C10.7141 6.01852 10.2664 5.5708 9.71413 5.5708H9.1427Z"
                      fill="#1E1E1E" />
            </svg>

            <div class="instruction_label">
                {!! __('billing/menu.close_menu')!!}
            </div>
        </a>
    </div>



    {{--    {{App\Helpers\MenuHelper::render('billing', 'left')}}--}}
@endsection

@section('aside-class', 'sticky')

@section('mobile-bar')
    @include('templates.billing.parts.mobile_bar')
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

    <div class="center-body position-relative">
        @yield('content')
    </div><!-- /.content-body -->
@overwrite
