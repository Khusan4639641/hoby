@extends('templates.panel.app')
@section('title', __('order.title'))

@section('print_act')
@endsection
@section('content')
    <style>
        :root {
            --12-pt: 22px;
            --9-pt: 18px;
        }
        @media print {
            html, body, .court-contract .page-a4 {
                height: 100px;
                page-break-after: auto;
            }
        }
        .title>div,
        .title h1 {
            width: 100%;
        }
        .content .center .center-body {
            background: #474747;
        }
        .court-contract .page-a4{
            font-family: Montserrat;
            font-size: var(--9-pt);
            border-radius: 0;
            page-break-after: auto;
        }
        .court-contract table th,
        .court-contract .table td {
            padding: 0;
            font-size: var(--9-pt) !important;
            text-align: center;
        }

        .court-contract .page-a4 .title {
            font-size: var(--12-pt);
            font-weight: 700;
        }
        .btn.btn-orange {
            box-shadow: 0px 2px 5px 0px #00000040;

        }
        .btn:focus {
            box-shadow: 0px 2px 5px 0px #00000040;
            border: 1px solid transparent;
        }
    </style>
    <div id="court-contract" class="letter court-contract" v-if="doc_data">
        <div class="row justify-content-center mb-5">
            <div v-if="!printing" class="col-12">
                <button @click="print" class="print-btn btn btn-orange mb-3 float-right">Распечатать</button>
            </div>
            <div class="col-2xl-2 col-xl-8 col-md-10">
                <div class="page-a4">
                    <p class="title text-center"> Договор №{{ $contract_id }} </p>
                    <p class="title text-center mb-5"> от @{{ doc_data.created_at }}</p>

                    {{-- реквизиты --}}
                    <div class="row justify-content-center mb-5">
                        <div class="col-6">
                            <table class="table table-borderless w-100">
                                <tbody>
                                    <tr v-for="(td, i) in seller_table_cols" :key="i">
                                        <th class="text-left pb-3">@{{ td.name }}</th>
                                        <td v-if="'format' in td" class="text-right">@{{ td.format(doc_data.company[td.key], doc_data, i) }}</td>
                                        <td v-else class="text-right">@{{ doc_data.company[td.key] }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-6">
                            <table class="table table-borderless w-100">
                                <tbody>
                                    <tr v-for="(td, i) in buyer_table_cols" :key="i">
                                        <th class="text-left pb-3">@{{ td.name }}</th>
                                        <td v-if="'format' in td" class="text-right">@{{ td.format(doc_data.debtor[td.key], doc_data, i) }}</td>
                                        <td v-else class="text-right">@{{ doc_data.debtor[td.key] }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Список товаров --}}
                    <div class="row justify-content-center mb-5">
                        <div class="col-12">
                            <table class="table table-bordered w-100">
                                <thead>
                                    <tr>
                                        <th class="align-middle" v-for="(tc, i) in product_table_cols" :key="i">@{{ tc.name }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(tb, k) in doc_data.products" :key="k">
                                        <td class="align-middle" v-for="(tc, i) in product_table_cols" :key="i" :style="{ width: tc.width }">
                                            <span v-if="tc.key == 'k'">@{{ k + 1 }}</span>
                                            <span v-else-if="'format' in tc" v-html="tc.format(tb[tc.key], doc_data, k)"></span>
                                            <span v-else> @{{ tb[tc.key] }} </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{--Данные автопей --}}
                    <table class="table table-bordered w-100 mb-5">
                        <thead>
                            <tr>
                                <th v-for="(tc, i) in debt_table_cols" :key="i">@{{ tc.name }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr >
                                <td v-for="(td, i) in debt_table_cols" :key="i">
                                    <span v-if="'format' in td" class="text-right">@{{ td.format(doc_data.debtor[td.key], doc_data.debts, i) }}</span>
                                    <span v-else class="text-right">@{{ doc_data.debtor[td.key] }}</span>
                                </td>

                            </tr>
                        </tbody>
                    </table>

                    {{-- Условия  --}}
                    <p style="font-size: var(--9-pt)">*Условия рассрочки итого на общую сумму: <b>@{{nf(doc_data.order.total)}}</b></p>
                    <p style="font-size: var(--9-pt)" class="mb-5">*<b>@{{nf(doc_data.order.total)}}</b> подлежит выплате в течении - <b>@{{doc_data.order.period || ''}} месяцев</b></p>


                    {{-- График платежей  --}}
                    <h4 class="text-center" style="font-size: var(--9pt); font-weight: bold;">График платежей</h4>
                    <table class="table table-bordered w-100">
                        <thead>
                            <tr>
                                <th v-for="(tc, i) in payment_table_cols" :key="i">@{{ tc.name }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(tb, k) in doc_data.schedules" :key="k">
                                <td v-for="(tc, i) in payment_table_cols" :key="i" :style="{ width: tc.width }">
                                    <span v-if="tc.key == 'k'">@{{ k + 1 }}</span>
                                    <span v-else-if="'format' in tc" v-html="tc.format(tb[tc.key], tb, k)"></span>
                                    <span v-else> @{{ tb[tc.key] }} </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="bottomside__register d-flex justify-content-between " style="margin-top: 6rem">
                        <p><span>{{ __('panel/contract.director') }} @{{ doc_data.company?.general_company_name_uzlat }}</span></p>
                        <img
                          :src="doc_data.company?.general_company_stamp"
                          alt="stamp"
                          class="bottomside__register-stamp"
                        >
                        <p><span>@{{ doc_data.company?.general_company_director_uzlat }}</span></p>
                      </div>
                    </section>
        
                    <p class="bottomside__number">Call Center: @{{ doc_data.company?.call_center }}</p>
                </div>
            </div>
        </div>
    </div>


    <script>
        const court_contract = new Vue({
            el: '#court-contract',
            data: {
                payment_table_cols: [{
                        name: 'Номер',
                        width: "5%",
                        key: "k",
                        format: (data, raw, index) => index + 1
                    },
                    {
                        name: 'Дата',
                        width: "15%",
                        key: "payment_date",
                        format: (data, raw, index) => moment(data, 'DD.MM.YYYY HH:mm:ss').format('DD.MM.YYYY')
                    },
                    {
                        name: 'Платеж (сум)',
                        width: "20%",
                        key: "total",
                        format: (data, raw, index) => `<b>${court_contract.nf(data)}</b>`
                    },
                    {
                        name: 'Остаток (сум)',
                        width: "20%",
                        key: "balance",
                        format: (data, raw, index) => `${court_contract.nf(data)}`
                    },
                    {
                        name: 'Статус',
                        width: "40%",
                        key: "paid_at",
                        format: (data, raw, index) => data ? `Оплачен в ${moment(data, 'YYYY-MM-DD HH:mm:ss').format('DD.MM.YYYY HH:mm:ss')}` : 'Не оплачен'
                    },
                ],
                seller_table_cols: [{
                        name: 'Торговая компания',
                        key: 'general_company_name_uzlat'
                    },
                    {
                        name: 'Продавец',
                        key: 'manager'
                    },
                    {
                        name: 'Название компании',
                        key: 'name'
                    },
                    {
                        name: 'Адрес',
                        key: 'address'
                    },
                    {
                        name: 'Юр. адрес',
                        key: 'legal_address'
                    },
                    {
                        name: 'ИНН',
                        key: 'inn'
                    },
                    {
                        name: 'Р/C',
                        key: 'payment_account'
                    }
                ],
                buyer_table_cols: [{
                        name: 'Покупатель',
                        key: 'full_name'
                    },
                    {
                        name: 'Адрес',
                        key: '',
                        format: (data, {debtor}, index)=> debtor.addresses.registration
                    },
                    {
                        name: 'ID',
                        key: 'id'
                    },
                    {
                        name: 'Телефон',
                        key: 'phone'
                    },
                    {
                        name: 'Срок рассрочки',
                        key: '',
                        format: (data, {debtor}, index)=> `${debtor.settings.period} мес.`
                    },
                    {
                        name: 'Сумма рассрочки',
                        key: '',
                        format: (data, {debtor}, index)=> `${court_contract.nf(debtor.settings.limit)} сум`
                    },
                    {
                        name: 'Общая задолженность',
                        key: '',
                        format: (data, raw, index)=> `${court_contract.nf(raw.debt_sum)} сум`
                    },
                ],
                debt_table_cols: [{
                        name: '1% от всей оставшейся задолженности по контракту',
                        key: 'collect_cost.percent',
                        format: (data, {collect_cost}, index)=> `${court_contract.nf(collect_cost?.percent)} сум`
                    },
                    {
                        name: 'Фиксированная сумма по взысканию',
                        key: 'collect_cost.fix',
                        format: (data, {collect_cost}, index)=> `${court_contract.nf(collect_cost.fix)} сум`
                    },
                    {
                        name: 'Остаток оплаты за взыскание',
                        key: 'collect_cost.balance',
                        format: (data, {collect_cost}, index)=> `${court_contract.nf(collect_cost.balance)} сум`
                    },
                    // {
                    //     name: 'Фиксированная сумма за Autopay (3%)',
                    //     key: 'autopay.percent',
                    //     format: (data, {autopay}, index)=> `${court_contract.nf(autopay?.percent)} сум`
                    // },
                    // {
                    //     name: 'Остаток оплаты за Autopay',
                    //     key: 'autopay.balance',
                    //     format: (data, {autopay}, index)=> `${court_contract.nf(autopay?.balance)} сум`
                    // },

                ],
                product_table_cols: [
                    {
                        name: 'Категория',
                        width: "20%",
                        key: "category",
                    },
                    {
                        name: 'Наименование товара',
                        width: "35%",
                        key: "name",
                    },
                    {
                        name: 'Кол-во, шт.',
                        width: "10%",
                        key: "amount",
                    },
                    {
                        name: 'Стоимость с НДС, сум.',
                        width: "20%",
                        key: "price",
                        format: (data, raw, index) => court_contract.nf(data)
                    },
                    {
                        name: 'Депозит',
                        width: "15%",
                        key: "",
                        format: (data, {order}, index) => court_contract.nf(order.deposit)
                    },
                ],
                doc_data: null,
                printing: false
            },
            methods: {
                nf(x) {
                    if (!x) x = 0
                    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
                },
                async getData() {
                    const contractId = @json($contract_id);

                    try {
                        const response = await axios.get(`/api/v3/debt-collect-leader/contracts/${contractId}`, {
                                headers: {
                                    'Authorization': `Bearer ${window.globalApiToken}`,
                                    'Content-Language': window.Laravel.locale,
                                },
                            },
                        )
                        this.doc_data = response.data
                        // let company_is_ttp = this.doc_data?.company?.general_company_is_tpp
                        
                        // switch (company_is_ttp) {
                        //     case 1:
                        //         this.debt_table_cols.shift()
                        //         break;
                        //     case 0:
                        //         this.debt_table_cols.splice(-2, 2)
                        //         break;
                        //     default:
                        //         break;
                        // }
                        
                    } catch (err) {
                        console.error(err)
                    }
                },
                print() {
                    this.printing = true
                    setTimeout(() => {
                        window.print()
                    }, 0)
                    window.onafterprint = () => {
                        this.printing = false;
                    }
                }

            },
            created() {
                this.getData();
            },
        })
    </script>
@endsection
