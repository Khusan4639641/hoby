@extends('templates.panel.app')

@section('title', __('reports.title'))

@section('content')

    <div>
        <style>
            .col-sm-3{
                animation-name: appear;
                animation-duration: 0.3s;
                animation-delay: 0.3s;
            }

            @keyframes appear {
                0% {left:0px; opacity: 0;}
                20% {left:10px;opacity: 0.25;}
                50% {left:15px;opacity: 0.5;}
                70% {left:20px;opacity: 0.75;}
                100% {left:0px;opacity: 1;}
            }
        </style>

        <div class="filtered" id="statistics">

            <a href="{{ localeRoute('panel.report-files.index') }}" style="margin-bottom: 20px; display: block;">Вернуться к списку</a>

            {{--<div v-if="messages.length">
                <div class="alert alert-success" v-for="message in messages">@{{ message }}</div>
            </div>
            <div class="alert alert-danger" v-for="item in errors.system">@{{ item }}</div>--}}

            <form method="POST" action="{{ localeRoute('panel.report-files.export') }}">
                @csrf
                <div class="filter">
                    <div class="row">
                        <div class="col-12 col-md-4">
                            <div class="form-group">
                                <label for="period">{{__('statistics.partner.period')}}</label>
                                <select id="period" name="type" class="form-control select-period">
                                    @foreach($report_periods as $key => $value)
                                        <option value="{{ $key }}">{{ $value }}</option>
                                    @endforeach
                                    {{--<option value="last_day">{{__('statistics.partner.last_day') }}</option>
                                    <option value="last_week">{{__('statistics.partner.last_week')}}</option>
                                    <option value="last_month">{{__('statistics.partner.last_month')}}</option>
                                    <option value="last_half_year">{{__('statistics.partner.last_half_year')}}</option>
                                    <option value="custom">{{__('statistics.partner.custom_period')}}</option>--}}
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-md-4" v-if="filter.period == 'custom'">
                            <div class="form-group">
                                <label for="date">{{__('statistics.partner.date')}}</label>
                                <input class="form-control" name="date" />
                                <br>
                                Формат: 10.01.2022,20.02.2022
                                {{--<date-picker
                                    id="date"
                                    v-on:change="checkDate"
                                    value-type="format"
                                    v-model="filter.date"
                                    type="date"
                                    format="DD.MM.YYYY"
                                    range="true"
                                    name="date"
                                    :class="(errors.date?' is-invalid':'') + 'range-100'"
                                    placeholder="{{ __('statistics.partner.date') }}">
                                </date-picker>
                                <div class="error" v-for="item in errors.date">@{{ item }}</div>--}}
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 col-md-4">
                            <div class="form-group">
                                <label for="report">Отчеты</label>
                                <select id="report" name="report" class="form-control select-report">
                                    @foreach($report_types as $key => $value)
                                        <option value="{{ $key }}">{{ $value['title'] }}</option>
                                    @endforeach
                                </select>
                                <br>
                                РАБОТАЮТ:
                                <br>
                                <br>
                                - OrdersCancelNewExport (Бухгалтерия c отмененными. Новая)
                                <br>
                                - PaymentsExport (Списания)
                                <br>
                                - HistoryExport (Пополнения)
                                <br>
                                - TransactionsExport (Транзакции)
                                <br>
                                - BonusExport (Начисление бонусов (контракты))
                                <br>
                                - BonusClientsExport (Начисление бонусов (клиенты))
                                <br>
                                - DetailedContractsExport (Детальные договора)
                                <br>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 col-md-8">
                            <button type="submit" class="btn btn-orange">Сформировать отчет</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{--<script>

        let statistics = new Vue({
            el: '#statistics',
            data: {
                errors: {},
                messages: [],
                filter: {
                    period: 'last_week',
                    date: [null, null],
                    company: '',
                    report: 0,
                    url: ''
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
                        this.errors.date = [];
                        this.errors.date.push('{{__('statistics.partner.date_empty')}}');
                    } else {
                        this.errors.date = null;
                        return true;
                    }
                    return false;

                },
                changePeriod: function () {

                },
                getReport(){
                    var url = this.filter.url+'?type='+this.filter.period;
                    //console.log(url)
                    if(this.filter.period=='custom' && this.filter.date[0] !== null){
                        //console.log(url +'&date='+this.filter.date)
                        url += '&date='+this.filter.date
                    }
                    if(url.length>24) window.open(url);
                },
                reportOnChange: function() {
                    var options = event.target.options
                    if (options.selectedIndex > -1) {
                        this.filter.url = options[options.selectedIndex].getAttribute('url');
                        //console.log(this.filter.url)
                    }
                }

            },
            created: function () {
            }
        });

    </script>--}}


@endsection()
