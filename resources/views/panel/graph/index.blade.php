@extends('templates.panel.app')

@section('title', __('reports.title'))

@section('content')

    <div>
        <style>
            .col-sm-3 {
                animation-name: appear;
                animation-duration: 0.3s;
                animation-delay: 0.3s;
            }

            @keyframes appear {
                0% {
                    left: 0px;
                    opacity: 0;
                }
                20% {
                    left: 10px;
                    opacity: 0.25;
                }
                50% {
                    left: 15px;
                    opacity: 0.5;
                }
                70% {
                    left: 20px;
                    opacity: 0.75;
                }
                100% {
                    left: 0px;
                    opacity: 1;
                }
            }
        </style>

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
                                <select id="period" name="period" v-model="filter.period"
                                        class="form-control select-period" v-on:change="changePeriod">
                                    <option value="last_day">{{__('statistics.partner.last_day') }}</option>
                                    <option value="last_7_days">{{__('statistics.partner.last_7_days')}}</option>
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
                                    :class="(errors.date?' is-invalid':'') + 'range-100'"
                                    placeholder="{{ __('statistics.partner.date') }}">
                                </date-picker>
                                <div class="error" v-for="item in errors.date">@{{ item }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 col-md-4">
                            <div class="form-group">
                                <label for="interval">{{ __('Интервал') }}</label>
                                <select id="interval" name="interval" v-model="filter.interval"
                                        class="form-control select-interval">
                                    <option value="1">{{ __('1 день') }}</option>
                                    <option value="3">{{ __('3 дня') }}</option>
                                    <option value="5">{{ __('5 дней') }}</option>
                                    <option value="7">{{ __('7 дней') }}</option>
                                    <option value="10">{{ __('10 дней') }}</option>
                                    <option value="week">{{ __('Неделя') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 col-md-4">
                            <div class="form-group">
                                <label for="report">{{ __('Графики') }}</label>
                                <select id="report" name="report" v-model="filter.report"
                                        class="form-control select-report" @change="reportOnChange()">
                                    <option value=1
                                            url="{{localeRoute( 'panel.graph.contracts' ) }}">{{ __('statistics.partner.income_from_contracts') }}
                                    </option>
                                    <option value=2
                                            url="{{localeRoute( 'panel.graph.clients' ) }}">{{ __('statistics.partner.amounts_of_contracts') }}
                                    </option>
                                    <option value=3
                                            url="">{{ __('statistics.partner.amounts_of_contracts_by_vendor') }}
                                    </option>
                                </select>
                            </div>
                        </div>

                    </div>
                    <div v-if="!isVendorsHidden" class="row">
                        <div class="col-12 col-md-4">
                            <div class="form-group">
                                <label for="partner">{{ __('Вендора') }}</label>
                                <select id="partner" name="partner" v-model="filter.partner"
                                        class="form-control select-partner" @change="partnerOnChange()">
                                    <option url="" value="0"> -</option>
                                    @foreach($companies as $company)
                                        <option url="{{localeRoute( 'panel.graph.client', $company->id ) }}"
                                                value="{{ $company->id }}">{{ $company->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                    </div>
                    <div class="row">
                        <div class="col-12 col-md-8">
                            <button v-on:click="getReport" type="submit"
                                    class="btn btn-orange">{{ __('statistics.partner.show_graph') }}</button>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <canvas id="graphicChart" width="400" height="100"></canvas>
                    </div>
                </div>

            </div>

        @endif

    </div>


    <script>

        let statistics = new Vue({
            el: '#statistics',
            data: {
                chart: {},
                errors: {},
                messages: [],
                isVendorsHidden: true,
                vendorUrl: '',
                filter: {
                    period: 'last_week',
                    date: [null, null],
                    company: '',
                    report: 0,
                    partner: 0,
                    interval: '',
                    url: ''
                },
                parts: {},
                params: {},
                results: false,
                loading: false,
                api_token: '{{Auth::user()->api_token}}',
            },
            methods: {

                buildParameters: function () {
                    this.params = {}

                    this.params.api_token = this.api_token;
                    this.params.period = this.filter.period;
                    this.params.interval = this.filter.interval;
                    this.params.filter = true;
                    if (this.filter.period === "custom" && this.filter.date[0] !== null)
                        this.params.date = this.filter.date;
                    if (this.filter.company !== '')
                        this.params.company_id = this.filter.company;

                },
                checkDate: function () {
                    if (this.filter.date[0] === null) {
                        this.errors.date = [];
                        this.errors.date.push('{{__('statistics.partner.date_empty')}}');
                    } else {
                        this.errors.date = null;
                        return true;
                    }
                    return false;

                },
                getReport() {
                    var url = this.filter.url; //+'?type='+this.filter.period;
                    filter = true;
                    if (this.filter.period == 'custom' && this.filter.date[0] == null) {
                        // +'&date='+this.filter.date)
                        //url += '&date='+this.filter.date
                        filter = false;
                        //return false;
                    }
                    if (url.length > 24 && filter) {
                        // window.open(url);
                        axios.post(this.filter.url,
                            {
                                api_token: this.api_token,
                                date: this.filter.date ?? '',
                                type: this.filter.period,
                                interval: this.filter.interval
                            },
                            {
                                headers: {'Content-Language': '{{app()->getLocale()}}'}
                            }
                        ).then(result => {
                            if (result.data.status === 'success') {
                                drawGraphic(this.chart, result.data.data);
                            }
                        });

                    }

                },
                changePeriod: function () {

                },
                reportOnChange: function () {
                    var options = event.target.options
                    this.isVendorsHidden = parseInt(options[options.selectedIndex].getAttribute('value')) !== 3;
                    if (options.selectedIndex > -1) {
                        this.filter.url = options[options.selectedIndex].getAttribute('url') !== '' ? options[options.selectedIndex].getAttribute('url') : this.vendorUrl;
                    }
                },
                partnerOnChange: function () {
                    var options = event.target.options
                    if (options.selectedIndex > -1) {
                        this.filter.url = options[options.selectedIndex].getAttribute('url');
                        this.vendorUrl = options[options.selectedIndex].getAttribute('url')
                    }
                }

            },
            created: function () {
            },
            mounted: function () {
                const ctx = document.getElementById('graphicChart');
                this.chart = new Chart(ctx, {
                    plugins: [ChartDataLabels],
                    type: 'line',
                    data: {
                        labels: [],
                        datasets: [{
                            label: '{{ __('Общая сумма') }}',
                            data: [],
                            borderColor: 'rgba(0,169,221,0.5)',
                            backgroundColor: 'rgb(0,169,221)',
                        },
                            {
                                label: '{{ __('Без НДС') }}',
                                data: [],
                                borderColor: 'rgba(93,183,66,0.5)',
                                backgroundColor: 'rgb(93,183,66)',
                            },
                            {
                                label: '{{ __('Чистая прибыль') }}',
                                data: [],
                                borderColor: 'rgba(255,171,46,0.5)',
                                backgroundColor: 'rgb(255,171,46)',
                            }]
                    },
                    options: {
                        plugins: {
                            datalabels: {
                                backgroundColor: function (context) {
                                    return context.dataset.backgroundColor;
                                },
                                borderRadius: 4,
                                color: 'white',
                                font: {
                                    weight: 'bold'
                                },
                                formatter: function (value) {
                                    return graph_number_format(value);
                                },
                                padding: 6
                            }
                        },
                        aspectRatio: 3 / 1,
                        layout: {
                            padding: {
                                top: 32,
                                right: 16,
                                bottom: 16,
                                left: 16
                            }
                        },
                        elements: {
                            line: {
                                fill: false,
                                tension: 0.4
                            }
                        },
                        scales: {
                            y: {
                                // stacked: true
                            }
                        }
                        // scales: {
                        //     y: {
                        //         beginAtZero: true
                        //     }
                        // }
                    }
                });
            }
        });

        function drawGraphic(myChart, data) {
            if (data.length > 0) {

                const labels = [];
                for (var key in data) {
                    if (labels.find(x => x === data[key]['date']) == undefined) {
                        labels.push(data[key]['date']);
                    }
                }
                const totals = [];
                for (var dKey in data) {
                    for (var lKey in labels) {
                        if (labels[lKey] == data[dKey]['date']) {
                            totals[lKey] = data[dKey]['total'];
                        }
                    }
                }
                const totalsWithoutNDS = [];
                for (var dKey in data) {
                    for (var lKey in labels) {
                        if (labels[lKey] == data[dKey]['date']) {
                            totalsWithoutNDS[lKey] = data[dKey]['total_without_nds'];
                        }
                    }
                }
                const profits = [];
                for (var dKey in data) {
                    for (var lKey in labels) {
                        if (labels[lKey] == data[dKey]['date']) {
                            profits[lKey] = data[dKey]['profit'];
                        }
                    }
                }
                myChart.clear();
                myChart.data.labels = labels;
                myChart.data.datasets[0].data = totals;
                myChart.data.datasets[1].data = totalsWithoutNDS;
                myChart.data.datasets[2].data = profits;
                myChart.update();
            }
        }

        function graph_number_format(number) {
            let n = number_format(number, 0, '', ' ');
            return n.slice(0, n.length - 4);
        }

        function number_format(number, decimals = 0, dec_point = '.', thousands_sep = ',') {

            let sign = number < 0 ? '-' : '';

            let s_number = Math.abs(parseInt(number = (+number || 0).toFixed(decimals))) + "";
            let len = s_number.length;
            let tchunk = len > 3 ? len % 3 : 0;

            let ch_first = (tchunk ? s_number.substr(0, tchunk) + thousands_sep : '');
            let ch_rest = s_number.substr(tchunk)
                .replace(/(\d\d\d)(?=\d)/g, '$1' + thousands_sep);
            let ch_last = decimals ?
                dec_point + (Math.abs(number) - s_number)
                    .toFixed(decimals)
                    .slice(2) :
                '';

            return sign + ch_first + ch_rest + ch_last;
        }

    </script>


@endsection()
