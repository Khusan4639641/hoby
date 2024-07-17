@extends('templates.billing.app')

@section('title', __('statistics.title'))
@section('class', 'statistics partner')

@section('content')
{{--        <div class="params">--}}
{{--            <div class="row">--}}
{{--                <div class="col-12 col-sm part credit">--}}
{{--                    <div class="caption">{{__('statistics.partner.credit')}}</div>--}}
{{--                    <div class="value">{{$statistics['credit']}}</div>--}}
{{--                </div>--}}
{{--                <div class="col-12 col-sm part debit">--}}
{{--                    <div class="caption">{{__('statistics.partner.debit')}}</div>--}}
{{--                    <div class="value">{{$statistics['debit']}}</div>--}}
{{--                </div>--}}
{{--                <div class="col-12 col-sm part received">--}}
{{--                    <div class="caption">{{__('statistics.partner.received')}}</div>--}}
{{--                    <div class="value">{{$statistics['received']}}</div>--}}
{{--                </div>--}}
{{--                <div class="col-12 col-sm part sold">--}}
{{--                    <div class="caption">{{__('statistics.partner.sold')}}</div>--}}
{{--                    <div class="value">{{$statistics['sold']}}</div>--}}
{{--                </div>--}}
{{--            </div><!-- /.row -->--}}
{{--        </div><!-- /.params -->--}}
        <div class="filtered" id="statistics">



            <div v-if="messages.length">
                <div class="alert alert-success" v-for="message in messages">@{{ message }}</div>
            </div>
            <div class="alert alert-danger" v-for="item in errors.system">@{{ item }}</div>
            <div class="filter">
                <div class="font-weight-bold font-size-24 mb-3">{{__('statistics.finance.payments')}}</div>
                <div class="row">
                    <div class="col-12 col-md-4">
                        <div class="form-group">
                            <label for="period">{{__('statistics.partner.period')}}</label>
                            <select id="period" name="period" v-model="filter.period" class="form-control select-period">
                                <option value="last_day">{{__('statistics.partner.last_day')}}</option>
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
                                :class="(errors.date?' is-invalid':'') + 'range-100'"
                                placeholder="{{ __('statistics.partner.date') }}">
                            </date-picker>
                            <div class="error" v-for="item in errors.date">@{{ item }}</div>
                        </div>
                    </div>
                </div>
                <div class="font-weight-bold font-size-24 mb-3">{{__('statistics.partner.affiliates')}}</div>
                <div class="row">
                    <div class="col-12 col-md-4">
                        <div class="form-group">
                            <label for="company">{{__('statistics.partner.affiliate')}}</label>
                            <select id="company" name="period" v-model="filter.company" class="form-control select-period">
                                <option value="">{{__('statistics.partner.all')}}</option>
                                @foreach($affiliates as $affiliate)
                                    <option value="{{$affiliate->id}}">{{$affiliate->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-12 col-md-8">
                        <label>&nbsp;</label>
                        <button v-on:click="buildReport" type="submit" class="btn btn-orange">{{__('statistics.partner.btn_build_report')}}</button>
                    </div>
                </div>
            </div>
            <div class="results" v-show="results">
                <div class="table">
                    <div v-if="loading" class="loader">
                        <img src="{{asset('images/media/loader.svg')}}">
                    </div>
                    <table>
                        <thead>
                        <tr>
                            <th>{{__('statistics.partner.period')}}</th>
                            <th>{{__('statistics.partner.credit')}}</th>
                            <th>{{__('statistics.partner.debit')}}</th>
                            <th>{{__('statistics.partner.received')}}</th>
                            <th>{{__('statistics.partner.sold')}}</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="item in parts">
                            <td>@{{ item.period }}</td>
                            <td>@{{ item.statistics.credit }}</td>
                            <td>@{{ item.statistics.debit }}</td>
                            <td>@{{ item.statistics.received }}</td>
                            <td>@{{ item.statistics.sold }}</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <div class="chart">
                    <canvas id="chart"></canvas>
                </div>
            </div>
        </div>

    <script>


        window.chartColors = {
            red: 'rgb(255, 99, 132)',
            orange: 'rgb(255, 159, 64)',
            yellow: 'rgb(255, 205, 86)',
            green: 'rgb(75, 192, 192)',
            blue: 'rgb(54, 162, 235)',
            purple: 'rgb(153, 102, 255)',
            grey: 'rgb(201, 203, 207)'
        };

        let myBar;

        window.onload = function (){
            var ctx = document.getElementById('chart').getContext('2d');
            myBar = new Chart(ctx, {
                type: 'bar',
                data: {},
                options: {
                    title: {
                        display: false,
                    },
                    tooltips: {
                        mode: 'index',
                        intersect: false
                    },
                    responsive: true,
                    scales: {
                        xAxes: [{
                            stacked: true,
                        }],
                        yAxes: [{
                            stacked: true
                        }]
                    }
                }
            });
        }


        let statistics = new Vue({
            el: '#statistics',
            data: {
                errors: {},
                messages: [],
                filter: {
                    period: 'last_week',
                    date: [null, null],
                    company: ''
                },
                parts: {},
                params: {},
                results: false,
                loading: false,
                api_token: '{{Auth::user()->api_token}}',
            },
            methods: {
                buildReport: function (){
                    this.errors = {};
                    this.messages = [];
                    this.buildParameters();

                    if(this.filter.period === 'custom'){
                        if(!this.checkDate())
                            return false;
                    }

                    this.loading = true;
                    axios.post('/api/v1/statistics/partner',
                        this.params,
                        {headers: {'Content-Language': '{{app()->getLocale()}}'}}
                    ).then(response => {
                        if (response.data.status === 'success') {
                            this.parts = response.data.data;

                            let labels = [];
                            let statistics = {
                                'credit': [],
                                'debit': [],
                                'received': [],
                                'sold': []
                            };

                            for(let i = 0; i<this.parts.length; i++){
                                labels.push(this.parts[i].period);
                                statistics.credit.push(this.parts[i].statistics.credit);
                                statistics.debit.push(this.parts[i].statistics.debit);
                                statistics.received.push(this.parts[i].statistics.received);
                                statistics.sold.push(this.parts[i].statistics.sold);
                            }


                            var barChartData = {
                                labels: labels,
                                datasets: [{
                                    label: '{{__('statistics.partner.credit')}}',
                                    backgroundColor: window.chartColors.red,
                                    data: statistics.credit
                                }, {
                                    label: '{{__('statistics.partner.debit')}}',
                                    backgroundColor: window.chartColors.blue,
                                    data: statistics.debit
                                }, {
                                    label: '{{__('statistics.partner.received')}}',
                                    backgroundColor: window.chartColors.green,
                                    data: statistics.received
                                },
                                    {
                                        label: '{{__('statistics.partner.sold')}}',
                                        backgroundColor: window.chartColors.purple,
                                        data: statistics.sold
                                    }]

                            };


                            myBar.data = barChartData;
                            myBar.update();

                        } else {
                            this.errors = response.data.response.errors;
                        }
                        this.loading = false;
                        this.results = true;
                    })
                },

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
            },
            created: function () {

            }
        });

    </script>
@endsection()
