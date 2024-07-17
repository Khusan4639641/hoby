@extends('templates.panel.app')

@section('title', __('panel/menu.report_soliq_title'))


@section('content')

    <style>
        .bold{
            font-weight: bold;
        }
        td.padding_left{
            padding-left: 50px;
        }
        td.gray{
            color: #828282;
        }
    </style>

    <div class="catalog category" id="report">

        <div class="filtered row">

            <div class="filter" >

                <div class="row">

                    {{--
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
                                name="date"
                                :class="(errors?' is-invalid':'') + '-range-100'"
                                placeholder="{{ __('statistics.partner.date') }}">
                            </date-picker>
                            <div class="alert alert-danger alert-dismissible fade show" v-for="error in errors">
                                @{{ error }}
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        </div>
                    </div> --}}

                    <div class="col-12">
                        <div class="form-group">
                            <label for="inn">ИНН</label>
                            <input class="form-control" type="text" name="inn" v-model="filter.inn" required>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <select id="period" name="period" v-model="filter.period" class="form-control select-period" required>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-group">
                            <label><input type="checkbox" name="report" v-model="report.companies"> Информация по Юр. лицам</label>
                        </div>
                        <div class="form-group">
                            <label><input type="checkbox" name="report" v-model="report.debts"> Налоговая задолженность</label>
                        </div>
                        <div class="form-group">
                            <label><input type="checkbox" name="report" v-model="report.workers"> Колво работников</label>
                        </div>
                        <div class="form-group">
                            <label><input type="checkbox" name="report" v-model="report.balance"> Бухгалтерский баланс (Форма 1)</label>
                        </div>
                        <div class="form-group">
                            <label><input type="checkbox" name="report" v-model="report.finance"> Финансовая отчетность (Форма 2)</label>
                        </div>
                        <div class="form-group">
                            <label><input type="checkbox" name="report" v-model="report.nds"> База налога на добавленную стоимость (НДС)</label>
                        </div>
                        <div class="form-group">
                            <label><input type="checkbox" name="report" v-model="report.enp"> Отчет по подоходному налогу (ЕНП)</label>
                        </div>

                    </div>

                    <div class="alert alert-danger alert-dismissible fade show" v-for="error in errors">
                        @{{ error }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>


                    <div class="col-md-12">
                        <button v-on:click="getReport" type="submit" class="btn btn-orange" style="margin-top:20px;">{{__('app.btn_continue')}}</button>
                    </div>

                </div>

            </div>

        </div>

    </div>


    <script>

        let report = new Vue({
            el: '#report',
            data: {
                errors: [],
                messages: [],
                filter: {
                    period: {{$period}},
                    inn: '{{$inn}}',
                    report: 0,
                    url: '{{ localeRoute( 'panel.soliq.report' ) }}'
                },
                report:{
                    Companies:0,
                    Debts:0,
                    Workers:0,
                    Balance:0,
                    Finance:0,
                    NDS:0,
                    ENP:0
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
                    /*if(this.filter.period === "custom" && this.filter.date !== null)
                        this.params.date = this.filter.date;
                    if(this.filter.company !== '')
                        this.params.company_id = this.filter.company; */

                },
                checkDate: function (){

                    return true;

                    /*if(this.filter.date === null){
                        this.errors = [];
                        this.errors.push( '{{__('statistics.partner.date_empty')}}');
                    } else {
                        this.errors = [];
                        return true;
                    }
                    return false; */

                },
                changePeriod: function () {

                },
                getReport(){
                    //this.errors = [];
                    var url = this.filter.url;
                    var err = false;
                    if(this.filter.inn.length<9){
                        this.errors.push('{{__('statistics.partner.inn_not_fill')}}');
                        err=true;
                    }
                    if(this.filter.period == null) {
                        this.errors.push('{{__('statistics.partner.date_empty')}}');
                        err=true;
                    }
                    var reports = '';
                    for (_report in this.report){
                        if(this.report[_report]) reports+=_report +',';
                    }
                    if(reports == '') {
                        this.errors.push('{{__('statistics.partner.reports_not_set')}}');
                        err=true;
                    }

                    if(!err) {
                        url += '?inn='+this.filter.inn+ '&period=' + this.filter.period + '&reports='+reports
                        window.location.href = url;
                        return true;
                    }
                    report.$forceUpdate();
                },

            },
            created: function () {
            }
        });

    </script>

@endsection
