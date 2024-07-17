@extends('templates.panel.app')

@section('title', __('panel/menu.report_clients_title'))


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

            <div class="filter col-auto" >

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

        <table class="table table-responsive-md table-hover mt-4">
            <tbody>
            @php
                $cnt = 0;
                $sum = 0;
            @endphp
             <tr>
                <td>Total amount</td><td> {{ number_format($total_amount,2,'.',' ') }}</td>
            </tr>
            <tr>
                <td>Wallet User</td>
                <td>@{{ balance }}</td>
            </tr>
            <tr class="bold"><td colspan="2">Клиенты:</td></tr>

            <tr>
                <td>Всего зарегистрировано</td><td>{{ $data['users']['count'] }}</td>
            </tr>
            <tr>
                <td>Успешно зарегистрировано</td><td>{{ $data['users']['success'] }}</td>
            </tr>
            {{--
            <tr class="show" data-item="dropdown_wait">
                <td>В ожидании всего <img class="icon" src="{{asset('assets/icons/chevron-right.svg')}}"></td><td>{{ $data['users']['wait'] }}</td>
            </tr>
                {!!  $data['users']['statuses'] !!}
            --}}
            <tr class="show" data-item="dropdown_scoring">
                <td>Не прошли верификацию <img class="icon" src="{{asset('assets/icons/chevron-right.svg')}}"></td><td>{{ $data['users']['count_blocked'] }}</td>
            </tr>
            <tr class="dropdown_scoring hide">
                <td>Не прошли по карте</td><td>{{ $data['users']['card'] }}</td>
            </tr>
            <tr class="dropdown_scoring hide">
                <td>Не прошли по КАТМ</td><td>{{ $data['users']['katm'] }}</td>
            </tr>
            <tr class="dropdown_scoring hide">
                <td>Не прошли по Royxat</td><td>{{ $data['users']['royxat'] }}</td>
            </tr>
            <tr class="dropdown_scoring hide">
                <td>Не прошли по ПИНФЛ</td><td>{{ $data['users']['pinfl'] }}</td>
            </tr>
            <tr class="dropdown_scoring hide">
                <td>Не прошли по возрасту</td><td>{{ $data['users']['age'] }}</td>
            </tr>
            <tr class="dropdown_scoring hide">
                <td>Держатель карты не соответствует</td><td>{{ $data['users']['incorrect'] }}</td>
            </tr>



            <tr class="bold"><td colspan="2">Договора:</td></tr>

            <tr class="show" data-item="dropdown_limits">
                <td>Одобренная сумма лимита <img class="icon" src="{{asset('assets/icons/chevron-right.svg')}}"></td><td>{{ number_format($data['contracts']['limit'][1],2,'.',' ') }}</td>
            </tr>

            {{--<tr class="bold"><td colspan="2">Лимиты:</td></tr>--}}

            <tr class="dropdown_limits hide">
                <td>350 000</td><td>{{ $data['limits']['350000'] }}</td>
            </tr>
            <tr class="dropdown_limits hide">
                <td>1 000 000</td><td>{{ $data['limits']['1000000'] }}</td>
            </tr>
            <tr class="dropdown_limits hide">
                <td>3 000 000</td><td>{{ $data['limits']['3000000'] }}</td>
            </tr>
            <tr class="dropdown_limits hide">
                <td>6 000 000</td><td>{{ $data['limits']['6000000'] }}</td>
            </tr>
            <tr class="dropdown_limits hide">
                <td>9 000 000</td><td>{{ $data['limits']['9000000'] }}</td>
            </tr>
            <tr class="dropdown_limits hide">
                <td>12 000 000</td><td>{{ $data['limits']['12000000'] }}</td>
            </tr>
            <tr class="dropdown_limits hide">
                <td>15 000 000</td><td>{{ $data['limits']['15000000'] }}</td>
            </tr>

            <tr>
                <td>Оформленные договора (шт.)</td><td>{{ $data['contracts']['count'] }}</td>
            </tr>
            <tr class="show" data-item="dropdown_contracts">
                <td>Сумма оформленных договоров <img class="icon" src="{{asset('assets/icons/chevron-right.svg')}}"></td><td>{{ number_format($data['contracts']['sum'],2,'.',' ') }}</td>
            </tr>
            <tr class="dropdown_contracts hide">
                <td colspan="2">
                    <div class="dropdown-container">
                        <table class="table-responsive">
                            <tr>
                            <th>№</th>
                            <th>Вендор</th>
                            <th>Сумма продаж</th>
                                <th title="Процентное соотношение продаж вендора к общим продажам">Процент<sup>*</sup></th>
                            </tr>
                            {!! @$contracts_report_table !!}
                        </table>
                    </div>
                </td>
            </tr>
            <tr>
                <td>Сумма оплаты поставщикам</td><td>{{ number_format($data['contracts']['sum_partner'],2,'.',' ') }}</td>
            </tr>
            <tr>
                <td>Доход</td><td>{{ number_format($data['contracts']['profit'],2,'.',' ') }}</td>
            </tr>
            <tr>
                <td>Средний чек</td><td>{{ number_format($data['contracts']['mean'],2,'.',' ') }}</td>
            </tr>
            <tr class="show" data-item="dropdown_delays">
                <td>Просроченная сумма <img class="icon" src="{{asset('assets/icons/chevron-right.svg')}}"></td><td>{{ number_format($data['contracts']['limit'][4],2,'.',' ') }}</td>
            </tr>

            <tr class="dropdown_delays hide">
                <td colspan="2">
                    <div class="dropdown-container">
                        <table class="table-responsive">
                            <tr>
                                <th>№</th>
                                <th>Месяц</th>
                                <th>Сумма</th>
                                <th title="Процентное от ожидаемой суммы">Процент</th>
                                <th>Ожидаемая сумма</th>
                            </tr>
                            {!! @$delays_report !!}
                        </table>
                    </div>
                </td>
            </tr>

            <tr>
                <td>Отменено на сумму</td><td>{{ number_format($data['contracts']['limit'][5],2,'.',' ') }}</td>
            </tr>

            <tr class="bold"><td colspan="2">График погашения:</td></tr>

            <tr>
                <td>Ожидаемая сумма к оплате</td><td>{{ number_format($data['payments']['wait'],2,'.',' ') }}</td>
            </tr>
            <tr class="show" data-item="dropdown_paycard">
                <td>Списанная сумма с карты <img class="icon" src="{{asset('assets/icons/chevron-right.svg')}}"></td><td>{{ number_format($data['payments']['card'],2,'.',' ') }}</td>
            </tr>
            <tr class="dropdown_paycard hide">
                <td colspan="2">
                    <div class="dropdown-container">
                        <table class="table-responsive">
                            <tr>
                                <th>№</th>
                                <th>Месяц</th>
                                <th>Сумма</th>
                            </tr>
                            {!! @$card_report !!}
                        </table>
                    </div>
                </td>
            </tr>

            <tr class="show" data-item="dropdown_paycard_pnfl">
                <td>Списанная сумма с карты (ПИНФЛ) <img class="icon" src="{{asset('assets/icons/chevron-right.svg')}}"></td><td>{{ number_format($data['payments']['card_pnfl'],2,'.',' ') }}</td>
            </tr>
            <tr class="dropdown_paycard_pnfl hide">
                <td colspan="2">
                    <div class="dropdown-container">
                        <table class="table-responsive">
                            <tr>
                                <th>№</th>
                                <th>Месяц</th>
                                <th>Сумма</th>
                            </tr>
                            {!! @$cardpnfl_report !!}
                        </table>
                    </div>
                </td>
            </tr>

            <tr class="show" data-item="dropdown_payaccount">
                <td>Списанная сумма с лицевого счета <img class="icon" src="{{asset('assets/icons/chevron-right.svg')}}"></td><td>{{ number_format($data['payments']['account'],2,'.',' ') }}</td>
            </tr>

            <tr class="dropdown_payaccount hide">
                <td colspan="2">
                    <div class="dropdown-container">
                        <table class="table-responsive">
                            <tr>
                                <th>№</th>
                                <th>Месяц</th>
                                <th>Сумма</th>
                            </tr>
                            {!! @$account_report !!}
                        </table>
                    </div>
                </td>
            </tr>

            <tr>
                <td>Списанная сумма с депозита</td><td>{{ number_format($data['payments']['deposit'],2,'.',' ') }}</td>
            </tr>

			<tr class="show" data-item="dropdown_paysystem">
                <td>Пополнение лицевого счета <img class="icon" src="{{asset('assets/icons/chevron-right.svg')}}"></td><td>{{ number_format($data['payments']['paysystem_sum'],2,'.',' ') }}</td>
            </tr>

            <tr class="dropdown_paysystem hide">
                <td colspan="2">
                    <div class="dropdown-container">
                        <table class="table-responsive">
                            <tr>
                                <th>Система</th>
                                <th>Сумма</th>
                            </tr>
                            {!! @$paysystem_report !!}
                        </table>
                    </div>
                </td>
            </tr>


            </tbody>
        </table>


    </div>
    @php
          if(isset($request->date)){
                $date = explode(',',$request->date);
          }else{
                $date = ['null','null'];
          }
    @endphp

    <script>

        let report = new Vue({
            el: '#report',
            data: {
                errors: [],
                messages: [],
                filter: {
                    period: '{{ $request->type ?? 'last_day' }}',
                    date: [ '{{$date[0]}}','{{$date[1]}}' ],
                    company: '',
                    report: 0,
                    url: '{{ localeRoute( 'panel.buyer.report' ) }}'
                },
                parts: {},
                params: {},
                transactionConfig: null,
                results: false,
                loading: false,
                api_token: '{{Auth::user()->api_token}}',
            },
            computed: {
                balance() {
                    if (!this.transactionConfig?.balance) return 0;

                    return (this.transactionConfig.balance / 100).toLocaleString();
                }
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
                async getTransactionConfig() {
                    try {
                        const { data: config } = await axios.get('/api/v3/admin/transaction/config',
                            {
                                headers: {
                                    Authorization: `Bearer ${Cookies.get('api_token')}`,
                                },
                            });

                        this.transactionConfig = config.data;
                    } catch (e) {
                        console.error(e);
                    }
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
                            console.log('errr')
                            report.$forceUpdate();
                        }
                    }else {
                        if (url.length > 24) window.location.href = url;
                    }

                },

            },
            created: function () {
                this.getTransactionConfig()
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
