@extends('templates.panel.app')
@section('title', new \Illuminate\Support\HtmlString(__('payment_info.title')))

<script src="{{ asset('/assets/js/num2str.js') }}"></script>

@section('content')

@include('panel.payment_info.parts.style')
    
    <div id="payment_order">
        {{--        <section>--}}
        {{--            <div class="row">--}}
        {{--                <div class="col-md-4">--}}
        {{--                    <div class="form-group">--}}
        {{--                        <label>Период оплат</label>--}}
        {{--                        <input type="date" name="" placeholder="Период оплат" class="form-control modified">--}}
        {{--                    </div>--}}
        {{--                </div>--}}
        {{--            </div>--}}
        {{--        </section>--}}
        {{-- Новый документ -------------------------------------------------------------------------- --}}
        {{--        <section>--}}
        {{--            <h5 class="section-title">Новый документ</h5>--}}
        {{--            <div class="row">--}}
        {{--                <div class="col-md-4">--}}
        {{--                    <div class="form-group">--}}
        {{--                        <label>Тип документа</label>--}}
        {{--                        <select name="" class="form-control modified">--}}
        {{--                            <option value="" selected disabled>Выберите тип документа</option>--}}
        {{--                        </select>--}}
        {{--                    </div>--}}
        {{--                </div>--}}
        {{--                <div class="col-md-4">--}}
        {{--                    <div class="form-group">--}}
        {{--                        <label>Шаблон</label>--}}
        {{--                        <select name="" class="form-control modified">--}}
        {{--                            <option value="" selected disabled>Выберите шаблон</option>--}}
        {{--                        </select>--}}
        {{--                    </div>--}}
        {{--                </div>--}}
        {{--            </div>--}}
        {{--            <div class="row">--}}
        {{--                <div class="col-md-4">--}}
        {{--                    <div class="form-group mb-1">--}}
        {{--                        <label>Номер документа</label>--}}
        {{--                        <input type="number" name="" placeholder="Период оплат" class="form-control modified">--}}
        {{--                    </div>--}}
        {{--                    <div class="form-group">--}}
        {{--                        <label>--}}
        {{--                            <input type="checkbox" name="">--}}
        {{--                            <span>Установить нумерацию</span>--}}
        {{--                        </label>--}}
        {{--                    </div>--}}
        {{--                </div>--}}
        {{--                <div class="col-md-4">--}}
        {{--                    <div class="form-group">--}}
        {{--                        <label>Дата документа</label>--}}
        {{--                        <input type="date" name="" placeholder="Выберите дату документа" class="form-control modified">--}}
        {{--                    </div>--}}
        {{--                </div>--}}
        {{--            </div>--}}
        {{--            <hr>--}}
        {{--        </section>--}}


        {{-- ДЕБЕТ -------------------------------------------------------------------------- --}}
        <section>
            <h5 class="section-title">{{__('payment_info.debit')}}</h5>
            <div class="row">
                {{--                <div class="col-md-4">--}}
                {{--                    <div class="form-group mb-2">--}}
                {{--                        <label>Банк отправителя</label>--}}
                {{--                        <select name="" class="form-control modified">--}}
                {{--                            <option value="" selected disabled>Выберите банк отправителя</option>--}}
                {{--                        </select>--}}
                {{--                    </div>--}}
                {{--                    <div class="form-group">--}}
                {{--                        <label>--}}
                {{--                            <input type="checkbox" name="">--}}
                {{--                            <span>Включить/Отключить остаток на счете</span>--}}
                {{--                        </label>--}}
                {{--                    </div>--}}
                {{--                </div>--}}
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{__('payment_info.sender_account')}}</label>
                        <input :value="debit.number" v-mask="'#### #### #### #### ####'" readonly type="text" name=""
                               placeholder="{{__('payment_info.sender_account_placeholder')}}"
                               class="form-control modified">
                    </div>
                </div>
                {{--                <div class="col-md-4">--}}
                {{--                    <div class="form-group">--}}
                {{--                        <label>ИНН отправителя</label>--}}
                {{--                        <input type="number" name="" placeholder="Введите ИНН отправителя" class="form-control modified">--}}
                {{--                    </div>--}}
                {{--                </div>--}}

            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{__('payment_info.sender_name')}}</label>
                        <input :value="debit.name" readonly type="text" name=""
                               placeholder="{{__('payment_info.sender_name_placeholder')}}"
                               class="form-control modified">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{__('payment_info.unposted_balance')}}</label>
                        <input :value="debitBalance" readonly type="text" name=""
                               placeholder="{{__('payment_info.unposted_balance_placeholder')}}"
                               class="form-control modified">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{__('payment_info.account_balance')}}</label>
                        <input :value="debitBalance" readonly type="text" name=""
                               placeholder="{{__('payment_info.account_balance_placeholder')}}" class="form-control modified">
                    </div>
                </div>
            </div>
            <hr>
        </section>


        {{-- КРЕДИТ -------------------------------------------------------------------------- --}}
        <section>
            <h5 class="section-title">{{__('payment_info.credit')}}</h5>
            <div class="row">
                {{--                <div class="col-md-4">--}}
                {{--                    <div class="form-group">--}}
                {{--                        <label>Банк получателя</label>--}}
                {{--                        <select name="" class="form-control modified">--}}
                {{--                            <option value="" selected disabled>Выберите банк получателя</option>--}}
                {{--                        </select>--}}
                {{--                    </div>--}}
                {{--                </div>--}}
                <div class="col-md-4">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group" style="position: relative">
                                <label>{{__('payment_info.receiver_brand')}}</label>
                                <input
                                    type="text"
                                    placeholder="{{__('payment_info.receiver_brand_placeholder')}}"
                                    class="form-control modified mb-4"
                                    @input="searchCompany"
                                />
                                <div
                                    v-if="brandLoading" 
                                    class="spinner-border text-primary" 
                                    role="status"
                                    style="position: absolute; left: 92%; top: 13%;"
                                >
                                </div>

                                <div class="just-padding" :class="{ 'is-invalid': !brandModel }">
                                    <div 
                                        v-for="{id, brand, accounts } in brandList"
                                        class="list-group list-group-root well"
                                        :key="id"
                                        @click="onSelected(id)" 
                                    >
                                        <a  class="list-group-item" 
                                            :class="{'brand-selected': id === selectedBrandId }"
                                        >
                                            @{{ brand }}
                                        </a>
                                        <div 
                                            v-for="{ id:account_id, name } in accounts"
                                            class="list-group"
                                            :key="account_id"
                                            @click.stop="onSelected(id, account_id, accounts); "
                                        >
                                            <a class="list-group-item"
                                            :class="{'brand-selected': account_id === selectedBrandId }"
                                            >
                                                &#x2022; @{{name}}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <!-- <div class="modified">
                                    <ul>
                                      <li v-for="{brand, accounts} in brandList">
                                        @{{brand}}
                                        <ul>
                                          <li v-for="{name} in accounts">
                                            @{{ name }}
                                          </li>
                                        </ul>
                                      </li>
                                    </ul>
                                </div> -->
                                
                                <!-- <select
                                    :class="{ 'is-invalid': !brandModel }"
                                    id="companies"
                                    class="form-control modified"
                                    ref="companies" size="10" @change="onSelected"
                                >
                                    <option v-for="{id, brand, accounts} in brandList" :key="id" :value="id">
                                        @{{brand }}<br/>    
                                        <template v-if="accounts.length">
                                            <template v-for="{ id, name } in accounts" :key="id" >
                                                &#x2022; @{{ name }}<br/>
                                            </template>
                                        </template>
                                    </option>
                                </select> -->

                                <div class="pl-2 invalid-feedback">@{{validateField()}}</div>
                                {{--                        <multiselect--}}
                                {{--                            bg-color="grey"--}}
                                {{--                            class="modified single"--}}
                                {{--                            v-model="brandModel"--}}
                                {{--                            label="brand"--}}
                                {{--                            track-by="id"--}}
                                {{--                            :loading="brandListLoading"--}}
                                {{--                            :multiple="false"--}}
                                {{--                            :limit-text="count => `and ${count} more`"--}}
                                {{--                            :options="brandList"--}}
                                {{--                            deselect-label="Отменить выбор"--}}
                                {{--                            selected-label="Выбрано"--}}
                                {{--                            select-label=""--}}
                                {{--                            placeholder="Начните вводить для поиска "--}}
                                {{--                            :allowEmpty="false"--}}

                                {{--                            @select="brandSelected"--}}
                                {{--                        >--}}
                                {{--                            <span slot="noResult">@{{i18n.buyer.validations.no_result}}</span>--}}
                                {{--                            <span slot="noOptions">@{{i18n.buyer.validations.no_select_options}}</span>--}}
                                {{--                            <div slot-scope="props" slot="option" class="d-flex align-items-center justify-content-between">--}}
                                {{--                                <span>@{{ props?.option?.brand }}</span>--}}
                                {{--                            </div>--}}
                                {{--                        </multiselect>--}}
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>{{__('payment_info.receiver_name')}}</label>
                                <input 
                                    v-model="credit.name"
                                    class="form-control modified" 
                                    type="text"
                                    readonly
                                />
                                <div class="pl-2 invalid-feedback">@{{invalidFields['credit.name']}}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{__('payment_info.receiver_account')}}</label>
                                <input :class="{'is-invalid': invalidFields.hasOwnProperty('credit.account')}"
                                   v-mask="'#### #### #### #### ####'" v-model="credit.account" type="tel" name=""
                                   placeholder="{{__('payment_info.receiver_account_placeholder')}}"
                                   class="form-control modified">
                                <div class="pl-2 invalid-feedback">@{{validateField('credit.account', credit.account)}}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{__('payment_info.receiver_mfo')}}</label>
                                <input :class="{'is-invalid': invalidFields.hasOwnProperty('credit.mfo')}" v-mask="'#####'"
                                       v-model="credit.mfo" 
                                       type="tel"
                                       placeholder="{{__('payment_info.receiver_mfo_placeholder')}}"
                                       class="form-control modified">
                                <div class="pl-2 invalid-feedback">@{{invalidFields['credit.mfo']}}</div>
                            </div>
                        </div>
                    </div>

                    <div v-if="templateFieldVisible" class="row">
                        <div class="col-md-6 col-sm-12">
                            <div class="form-group">
                                <label>{{__('payment_info.template_name')}}</label>
                                <input
                                    class="form-control modified"
                                    type="text"
                                    placeholder="{{__('payment_info.template_name_placeholder')}}"
                                    v-model="templateName"
                                    >
                                <div class="pl-2 invalid-feedback">@{{invalidFields['credit.name']}}</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="form-group mt-4 d-flex">
                                <button
                                    class="btn btn-orange mt-1 btn-block flex-fill"
                                    :disabled="saveTemplateBtnDisabled || invalidFields.hasOwnProperty('credit.account') || invalidFields.hasOwnProperty('credit.mfo')"
                                    @click="openModalTemplate(1)"
                                >
                                    {{__('payment_info.btn_save_template')}}
                                </button>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="form-group mt-4 d-flex">
                                <button
                                    :disabled = "deleteTemplateBtnDisabled"
                                    class="btn btn-danger btn-orange btn-sm mt-1 btn-block flex-fill"
                                    @click="openModalTemplate(0)"
                                >
                                {{__('payment_info.btn_delete_template')}}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>


            </div>
            
            
            <div class="row">
                {{--                <div class="col-md-4">--}}
                {{--                    <div class="form-group">--}}
                {{--                        <label>Наименование получателя</label>--}}
                {{--                        <input v-model="credit.name" type="text" name="" placeholder="Введите наименование получателя"--}}
                {{--                               class="form-control modified">--}}
                {{--                    </div>--}}
                {{--                </div>--}}
                {{--                <div class="col-md-8">--}}
                {{--                    <div class="form-group">--}}
                {{--                        <label class="d-inline-flex align-items-center justify-content-start"--}}
                {{--                               style="gap:8px; height: 56px; margin-top:1.5rem;">--}}
                {{--                            <input type="checkbox" name="">--}}
                {{--                            <span>Филиал 00974; Счет отправителя 22640000900001190007 являются верными </span>--}}
                {{--                        </label>--}}
                {{--                    </div>--}}
                {{--                </div>--}}
            </div>
            <hr>
        </section>


        {{-- ПРОЧЕЕ -------------------------------------------------------------------------- --}}
        <section>
            <h5 class="section-title">{{__('payment_info.other')}}</h5>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{__('payment_info.sum')}}</label>
                        <input
                            v-model="credit.amount"
                            type="text"
                            name=""
                            :class="{'is-invalid': invalidFields.hasOwnProperty('credit.amount')}"
                            placeholder="{{__('payment_info.sum_placeholder')}}"
                            class="form-control modified integer-mask-with-digit"
                            onkeyup="num2str(this.value.replaceAll(' ', ''), 'amountInString')"
                        >
                        <div class="pl-2 invalid-feedback">@{{ invalidFields['credit.amount']}}</div>
                    </div>
                </div>
                {{--                <div class="col-md-4">--}}
                {{--                    <div class="form-group">--}}
                {{--                        <label>Код назначения платежа</label>--}}
                {{--                        <input type="text" name="" placeholder="Введите код назначения платежа"--}}
                {{--                               class="form-control modified">--}}
                {{--                    </div>--}}
                {{--                </div>--}}
                <div class="col-md-8">
                    <div class="form-group">
                        <label>{{__('payment_info.payment_purpose')}}</label>
                        <input v-model="credit.detail"
                               :class="{'is-invalid': invalidFields.hasOwnProperty('credit.detail')}" type="text"
                               placeholder="{{__('payment_info.payment_purpose_placeholder')}}"
                               class="form-control modified">
                        <div class="pl-2 invalid-feedback">@{{invalidFields['credit.detail']}}</div>
                    </div>
                </div>
            </div>
        </section>

        <section>
            <p style="margin-bottom: 2rem">
            {{__('payment_info.sum_in_words')}}
                <span style="color:var(--orange)" id="amountInString" ref="amountInString"></span>
            </p>

            <button :disabled="Object.keys(invalidFields).length > 0 || !brandModel"
                    class="btn px-3 d-inline-flex align-items-center justify-content-between btn-orange mb-3"
                    @click="openModal">
                    {{__('payment_info.btn_pay')}}
            </button>

            <hr>
        </section>

        {{-- ИСТОРИЯ ПЛАТЕЖЕЙ -------------------------------------------------------------------------- --}}
        <section>
            <h5 class="section-title">{{__('payment_info.history_payment')}}</h5>
            {{--            <div class="row align-items-end">--}}
            {{--                <div class="col-md-5">--}}
            {{--                    <div class="form-group">--}}
            {{--                        <label>Период</label>--}}
            {{--                        <select name="" class="form-control modified">--}}
            {{--                            <option value="" selected disabled>Выберите период</option>--}}
            {{--                        </select>--}}
            {{--                    </div>--}}
            {{--                </div>--}}
            {{--                <div class="col-md-5">--}}
            {{--                    <div class="form-group">--}}
            {{--                        <label>Даты</label>--}}
            {{--                        <input type="date" name="" placeholder="Выберите даты" class="form-control modified">--}}
            {{--                    </div>--}}
            {{--                </div>--}}
            {{--                <div class="col">--}}
            {{--                    <button style="height: 56px;"--}}
            {{--                            class="w-100 btn px-3 d-inline-flex justify-content-center align-items-center btn-orange mb-4">--}}
            {{--                        Применить фильтр--}}
            {{--                    </button>--}}
            {{--                </div>--}}
            {{--            </div>--}}
            <div class="table-responsive">
                <table class="table history-table">
                    <thead>
                    <tr>
                        <th v-for="(col, i) in table_cols" :style="{'width': col.width  || 'auto'}" :key="i">
                            @{{col.name}}
                        </th>
                    </tr>
                    </thead>
                    <tbody v-if="table_rows.length">
                    <tr v-for="(data, i) in table_rows" :key="i"
                        :class="{'row-success': data.status === '01', 'row-error': data.status === '02'}">
                        <td v-for="(col, k) in table_cols"
                            :style="{'width': col.width  || 'auto'}"
                            :class="{'col-success': data.status === '01' && col.key === 'status', 'col-error': data.status === '02' && col.key === 'status'}"
                            :key="k">
                            <div v-if="col.key === 'status' ">
                                <span v-if="data.status === '02' ">{{__('payment_info.error')}}</span>
                                <span v-if="data.status === '01' ">{{__('payment_info.success')}}</span>
                            </div>
                            <a v-else-if="col.key === 'payment_memorial'" :href="data[col.key]">{{__('payment_info.download')}}</a>
                            <span v-else>@{{ data[col.key] }}</span>
                        </td>
                    </tr>
                    </tbody>
                    <tbody v-else>
                    <tr v-if="tableLoading">
                        <td colspan="11" class="p-5 text-orange text-center">{{__('payment_info.history_loading')}}</td>
                    </tr>
                    <tr v-else>
                        <td colspan="11" class="p-5 text-muted text-center">{{__('payment_info.no_data')}}</td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="pagination.pageCount > 1" class="pagination__container" :class="{'disabled': tableLoading}">
                <span v-if="!tableLoading" class="pagination__txt">{{__('payment_info.page')}} @{{ pagination.currentPage || 0 }} из @{{ pagination.pageCount || 0 }}</span>
                <span v-else></span>
                <select-pagination
                    @prev-button="prevButton"
                    @next-button="nextButton"
                    :pagination="pagination"
                ></select-pagination>
            </div>
        </section>
        @include('panel.payment_info.parts.modal_template')
        <div class="modal fade-scale" id="modalConfirm" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document" style="max-width:408px;">
                <div class="modal-content">
                    <input type="hidden" id="deleteID">

                    <div class="modal-header border-0">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                     fill="none">
                                    <path d="M6.66699 6.646L17.333 17.31M6.66699 17.31L17.333 6.646" stroke="#1E1E1E"
                                          stroke-width="1.4" stroke-miterlimit="10" stroke-linecap="round"
                                          stroke-linejoin="round" />
                                </svg>
                            </span>
                        </button>
                    </div>

                    <div class="modal-body px-5 pt-0 text-center">
                        <h5 class="modal-title mb-3">Вы уверены что хотите произвести оплату?</h5>
                        <p>На сумму: <span style="color: var(--orange);" ref="creditAmountInString">0</span>
                            сум?</p>
                    </div>

                    <div class="modal-footer px-4 border-0 pb-4 d-flex justify-content-center" style="gap:16px;">
                        <button style="min-width: 160px;" type="button"
                                class="btn px-3 d-inline-flex  align-items-center justify-content-center btn-orange-light"
                                data-dismiss="modal">{{__('app.no')}}</button>
                        <button style="min-width: 160px;" :class="{'processing':btnLoading}" type="submit"
                                @click="makeTransaction"
                                class="btn px-3 d-inline-flex  align-items-center justify-content-center btn-orange  ">{{__('app.yes')}}
                            <div class="spinner-border "></div>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    </div>



    @include('panel.contract_verify.parts.SelectPagination')
    <script>
    // $("#companies").scroll(function () {
    //     console.log('sadasdasd');
    // })
    const table_cols = [
        { name: 'Дата документа', key: 'created_at', width: '60px' },
        { name: 'Счет', key: 'receiver_account' },
        { name: 'Наименование', key: 'receiver_name' },
        { name: 'Статус', key: 'status', width: '60px' },
        { name: 'Номер документа', key: 'id', width: '60px' },
        { name: 'Тип документа', key: 'payment_type', width: '60px' },
        { name: 'Филиал', key: 'receiver_mfo', width: '60px' },
        { name: 'Оборот Дебет', key: 'amount', width: '116px' },
        { name: 'Оборот Кредит', key: 'credit', width: '116px' },
        { name: 'Назначение', key: 'payment_detail', width: '190px' },
        // { name: 'Кассовый символ', key: 'cash_symbol', width: '60px' },
        { name: 'Мемориалный ордер', key: 'payment_memorial', width: '100px' },
    ];
    const validationRules = {
        'credit.account': [
            (val) => val && val.length > 0 || 'Это поле обязательно для ввода',
            (val) => val && val.length == 20 || 'Счет должен состоять из 20 цифр',
        ],
        'credit.mfo': [
            (val) => val && val.length > 0 || 'Это поле обязательно для ввода',
            (val) => val && val.length == 5 || 'МФО должен состоять из 5 цифр',
        ],
        'credit.name': [
            (val) => val && val.length > 0 || 'Это поле обязательно для ввода',
            (val) => val && val.length > 3 || 'Введите минимум 3 символа',
        ],
        'credit.amount': [
            (val) => val && val.length > 0 || 'Это поле обязательно для ввода',
            (val) => val && Number(val) > 0 || 'Сумма должна быть не менее 1 сум',
        ],
        'credit.detail': [
            (val) => val && val.length > 0 || 'Это поле обязательно для ввода',
            (val) => val && val.length > 3 || 'Введите минимум 3 символа',

        ],
    };
    const payment_order_app = new Vue({
        el: '#payment_order',
        components: {
            'SelectPagination': SelectPagination,
        },
        data: {
            apiToken: globalApiToken,
            paymentLoading: false,
            tableLoading: false,
            brandListLoading: false,
            btnLoading: false,
            brandLoading: false,
            templateFieldVisible: true,
            templateName:'',
            selectedBrandId: '',
            acc_id: null,
            table_cols,
            table_rows: [],
            brandList: [],
            page: 1,
            brandModel: null,
            timeout: null,
            searchQuery: null,
            debit: {
                number: null,
                mfo: null,
                name: null,
                balance: null,
            },
            credit: {
                amount: null,
                account: null,
                mfo: null,
                name: null,
                detail: null,
            },
            pagination: {
                totalCount: 0,
                currentPage: 0,
                pageCount: 12,
                perPage: 10,
            },
        },
        watch: {
            'pagination.currentPage': function (newVal, oldVal) {
                if (newVal != oldVal) {
                    // sessionStorage.setItem('currentPage', this.pagination.currentPage)
                    this.getTransactionList();
                }
            },
        },
        computed: {
            debitBalance() {
                if (!this.debit?.balance) return 0;

                return (this.debit.balance / 100).toLocaleString();
            },
            invalidFields() {
                let fieldList = {};
                for (const field in validationRules) {
                    if (Object.hasOwnProperty.call(validationRules, field)) {
                        let validation = this.validateField(field, this.credit[field.split('.')[1]]);
                        if (validation) fieldList[field] = validation;
                    }
                }

                return fieldList;

            },
            saveTemplateBtnDisabled() {
                return this.templateName?.trim().length > 0 && !!this.brandModel ? false : true;
            },
            deleteTemplateBtnDisabled() {
                return !this.acc_id
            },
        },
        methods: {
            validateField(field, val) {
                if (!val) return 'Это поле обязательно для ввода';
                let rules = validationRules[field];
                for (let i = 0; i < rules.length; i++) {
                    let validate = rules[i](val.replace(/\s/g, ''));
                    if (typeof (validate) == 'string') return validate;
                }
                return false;
            },
            prevButton() {
                if (this.pagination.currentPage > 1) {
                    this.pagination.currentPage -= 1;
                }
            },
            nextButton() {
                if (this.pagination.currentPage < this.pagination.pageCount) {
                    this.pagination.currentPage += 1;
                }
            },
            openModal() {
                if (Object.keys(this.invalidFields).length > 0) return;
                this.$refs.creditAmountInString.innerText = this.$refs.amountInString.innerHTML;
                $('#modalConfirm').modal('show');
            },
            openModalTemplate(type) {
                // if (Object.keys(this.invalidFields).length > 0) return;
                // this.$refs.creditAmountInString.innerText = this.$refs.amountInString.innerHTML;
                type ? $('#saveModalTemplate').modal('show') : $('#deleteModalTemplate').modal('show')
                
            },
            onSelected(id, account_id, accounts) { 
                account_id ? (this.selectedBrandId = account_id, this.acc_id = account_id) : (this.selectedBrandId = id, this.acc_id = null)
                

                this.brandModel = { id };
                this.brandSelected(id, account_id, accounts);
            },
            async searchCompany(e) {
                this.page = undefined
                clearTimeout(this.timeout)

                this.timeout = setTimeout(async () => {
                    if (e.target.value.length < 3) {
                        if (e.target.value === '') {
                            this.searchQuery = undefined
                            this.brandList = await this.fetchCompaniesList()
                        }

                        return
                    }

                    this.searchQuery = e.target.value
                    this.brandList = await this.fetchCompaniesList()
                }, 1000)
            },
            async makeTransaction() {
                this.btnLoading = true;
                const requestData = {
                    ...this.credit,
                    company_id: this.brandModel?.id,
                    account: this.credit?.account.replace(/\s/g, ''),
                    amount: Number(this.credit?.amount?.replaceAll(' ', '')),
                };

                try {
                    await axios.post('/api/v3/admin/transaction/make', requestData, {
                        headers: {
                            Authorization: `Bearer ${this.apiToken}`,
                            'Content-Language': 'ru',
                        },
                    });

                    window.location.reload();
                    this.btnLoading = false;
                } catch (e) {
                    const errorText = e?.response?.data?.error.map(e => e.text).join('\n');

                    if (errorText) {
                        polipop.add({
                            type: 'error',
                            title: `Ошибка`,
                            content: errorText,
                        });

                        $('#modalConfirm').modal('hide');
                    }
                    this.btnLoading = false;
                    this.getTransactionList();
                }

            },
            async deleteTemplate() {
                this.btnLoading = true;
                const headers = {
                    Authorization: `Bearer ${this.apiToken}`,
                    'Content-Language': 'ru',
                }

                try {
                    await axios.delete(`/api/v3/admin/company/accounts/delete/${this.acc_id}`, { headers })
                    
                    window.location.reload();
                    this.btnLoading = false;
                    polipop.add({
                        type: 'success',
                        title: `Удалено`,
                        content: `Удалено успешно!`,
                    });
                }catch(e) {
                    const errorText = e?.response?.data?.error.map(e => e.text).join('\n');
                    if (errorText) {
                        polipop.add({
                            type: 'error',
                            title: `Ошибка`,
                            content: errorText,
                        });
                        $('#deleteModalTemplate').modal('hide');
                    }
                    this.btnLoading = false;
                    this.getTransactionList();
                }
            },
            async editTemplate() {
                this.btnLoading = true;
                const requestData = {
                    name: this.templateName,
                    payment_account: this.credit.account.replaceAll(' ',''),
                    mfo: this.credit.mfo
                }
                const headers = {
                    Authorization: `Bearer ${this.apiToken}`,
                    'Content-Language': 'ru',
                }
                try {
                    await axios.patch(`/api/v3/admin/company/accounts/update/${this.acc_id}`, requestData, {headers})
                    window.location.reload();
                    this.btnLoading = false;
                    
                    polipop.add({
                        type: 'success',
                        title: `Успешно`,
                        content: `Изминено успешно!`,
                    });
                } catch(e) {
                    const errorText = e?.response?.data?.error.map(e => e.text).join('\n');
                    if (errorText) {
                        polipop.add({
                            type: 'error',
                            title: `Ошибка`,
                            content: errorText,
                        });
                        
                        $('#saveModalTemplate').modal('hide');
                    }
                    this.btnLoading = false;
                    this.getTransactionList();
                }
            },
            async saveTemplate() {
                this.btnLoading = true;
                const requestData = {
                    company_id: this.brandModel?.id,
                    name: this.templateName,
                    payment_account: this.credit.account.replaceAll(' ',''),
                    mfo: this.credit.mfo
                }
                try {
                    await axios.post('/api/v3/admin/company/accounts/add', requestData, {
                        headers: {
                            Authorization: `Bearer ${this.apiToken}`,
                            'Content-Language': 'ru',
                        },
                    });

                    window.location.reload();
                    this.btnLoading = false;
                    polipop.add({
                        type: 'success',
                        title: `Успешно`,
                        content: `Сохранено успешно!`,
                    });
                } catch (e) {
                    const errorText = e?.response?.data?.error.map(e => e.text).join('\n');

                    if (errorText) {
                        polipop.add({
                            type: 'error',
                            title: `Ошибка`,
                            content: errorText,
                        });

                        $('#saveModalTemplate').modal('hide');
                    }
                    this.btnLoading = false;
                    this.getTransactionList();
                }
            },
            async brandSelected(id, account_id, accounts) {
                this.brandListLoading = true;
                try {
                    const { data: response } = await axios.get(`/api/v3/admin/company/single/${id}`,
                        {
                            headers: {
                                Authorization: `Bearer ${this.apiToken}`,
                            },
                        });
                        if(account_id){
                            const filteredAccount = accounts.filter((item) => { return item.id==account_id })[0];
                            
                            this.credit.account = filteredAccount.payment_account
                            this.credit.mfo = filteredAccount.mfo
                            this.templateName = filteredAccount.name
                        }else{
                            this.credit.account = response.data.payment_account;
                            this.credit.mfo = response.data.mfo;
                            //clearing templateName field
                            this.templateName = ''
                        } 
                    this.credit.name = response.data.name;
                    this.credit.detail = response.data.header_text;

                    this.brandListLoading = false;
                } catch (e) {
                    console.error(e);
                    this.brandListLoading = false;
                }
            },
            async fetchCompaniesList() {
                this.brandListLoading = true;
                this.brandLoading = true
                try {
                    const { data: response } = await axios.get(`/api/v3/admin/company/list`,
                        {
                            params: {
                                page: this.page,
                                search: this.searchQuery
                            },
                            headers: {
                                Authorization: `Bearer ${this.apiToken}`,
                            },
                        });

                    return response.data?.data || [];

                } catch (e) {
                    console.error(e);
                    this.brandListLoading = false;
                    this.brandLoading = false
                } finally {
                    this.brandListLoading = false;
                    this.brandLoading = false    
                }
            },
            // async fetchCompaniesList(searchParam) {
            //     if (searchParam.replace(/\s/g, "").length < 3) return

            //     this.brandListLoading = true
            //     try {
            //         const { data: response } = await axios.get(`/api/v3/admin/company/list?search=${searchParam}`,
            //             {
            //                 headers: {
            //                     Authorization: `Bearer ${this.apiToken}`,
            //                 },
            //             });

            //         this.brandList = response.data?.data || [];
            //         this.brandListLoading = false
            //     } catch (e) {
            //         console.error(e);
            //         this.brandListLoading = false
            //     }
            // },
            async getTransactionConfig() {
                try {
                    const { data: config } = await axios.get('/api/v3/admin/transaction/config',
                        {
                            headers: {
                                Authorization: `Bearer ${this.apiToken}`,
                            },
                        });

                    this.debit = config.data;
                } catch (e) {
                    console.error(e);
                }
            },
            async getTransactionList() {
                this.tableLoading = true;
                try {
                    const { data: transactionList } = await axios.get('/api/v3/admin/transaction/list',
                        {
                            params: {
                                page: this.pagination?.currentPage || undefined,
                                per_page: this.pagination?.perPage || undefined,
                            },
                            headers: {
                                Authorization: `Bearer ${this.apiToken}`,
                            },
                        });

                    this.table_rows = transactionList.data.data.map(data => ({
                        ...data,
                        created_at: new Date(data.created_at).toLocaleString(),
                        amount: Number(data.amount).toLocaleString(),
                        credit: 0,
                    }));

                    this.pagination.totalCount = transactionList.data.total;
                    this.pagination.currentPage = transactionList.data.current_page;
                    this.pagination.pageCount = transactionList.data.last_page;
                    this.pagination.perPage = transactionList.data.per_page;
                    this.tableLoading = false;
                } catch (e) {
                    this.tableLoading = false;
                    console.error(e);
                }
            },
        },
        mounted() {
            const select = document.querySelector('#companies');

            select?.addEventListener('scroll', async e => {
                if (select.scrollTop + select.clientHeight >= select.scrollHeight) {
                    this.page++;
                    const newCompanies = await this.fetchCompaniesList();
                    this.brandList.push(...newCompanies);
                }
            });
        },
        async created() {
            this.brandList = await this.fetchCompaniesList();
            await this.getTransactionConfig();
            await this.getTransactionList();
        },
    });

    </script>

@endsection
