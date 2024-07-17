@extends('templates.backend.app')

@section('header')
    @include('templates.common.parts.edited.auth-header')
@endsection

@push('css')
    <link href="{{ asset('assets/css/panel.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/panel-edited.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/vue-multiselect.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/vue-treeselect.min.css') }}" rel="stylesheet">

@endpush

@push('js')
    <script src="{{ asset('assets/js/datatable-pagination-input-plugin.js') }}"></script>
    <script src="{{ asset('assets/js/vue-multiselect.min.js') }}"></script>
    <script src="{{ asset('assets/js/vue-treeselect.umd.min.js') }}"></script>
    <script src="{{ asset('assets/js/vuejs-paginate.js') }}"></script>
    <script src="{{ asset('assets/js/textMaskAddons.min.js') }}"></script>
    <script src="{{ asset('assets/js/AutoNumeric.min.js') }}"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function(event) {
            new AutoNumeric.multiple('.delay-mask', ['integerPos',{maximumValue: '999',minimumValue:'0',  modifyValueOnWheel:false, unformatOnSubmit: true}])
            new AutoNumeric.multiple('.percent-mask', ['numericPos',{maximumValue: '100',minimumValue:'0', suffixText:' %',  modifyValueOnWheel:false, unformatOnSubmit: true}])
            new AutoNumeric.multiple('.coefficient-mask', ['numericPos',{maximumValue: '1',minimumValue:'0',  modifyValueOnWheel:false, unformatOnSubmit: true}])
            new AutoNumeric.multiple('.integer-mask', ['integerPos',{modifyValueOnWheel:false, unformatOnSubmit: true, digitGroupSeparator:'' }])
            new AutoNumeric.multiple('.integer-mask-with-digit', ['integerPos',{modifyValueOnWheel:false, unformatOnSubmit: true, digitGroupSeparator:' ' }])
            new AutoNumeric.multiple('.currency-mask', ['dotDecimalCharCommaSeparator',{modifyValueOnWheel:false, digitGroupSeparator:' ', unformatOnSubmit: true}])
            new AutoNumeric.multiple('.lattitude-mask', ['float',{maximumValue: '90',minimumValue:'-90', suffixText:'°', decimalPlaces: 8, modifyValueOnWheel:false, unformatOnSubmit: true}])
            new AutoNumeric.multiple('.longitude-mask', ['float',{maximumValue: '180',minimumValue:'-180', suffixText:'°', decimalPlaces:8, modifyValueOnWheel:false, unformatOnSubmit: true}])

        });
        Vue.component('multiselect', VueMultiselect.Multiselect)
        Vue.component('treeselect', VueTreeselect.Treeselect)
        Vue.component('paginate', VuejsPaginate)
        const globalApiToken = @json(Auth::user()->api_token)

        Vue.directive('numberOnly',{
            bind: function(el) {
                el.handler = function() {
                    el.value = el.value.replace(/\D+/, '')
                }
                el.addEventListener('input', el.handler)
            },
            unbind: function(el) {
                el.removeEventListener('input', el.handler)
            }
        })
    </script>
@endpush

@section('aside')
    {{App\Http\Controllers\Web\Panel\ProfileController::card()}}

    {{App\Helpers\MenuHelper::render('panel', 'left')}}
@endsection


@section('content')

    <div class="center-header">

        <div class="title">
            @yield('center-header-prefix')

            <div class="d-flex flex-column">
                <h1>
                    @if(View::hasSection('h1'))
                        @yield('h1')
                    @else
                        @yield('title')
                        @yield('print_act')
                    @endif
                </h1>
                @if(View::hasSection('title_date'))
                    <span class="mb-0">{{__('billing/order.lbl_from')}} @yield('title_date')</span>
                @endif
            </div>

            <div class="title-right">
                @yield('center-header-control')
            </div>
        </div>

    </div><!-- /.content-header -->

    <div class="center-body">
        @yield('content')
    </div><!-- /.content-body -->

@overwrite
