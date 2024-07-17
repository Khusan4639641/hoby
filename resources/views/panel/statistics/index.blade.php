@extends('templates.panel.app')

@section('title', __('statistics.title'))
@section('class', 'statistics finance')

@section('content')
    <div class="container">
        <div class="filtered">
            <div class="" id="statisticsPayments">
                <div class="lead">{{__('statistics.finance.payments')}}</div>
                <div v-if="messages.length">
                    <div class="alert alert-success" v-for="message in messages">@{{ message }}</div>
                </div>
                <div class="alert alert-danger" v-for="item in errors.system">@{{ item }}</div>
                <div class="filter">
                    <div class="row">
                        <div class="col-12 col-md-4">
                            <div class="form-group">
                                <label for="period">{{__('statistics.partner.period')}}</label>
                                <select id="period" name="period" v-model="filter.period" class="form-control">
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
                                <date-picker id="date" v-on:change="checkDate" value-type="format" v-model="filter.date" type="date" format="DD.MM.YYYY" range="true" name="date" :class="(errors.date?' is-invalid':'')"></date-picker>
                                <div class="error" v-for="item in errors.date">@{{ item }}</div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label>&nbsp;</label>
                            <button v-on:click="buildReport" type="submit" class="btn btn-primary">{{__('statistics.partner.btn_build_report')}}</button>
                        </div>
                    </div>
                </div>
                <div class="mt-3 results" v-show="results">
                    <div class="table">
                        <div v-if="loading" class="loader">
                            <img src="{{asset('images/media/loader.svg')}}">
                        </div>
                        <table>
                            <thead>
                            <tr>
                                <th>{{__('statistics.partner.period')}}</th>
                                <th>{{__('statistics.finance.debit')}}</th>
                                <th>{{__('statistics.finance.credit')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="item in parts">
                                <td>@{{ item.period }}</td>
                                <td>@{{ item.statistics.debit }}</td>
                                <td>@{{ item.statistics.credit }}</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="chart">
                        <canvas id="chartPayments"></canvas>
                    </div>
                </div>
            </div>
            <div class="mt-4" id="statisticsOrders">
                <div class="lead">{{__('statistics.finance.orders')}}</div>
                <div v-if="messages.length">
                    <div class="alert alert-success" v-for="message in messages">@{{ message }}</div>
                </div>
                <div class="alert alert-danger" v-for="item in errors.system">@{{ item }}</div>
                <div class="filter">
                    <div class="row">
                        <div class="col-12 col-md-4">
                            <div class="form-group">
                                <label for="period">{{__('statistics.partner.period')}}</label>
                                <select id="period" name="period" v-model="filter.period" class="form-control">
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
                                <date-picker id="date" v-on:change="checkDate" value-type="format" v-model="filter.date" type="date" format="DD.MM.YYYY" range="true" name="date" :class="(errors.date?' is-invalid':'')"></date-picker>
                                <div class="error" v-for="item in errors.date">@{{ item }}</div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label>&nbsp;</label>
                            <button v-on:click="buildReport" type="submit" class="btn btn-primary">{{__('statistics.partner.btn_build_report')}}</button>
                        </div>
                    </div>
                </div>
                <div class="mt-3 results" v-show="results">
                    <div class="table">
                        <div v-if="loading" class="loader">
                            <img src="{{asset('images/media/loader.svg')}}">
                        </div>
                        <table>
                            <thead>
                            <tr>
                                <th>{{__('statistics.partner.period')}}</th>
                                <th>{{__('statistics.finance.sold')}}</th>
                                <th>{{__('statistics.finance.paid')}}</th>
                                <th>{{__('statistics.finance.earned')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="item in parts">
                                <td>@{{ item.period }}</td>
                                <td>@{{ item.statistics.sold }}</td>
                                <td>@{{ item.statistics.paid }}</td>
                                <td>@{{ item.statistics.earned }}</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="chart">
                        <canvas id="chartOrders"></canvas>
                    </div>
                </div>
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

        let myBarPayments, myBarOrders;

        window.onload = function (){
            var ctxPayments = document.getElementById('chartPayments').getContext('2d');
            var ctxOrders = document.getElementById('chartOrders').getContext('2d');

            let _params = {
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
            };
            myBarPayments = new Chart(ctxPayments, _params);
            myBarOrders = new Chart(ctxOrders, _params);
        }


        let statisticsPayments = new Vue({
            el: '#statisticsPayments',
            data: {
                errors: {},
                messages: [],
                filter: {
                    period: 'last_week',
                    date: [null, null]
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
                    axios.post('/api/v1/statistics/finance',
                        this.params,
                        {headers: {'Content-Language': '{{app()->getLocale()}}'}}
                    ).then(response => {
                        if (response.data.status === 'success') {
                            this.parts = response.data.data;

                            let labels = [];
                            let statistics = {
                                'debit': [],
                                'credit': []
                            };

                            for(let i = 0; i<this.parts.length; i++){
                                labels.push(this.parts[i].period);
                                statistics.debit.push(this.parts[i].statistics.debit);
                                statistics.credit.push(this.parts[i].statistics.credit);

                            }


                            var barChartData = {
                                labels: labels,
                                datasets: [{
                                    label: '{{__('statistics.finance.debit')}}',
                                    backgroundColor: window.chartColors.blue,
                                    data: statistics.debit
                                },
                                    {
                                    label: '{{__('statistics.finance.credit')}}',
                                    backgroundColor: window.chartColors.red,
                                    data: statistics.credit
                                }]

                            };


                            myBarPayments.data = barChartData;
                            myBarPayments.update();

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
                    this.params.statistic_type = 'payments';
                    if(this.filter.period === "custom" && this.filter.date[0] !== null)
                        this.params.date = this.filter.date;

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

        let statisticsOrders = new Vue({
            el: '#statisticsOrders',
            data: {
                errors: {},
                messages: [],
                filter: {
                    period: 'last_week',
                    date: [null, null]
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
                    axios.post('/api/v1/statistics/finance',
                        this.params,
                        {headers: {'Content-Language': '{{app()->getLocale()}}'}}
                    ).then(response => {
                        if (response.data.status === 'success') {
                            this.parts = response.data.data;

                            let labels = [];
                            let statistics = {
                                'sold': [],
                                'paid': [],
                                'earned': []
                            };

                            for(let i = 0; i<this.parts.length; i++){
                                labels.push(this.parts[i].period);
                                statistics.sold.push(this.parts[i].statistics.sold);
                                statistics.paid.push(this.parts[i].statistics.paid);
                                statistics.earned.push(this.parts[i].statistics.earned);

                            }


                            var barChartData = {
                                labels: labels,
                                datasets: [{
                                    label: '{{__('statistics.finance.sold')}}',
                                    backgroundColor: window.chartColors.blue,
                                    data: statistics.sold
                                    },
                                    {
                                        label: '{{__('statistics.finance.paid')}}',
                                        backgroundColor: window.chartColors.red,
                                        data: statistics.paid
                                    },
                                    {
                                        label: '{{__('statistics.finance.earned')}}',
                                        backgroundColor: window.chartColors.green,
                                        data: statistics.earned
                                    }]

                            };


                            myBarOrders.data = barChartData;
                            myBarOrders.update();

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
                    this.params.statistic_type = 'orders';
                    if(this.filter.period === "custom" && this.filter.date[0] !== null)
                        this.params.date = this.filter.date;

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
