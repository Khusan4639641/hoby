@extends('templates.panel.app')

@section('title', __('reports.title'))
@section('content')
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

    <div>

        @if($access == 'finance' || $access == 'sales_finance' || $access == 'admin')

            <div class="filtered" id="statistics">
                <div v-if="messages.length">
                    <div class="alert alert-success" v-for="message in messages">@{{ message }}</div>
                </div>
                <div class="alert alert-danger" v-for="item in errors.system">@{{ item }}</div>
                <div class="filter">
                    <div class="row">
                        <div class="col-12 col-md-4">
                            <div class="form-group">
                                <label for="period">{{__('statistics.partner.period')}}</label>
                                <select id="period" name="period" v-model="filter.period" class="form-control select-period modified">
                                    <option value="last_day">{{__('statistics.partner.last_day') }}</option>
                                    <option value="last_week">{{__('statistics.partner.last_week')}}</option>
                                    <option value="last_month">{{__('statistics.partner.last_month')}}</option>
                                    <option value="last_half_year">{{__('statistics.partner.last_half_year')}}</option>
                                    <option value="custom">{{__('statistics.partner.custom_period')}}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-md-4" v-if="filter.period == 'custom'">
                            <div class="form-group">
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
                                    :disabled-date="disabledDates"
                                    :class="(errors.date?' is-invalid':'modified') + 'range-100'"
                                    placeholder="{{ __('statistics.partner.date') }}">
                                </date-picker>
                                <div class="error" v-for="item in errors.date">@{{ item }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 col-md-4">
                            <div class="form-group">
                                <label for="report">Отчеты</label>
                                <select id="report" name="report" v-model="filter.report" class="form-control select-report modified" @change="reportOnChange">
                                    <option selected disabled>Выбрать отчет</option>
                                    @foreach($reports as $report)
                                        <option value="{{ $report['name'] }}" url="{{localeRoute( 'panel.reports.'.$report['name'].'.export', $report['name']) }}">
                                            {{ $report['title'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                    </div>
                   
                    <div class="row" v-if="filter.report == 'comparativeDocument'">
                        <div class="col-12 col-md-4">
                            <div class="form-group">
                                <label for="report">ИНН <span class="text-red">*</span></label>
                                <input type="text" v-mask="'#########'" v-model="filter.inn" id="inn" name="inn" class="form-control modified">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 col-md-8">
                            <button v-on:click="getReport" :disabled="!filter.report || (filter.period == 'custom' && !filter.date[0]) || (filter.report == 'comparativeDocument' && filter.inn.length != 9)" type="submit" class="btn btn-orange">Скачать отчет {{--__('statistics.partner.btn_build_report')--}}</button>
                        </div>
                    </div>
                </div>

            </div>

        @endif

    </div>


    <script>

        let statistics = new Vue({
            el: '#statistics',
            data: {
                errors: {},
                messages: [],
                filter: {
                    period: 'last_week',
                    date: [null,null],
                    company: '',
                    report: '',
                    inn: '',
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
                disabledDates(date, currentValue){
                    if (moment(date).isAfter(new Date())) return true
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
                getReport(){
                    let url = `${this.filter.url}?type=${this.filter.period}`;
                    if(this.filter.period=='custom' && this.filter.date[0] !== null){
                        url = `${this.filter.url}?type=${this.filter.period}&date=${this.filter.date}`
                    }
                    if (this.filter.inn.length == 9) {
                        url+=`&inn=${this.filter.inn}`
                    }
                    if(url.length>24) window.open(url);
                },
                reportOnChange: function(event) {
                    this.filter.inn = ''
                    const options = event.target.options

                    if (options.selectedIndex > -1) {
                        this.filter.url = options[options.selectedIndex].getAttribute('url');
                    }
                }

            },
        });

    </script>


@endsection()
