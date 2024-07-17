@extends('templates.panel.app')

@section('title', __('panel/menu.report_paymnets_title'))


@section('content')

    <style>
        .bold{
            font-weight: bold;
        }
        /*td.padding_left{
            padding-left: 50px;
        }
        td.gray{
            color: #828282;
        } */

        .hide{
            display: none;
        }
        .show{
            cursor: pointer;
            //text-decoration: underline;
        }

        .icon.open{
            -webkit-transform: rotate(90deg);
            -moz-transform: rotate(90deg);
            -o-transform: rotate(90deg);
            -ms-transform: rotate(90deg);
            transform: rotate(90deg);
        }
        .icon{
            width: 24px;
            height: 24px;
            transform: rotate(0);
        }
        .dropdown-container{
            overflow-y: auto;
            min-height:250px;
            max-height: 500px;"
        }

    </style>

    <div class="catalog category" id="report">

        <div class="filtered row">

            <div class="filter" >

                <div class="row">

                    <div class="col-12">
                        <div class="form-group">
                            <label for="period">{{__('statistics.partner.period')}}</label>
                            <select id="period" name="period" v-model="filter.period" class="form-control select-period" v-on:change="changePeriod">
                                <option value="last_day">{{__('statistics.partner.last_day') }}</option>
                                <option value="last_week">{{__('statistics.partner.last_week')}}</option>
                                <option value="last_month">{{__('statistics.partner.last_month')}}</option>
                                <option value="last_half_year">{{__('statistics.partner.last_half_year')}}</option>
                                <option value="custom">{{__('statistics.partner.custom_period')}}</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-12" v-if="filter.period == 'custom'">
                        <div class="form-group" >
                            <label for="date">{{__('statistics.partner.date')}}</label>
                            <date-picker
                                id="date"
                                v-on:change="checkDate"
                                value-type="format"
                                v-model="filter.date"
                                type="date"
                                format="DD.MM.YYYY"
                                range="true"
                                name="date"
                                :class="(errors?' is-invalid':'') + 'range-100'"
                                placeholder="{{ __('statistics.partner.date') }}">
                            </date-picker>
                            <div class="alert alert-danger alert-dismissible fade show" v-for="error in errors">
                                @{{ error }}
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <button v-on:click="getReport" type="submit" class="btn btn-orange" style="margin-top:20px;">{{__('app.btn_continue')}}</button>
                    </div>

                </div>

            </div>

        </div>

        <table class="table table-responsive-md table-hover">
            <tbody>
            @php
                $cnt = 0;
                $sum = 0;
            @endphp
            @if($payment_header && $payment_data)

                    {{ $payment_header }}
                    {{ $payment_data }}
                    {{ $payment_sum }}
            @endif

            </tbody>
        </table>


    </div>

    <script>

        let report = new Vue({
            el: '#report',
            data: {
                errors: [],
                messages: [],
                filter: {
                    period: '{{ $request->type ?? 'last_day' }}',
                    date: [], // [ '{{-- $date[0]}}','{{$date[1] --}}' ],
                    company: '',
                    report: 0,
                    url: '{{ localeRoute( 'panel.buyer.payments' ) }}'
                },
                parts: {},
                params: {},
                results: false,
                loading: false,
                api_token: '{{Auth::user()->api_token}}',
            },
            methods: {

                buildParameters: function() {
                    this.params = {}

                    this.params.api_token = this.api_token;
                    this.params.period = this.filter.period;
                    this.params.filter = true;
                    if(this.filter.period === "custom" && this.filter.date[0] !== null)
                        this.params.date = this.filter.date;
                    if(this.filter.company !== '')
                        this.params.company_id = this.filter.company;

                },
                checkDate: function (){
                    if(this.filter.date[0] === null){
                        this.errors = [];
                        this.errors.push( '{{__('statistics.partner.date_empty')}}');
                    } else {
                        this.errors = [];
                        return true;
                    }
                    return false;

                },
                changePeriod: function () {

                },
                getReport(){
                    var url = this.filter.url+'?type='+this.filter.period;
                    if(this.filter.period=='custom'){
                        if(this.filter.date[0] !== null) {
                            url += '&date=' + this.filter.date
                            window.location.href = url;
                            return true;
                        }else{
                            //this.errors = [];
                            this.errors.push('{{__('statistics.partner.date_empty')}}');
                            report.$forceUpdate();
                        }
                    }else {
                        if (url.length > 24) window.location.href = url;
                    }

                },

            },
            created: function () {
            }
        });
        $('.show').click(function () {
            if($(this).has('hide')) {
                $(this).find('.icon').toggleClass('open');
                $(this).parent().find('tr.'+$(this).data('item')).toggleClass('hide');
            }
        })

    </script>

@endsection
