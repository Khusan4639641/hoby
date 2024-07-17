@extends('templates.panel.app')

@section('title', 'Счета')

@section('content')
    <style>
        /* The switch - the box around the slider */
        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        /* Hide default HTML checkbox */
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        /* The slider */
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            -webkit-transition: .4s;
            transition: .4s;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            -webkit-transition: .4s;
            transition: .4s;
        }

        input:checked + .slider {
            background-color: var(--primary);
        }

        input:focus + .slider {
            box-shadow: 0 0 1px var(--primary);
        }

        input:checked + .slider:before {
            -webkit-transform: translateX(26px);
            -ms-transform: translateX(26px);
            transform: translateX(26px);
        }

        /* Rounded sliders */
        .slider.round {
            border-radius: 34px;
        }

        .slider.round:before {
            border-radius: 50%;
        }
    </style>
    <style>
        .content .center .center-body {
            padding: 0;
        }

        .filter {
            padding: 15px 42px !important;
            color: var(--primary);
            border-color: var(--primary)!important;
        }
    </style>
    <style>
        .pagination__container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0 20px;
            padding: 1rem 2rem;
        }

        .pagination.modified {
            margin: 0;
            height: 100%;
            padding: 4px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .pagination__txt {
            margin: 0;
            color: var(--secondary);
            font-size: 16px;
        }
        .pagination__item {
            color: var(--orange);
        }
        .pagination__item.active {
            color: #fff;
        }
        .pagination__item a{
            border-radius: 4px;
            color: var(--orange);
            box-shadow: none;
            border: none;
            cursor: pointer;
            background-color: #ff764317;
            transition: all .25s ease-in;
            display: inline-flex;
            align-items: center;
            padding: 8px 14px;
        }
        .pagination__item.active a{
            background-color: var(--orange);
            color: #fff;
        }
        .pagination__item.disabled a, .pagination__container.disabled{
            pointer-events: none;
            filter: grayscale(1);
            opacity: 0.6;
        }
        .pagination__item a:hover{
            background-color: #ff76432b;
        }
        .pagination__item.active a:hover{
            background-color: var(--orange);
        }
        .pagination__item a:active, .pagination__item a:focus{
            box-shadow: none;
            outline: none;
        }

        .mfo table tbody td {
            width: 170px;
        }

        .mfo table tbody td:last-child {
            width: 500px;
        }

    </style>
    <style>
        .fade-scale {
            transform: scale(.8) translateY(10px);
            opacity: 0;
            -webkit-transition: all .15s linear;
            -o-transition: all .15s linear;
            transition: all .15s linear;
        }
        .form-control.modified+.invalid-feedback {
            animation: slide-down .35s ease-in-out
        }
        @keyframes slide-down {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .form-control.modified.is-invalid {
            background-color: #ffd7d736;
        }
        .form-control.modified.is-invalid:focus {
            border-color: #ff7885;
        }
        .fade-scale.show {
            opacity: 1;
            transform: scale(1) translateY(0px);
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn.processing {
            opacity: .3;
        }
        .btn .spinner-border{
            display: none;
            width: 1rem;
            height: 1rem;
            border: 2px solid currentColor;
            border-right-color: transparent;
        }
        .btn.processing .spinner-border {
            display: inline-block;
        }

        .btn:focus {
            border: 1px solid transparent;
        }
        button:focus {
            outline: none;
            box-shadow: none;
        }

        .form-group label {
            font-style: normal;
            font-weight: 400;
            font-size: 15px;
            line-height: 24px;
            letter-spacing: 0.01em;
            color: #2A2A2A;
        }

        .spinner-border {
            width: 20px;
            height: 20px;
            border: 1px solid currentColor;
            border-right-color: transparent;
        }

        .btn.disabled {
            cursor: not-allowed;
            pointer-events: none;
            opacity: .65;
        }

        .form-control.modified {
            border-radius: 14px;
            height: 56px;
            line-height: 56px;
            padding: 16px !important;
        }

        .form-control.modified.is-invalid {
            box-shadow: none !important;
            border: 1px solid #ff97a1 !important;
        }

        section {
            margin-bottom: 1rem;
        }

        section hr {
            margin-left: -2rem;
            margin-right: -2rem;
        }

        section .section-title {
            font-family: 'Gilroy';
            font-style: normal;
            font-weight: 700;
            font-size: 24px;
            line-height: 30px;
            color: #1E1E1E;
            margin-bottom: 1.5rem;
        }

        .btn {
            border-radius: 14px !important;
            padding: 15px 42px !important;
        }

        .overlay {
            z-index: 9999;
        }

        .modal {
            top: 0;
        }

        .multiselect__tags {
            border-radius: 14px;
            border: none;
            height: 56px;
            line-height: 56px;
            padding: 16px !important;
            background: #F6F6F6;
        }

        .multiselect__single {
            background: #F6F6F6 !important;
        }

        .multiselect__tags input {
            background: #F6F6F6;
        }

        .multiselect-table table tbody td{
            padding: 10px;
        }

    </style>

    <div class="mfo" id="mfo">
        <div class="mfo__header mb-3" style="padding: 30px 30px 0 30px">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <ul class="nav nav-tabs m-0 p-0">
                    <li class="nav-item" v-for="(link, index) in navLinks" :key="index">
                        <a :href="'#'" :class="link.value == clickedNavLink ? 'nav-link active' : 'nav-link'" @click="clickedNavLink = link.value"> @{{ link.title }}</a>
                    </li>
                </ul>

                <div
                    class="d-flex align-items-center border p-2 filter"
                    style="border-radius: 14px"
                >
                    <p class="m-0 mr-1" style="width: 110px">МФО:</p>
                    <select
                        class="my-select border-0"
                        style="width: 100%"
                        {{--                    v-model="filteredCompanyId"--}}
                    >
                        >
                        <option :value="null">OOO МФО «SHAFFOF-MOLIYA»</option>
                        {{--                    <option v-for="company in companies" :key="company.value">@{{ companies.title }}</option>--}}
                    </select>
                </div>
            </div>
            <button v-if="clickedNavLink === CONSTANTS.ACCOUNT_STATUS_NUMBER" class="btn btn-primary" @click="window.location.href = '{{ route('panel.accounts.create', [app()->getLocale()]) }}'">Добавить</button>
            <button v-if="clickedNavLink === CONSTANTS.MASK_STATUS_NUMBER" class="btn btn-primary" @click="window.location.href = '{{ route('panel.accounts.create-mask', [app()->getLocale()]) }}'">Добавить</button>
            <template  v-if="clickedNavLink === CONSTANTS.REMAINDERS_BY_ACCOUNTS" >
                <button class="btn btn-primary" @click="$('#remainderAddModal').modal('show')">Добавить</button>
                <button class="btn btn-primary" @click="$('#recalculateModal').modal('show')">Перерасчитать</button>
            </template>
        </div>

        <div v-if="clickedNavLink === CONSTANTS.ACCOUNT_STATUS_NUMBER"  >
            <data-table :columns="masksColumns" :rows="rows">

                <template v-slot:actions="{item, rowIndex}">
                    <button v-if="!item.isEditing" class="btn btn-outline-primary" @click.stop="onEditRow(item.id, rowIndex)" style="width: 200px">Редактировать</button>
                    <button v-else class="btn btn-outline-primary" @click.stop="onSaveEditedRow(item.id, rowIndex)" style="width: 200px">Сохранить</button>
                    <button v-if="!item.isEditing" class="btn btn-outline-danger" @click.stop="onRemoveRow(item.id)">Удалить</button>
                    <button v-if="item.isEditing" class="btn btn-outline-danger" @click.stop="onCancelEdit(item.id, rowIndex)">Отменить</button>
                </template>

            </data-table>

        </div>

        <data-table v-if="clickedNavLink === CONSTANTS.MASK_STATUS_NUMBER" :columns="columns" :rows="rows">

            <template v-slot:actions="{item, rowIndex}">
                <button v-if="!item.isEditing" class="btn btn-outline-primary" @click.stop="onEditRow(item.id, rowIndex)" style="width: 200px">Редактировать</button>
                <button v-else class="btn btn-outline-primary" @click.stop="onSaveEditedRow(item.id, rowIndex)" style="width: 200px">Сохранить</button>
                <button v-if="!item.isEditing" class="btn btn-outline-danger" @click.stop="onRemoveRow(item.id)">Удалить</button>
                <button v-if="item.isEditing" class="btn btn-outline-danger" @click.stop="onCancelEdit(item.id, rowIndex)">Отменить</button>
            </template>

        </data-table>

        <data-table class="remainder-table" v-if="clickedNavLink === CONSTANTS.REMAINDERS_BY_ACCOUNTS" :columns="remainderColumns" :rows="rows">

            <template v-slot:actions="{item, rowIndex}">
                <button v-if="!item.isEditing" class="btn btn-outline-primary" @click.stop="openRemainderRow(item.id)" style="width: 200px">Перерасчитать</button>
                <button v-if="!item.isEditing" class="btn btn-outline-primary" @click.stop="onEditRow(item.id, rowIndex)" style="width: 200px">Редактировать</button>
                <button v-else class="btn btn-outline-primary" @click.stop="onSaveEditedRow(item.id, rowIndex)" style="width: 200px">Сохранить</button>
                <button v-if="!item.isEditing" class="btn btn-outline-danger" @click.stop="onRemoveRow(item.id)">Удалить</button>
                <button v-if="item.isEditing" class="btn btn-outline-danger" @click.stop="onCancelEdit(item.id, rowIndex)">Отменить</button>
            </template>

        </data-table>

        <div class="modal fade"  id="remainderAddModal" tabindex="-1" role="dialog" aria-labelledby="remainderAddModal" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLongTitle">Добавить</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="isRemainderModalOpened = false">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <label for="" class="font-size-15">Счет МФО</label>
                        <multiselect
                            v-model="isRemainderModalData.mfo_account_id"
                            class="modified"
                            :options="remainderAccounts"
                            label="id"
                            track-by="id"
                            placeholder="Выбрать"
                            select-label=""
                            selected-label="Выбрано"
                            deselect-label="Нажмите Enter для удаления"
                        >
                            <template slot="option" slot-scope="{ option }">
                                <table class="multiselect-table">
                                    <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Номер МФО счета</th>
                                        <th>Номер 1с счета</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr>
                                        <td>@{{ option.id }}</td>
                                        <td>@{{ option.number }}</td>
                                        <td>
                                            <span v-for="number in option.account_1c_numbers"> @{{  number }} </span>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </template>
                        </multiselect>
                        <div class="d-flex mt-2">
                            <div class="form-group" style="width: 60%">
                                <label for="">Стартовый баланс</label>
                                <input class="form-control modified" type="number" v-number-only v-model="isRemainderModalData.balance">
                            </div>
                            <div class="form-group" style="width: 40%">
                                <label for="">Операционный день</label>
                                <input
                                    class="form-control modified"
                                    type="date"
                                    @keypress.prevent
                                    v-model="isRemainderModalData.operation_date"
                                    :min="isRemainderModalDateMin"
                                    :max="isRemainderModalDateMax"
                                >
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" >Закрыть</button>
                        <button type="button" class="btn btn-primary" @click="onAddRemainderAccount" :disabled="isLoading">Добавить</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="recalculateModal" tabindex="-1" role="dialog" aria-labelledby="recalculateModal" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Перерасчитать</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="isRemainderModalOpened = false">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="">Операционный день c:</label>
                            <input
                                class="form-control modified"
                                type="date"
                                @keypress.prevent
                                v-model="start_date"
                                :min="isRemainderModalDateMin"
                                :max="isRemainderModalDateMax"
                            >
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" @click="isRemainderModalOpened = false">Закрыть</button>
                        <button type="button" class="btn btn-primary" @click="onRecalculateData" :disabled="isLoading || !start_date">Перерасчитать</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="progressRecalculationModal"  data-backdrop="static" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-body">
                        <h4>
                            @{{ progressData.status == 'in_progress' ? ' Перерасчет начался' : 'Перерасчет завершен' }}
                        </h4>
                        <p>
                            Пожалуйста подождите...
                        </p>
                        <div class="progress mb-3" style="height: 2rem" >
                            <div class="progress-bar" role="progressbar" :aria-valuenow="progressData.percentage" :style="`width: ${progressData.percentage}%; background: var(--primary)`"  aria-valuemin="0" aria-valuemax="100">@{{ progressData.percentage }}%</div>
                        </div>
                        <pre class="font-size-12 mb-1">Статус: @{{ progressData.status == 'in_progress' ? 'Обрабатывается...' : 'Процесс завершен.' }}</pre>
                        <pre class="font-size-12 mb-1">Осталось: @{{ progressData.estimated_remaining_time }}</pre>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="pagination.pageCount > 1" class="pagination__container" :class="{'disabled': isLoading}" >
            <span v-if="!isLoading"  class="pagination__txt">страница @{{ pagination.currentPage || 0 }} из @{{ pagination.pageCount || 0 }}</span>
            <span v-else ></span>
            <select-pagination
                @prev-button="prevButton"
                @next-button="nextButton"
                :pagination="pagination"
            ></select-pagination>
        </div>

    </div>

    @include('panel.components.DataTable')
    @include('panel.components.SelectPagination')

    <script>
        const CONSTANTS = {
            ACCOUNT_STATUS_NUMBER: 1,
            MASK_STATUS_NUMBER: 2,
            REMAINDERS_BY_ACCOUNTS: 3
        }
        const api = axios.create({
            headers: {
                Authorization: `Bearer ${globalApiToken}`,
                'Content-Language': '{{app()->getLocale()}}',
                "Access-Control-Allow-Origin": "*"
            },
        })
        const app = new Vue({
            el: "#mfo",
            components: {
                "DataTable": DataTable,
            },
            data: {
                selectedRemainderAccount: '',
                pagination: {
                    totalCount: 0,
                    currentPage: 1,
                    pageCount: 0,
                },
                isLoading: false,
                navLinks: [
                    { title: "Сопоставление счетов МФО с 1С", value: 1 },
                    { title: "Сопоставление масок счетов", value: 2 },
                    { title: "Остатки по счетам", value: 3 },
                ],
                columns: [
                    { title: 'ID', key: 'id' },
                    { title: 'Счет МФО', key: 'mfo_account_number'},
                    { title: 'Счет 1C', key: 'account_1c_number'},
                    { title: 'Наименование 1С счета', key: 'account_1c_name'},
                    { title: 'Тип учетной карточки', key: 'account_type'},
                    { title: 'Системный номер уч.карточки', key: 'account_system_number'},
                    { title: 'Субконто', key: 'is_subconto'},
                    { title: 'Номер субконто', key: 'subconto_number'},
                    { title: 'Субконто без остатков', key: 'is_subconto_without_remainder'},
                    { title: 'Действия', key: 'actions'},
                ],
                rows: [],
                clickedNavLink: CONSTANTS.REMAINDERS_BY_ACCOUNTS,
                companies: [
                    { title: 'OOO МФО «SHAFFOF-MOLIYA»', value: null }
                ],
                filteredCompanyId: null,
                masksColumns: [
                    { title: 'ID', key: 'id' },
                    { title: 'Маска МФО', key: 'mfo_mask'},
                    { title: 'Номер 1С счета', key: '1c_mask'},
                    { title: 'Parent ID', key: 'parent_id'},
                    { title: 'Номер субконто', key: 'number'},
                    { title: 'Название счета', key: 'mfo_account_name'},
                    { title: 'Действия', key: 'actions'},
                ],
                // third tab data
                start_date: null,
                isRemainderModalData: {
                    mfo_account_id: null,
                    operation_date: null,
                    balance: null,
                },
                isRemainderModalDateMin: null,
                isRemainderModalDateMax: null,
                remainderColumns: [
                    { title: 'ID', key: 'id' },
                    { title: 'ID МФО счета', key: 'mfo_account', deep: 'id', readonly: true },
                    { title: 'Номер счета', key: 'mfo_account', deep: 'number' , readonly: true },
                    { title: 'Стартовый баланс', key: 'earliest_balance', readonly: false },
                    { title: 'Операционный день', key: 'operation_date', readonly: true },
                    { title: 'Текущий баланс', key: 'current_balance', readonly: true },
                    { title: 'Действия', key: 'actions' },
                ],
                remainderAccounts: [],
                progressData: {
                    status: 'in_progress',
                    percentage: 0,
                    estimated_remaining_time: '00:00'
                },
                recalculateTimout: null,
                recalulcatingRowId: null,
            },
            watch: {
                'clickedNavLink': function(newVal, oldVal) {
                    if (newVal !== oldVal){
                        this.fetchData()
                    }
                },
                 'pagination.currentPage': function (newVal, oldVal) {
                    if (newVal !== oldVal) {
                        this.fetchData()
                    }
                },
            },
            methods: {
                getCurrentDate(lastMonth){
                    const today = new Date()
                    const dd = String(today.getDate()).padStart(2, '0')
                    const mm = String(today.getMonth() + 1).padStart(2, '0') //January is 0!
                    const yyyy = today.getFullYear()
                    const previousMonthLastDay = new Date(today.setDate(0)).getDate()
                    const previousMonth = String(new Date(today.setMonth(today.getMonth())).getMonth() + 1).padStart(2, '0')
                    if (lastMonth){
                        return `${yyyy}-${previousMonth}-${previousMonthLastDay}`
                    }
                    return `${yyyy}-${mm}-${dd}`
                },
                // first tab
                fetchAccountData(params){
                    api.get('/api/v3/accounts', { params }).then(({ data: response }) => {
                        this.rows = response.data.map(row => ({
                            ...row, isEditing: false,
                        }))
                        this.pagination.totalCount = response.total
                        this.pagination.currentPage = response.current_page
                        this.pagination.pageCount = response.last_page
                        this.pagination.perPagee = response.per_page
                    }).catch(err => {
                        err.response.data.errors.forEach((error) => console.error(error))
                    })
                },
                removeAccountRow(id){
                    api.delete(`/api/v3/accounts/${id}`).then(response => {
                        if (response.data.status === 'success'){
                            polipop.add({title: `Запись успешно удалено`, type: 'success'})
                            this.fetchData()
                        }
                    }).catch(err => {
                        console.error(err)
                        err.response.data.errors.forEach((error) => console.error(error))
                        polipop.add({title: `Ошибка`, type: 'error'})
                    })
                },
                onRemoveRow(id){
                    if(window.confirm('Вы действительно хотите удалить запись?')){
                        axios.delete(`/api/v3/accounts/${id}`, { headers: { Authorization: `Bearer ${globalApiToken}`} }).then(response => {
                            if (response.data.status == 'success'){
                                polipop.add({title: `Успешно удалено`, type: 'success'})
                                this.fetchData()
                            }
                        })
                    }
                editAccountRow(id, rowIndex) {
                    api.patch(`/api/v3/accounts/${id}`, this.rows[rowIndex]).then(response => {
                        if (response.data.status === 'success'){
                            polipop.add({title: `Запись успешно изменено`, type: 'success'})
                            this.fetchData()
                            this.rows[rowIndex].isEditing = false
                        }
                    }).catch(err => {
                        console.error(err)
                        err.response.data.errors.forEach((error) => console.error(error))
                        polipop.add({title: `Ошибка`, type: 'error'})
                    })
                },
                // second TAB
                fetchAccountMatchData(params) {
                    api.get('/api/v3/admin/account-match/list', { params }).then(({ data: response }) => {
                        this.rows = response.data.data.map(row => ({
                            ...row, isEditing: false,
                        }))
                        this.pagination.totalCount = response.data.total
                        this.pagination.currentPage = response.data.current_page
                        this.pagination.pageCount = response.data.last_page
                        this.pagination.perPagee = response.data.per_page
                    }).catch(err => {
                        console.error(err)
                        err.response.data.errors.forEach((error) => console.error(error))
                        polipop.add({title: `Ошибка`, type: 'error'})
                    })
                },
                removeAccountMatchRow(id){
                    api.delete(`/api/v3/admin/account-match/delete/${id}`).then(response => {
                        if (response.data.status === 'success'){
                            polipop.add({title: `Запись успешно удалено`, type: 'success'})
                            this.fetchData()
                        }
                    }).catch(err => {
                        console.error(err)
                        err.response.data.errors.forEach((error) => console.error(error))
                        polipop.add({title: `Ошибка`, type: 'error'})
                    })
                },
                editAccountMatchRow(id, rowIndex){
                    api.post(`/api/v3/admin/account-match/update/${id}`, this.rows[rowIndex]).then(response => {
                        if (response.data.status === 'success'){
                            polipop.add({title: `Запись успешно изменено`, type: 'success'})
                            this.fetchData()
                            this.rows[rowIndex].isEditing = false
                        }
                    }).catch(err => {
                        console.error(err.response.data.errors)
                        err.response.data.errors.forEach((error) => console.error(error))
                        polipop.add({title: `Ошибка`, type: 'error'})
                    })
                },
                // third tab
                async onRecalculateData() {
                    this.progressData.percentage =  0
                    this.progressData.status = 'in_progress'
                    this.progressData.estimated_remaining_time = '00:00'
                    this.isLoading = true
                    try {
                        const {data: response} = await api.post(`/api/v3/admin/accounts/balances/calculate/${this.recalulcatingRowId ?? ''}`, {
                            start_date: this.start_date
                        })
                        if (response.status === 'success') {
                            $('#progressRecalculationModal').modal('show')
                            this.start_date = null
                            this.recalculateTimout = setInterval(() => {
                                this.getRecalculateProgress(response.data.process_id)
                            }, 10000);
                        }
                    } catch (err) {
                        console.error(err.response)
                        err.response.data.error.forEach(({text}) => {
                            polipop.add({content: text, title: `Ошибка`, type: 'error'})
                            console.error(text)
                        })
                    } finally {
                        this.isLoading = false
                        $('#recalculateModal').modal('hide')
                    }
                },
                openRemainderRow(id){
                    $('#recalculateModal').modal('show');
                    this.recalulcatingRowId = id
                },
                async getRecalculateProgress(process_id){
                    try {
                        const { data: response } = await api.get(`/api/v3/admin/accounts/balances/calculate/status/${process_id}`)
                        this.progressData.percentage =  response.data.percentage
                        this.progressData.status = response.data.status
                        this.progressData.estimated_remaining_time = response.data.estimated_remaining_time
                        if (response.data.status === 'finished') {
                            clearInterval(this.recalculateTimout)
                            this.fetchData()
                            setTimeout(() => {
                                $('#progressRecalculationModal').modal('hide');
                            }, 5000)
                        }
                    } catch(err){
                        console.error(err.response)
                        err.response.data.error.forEach(({text}) => {
                            polipop.add({content: text,title: `Ошибка`, type: 'error'})
                            console.error(text)
                        })
                    }
                },
                async onAddRemainderAccount() {
                    this.isLoading = true
                    try {
                        const data = {...this.isRemainderModalData, mfo_account_id: this.isRemainderModalData.mfo_account_id.id}
                        const { data: response } = await api.post('/api/v3/admin/accounts/balances', data)
                        if (response.status === 'success'){
                            polipop.add({title: `Данные успешно добавлены!`, type: 'success'})
                            this.fetchData()
                            $("#remainderAddModal").modal('hide')
                        }
                    } catch (err) {
                        err.response.data.error.forEach(({text}) => {
                            polipop.add({content: text,title: `Ошибка`, type: 'error'})
                            console.error(text)
                        })
                    } finally {
                        this.isLoading = false;
                    }
                },
                async fetchAccountRemainderData(params) {
                    try {
                        const { data: response } = await api.get('/api/v3/admin/accounts/balances', { params })
                        this.rows = response.data.balances.map(row => ({
                            ...row,
                            isEditing: false,
                        }))
                        this.pagination.totalCount  = response.data.pagination.total
                        this.pagination.currentPage = response.data.pagination.current_page
                        this.pagination.pageCount   = response.data.pagination.last_page
                        this.pagination.perPagee    = response.data.pagination.per_page
                    } catch(err){
                        err.response.data.error.forEach(({text}) => {
                            polipop.add({content: text, title: `Ошибка`, type: 'error'})
                            console.error(text)
                        })
                    }
                },
                async fetchRemainderAccounts() {
                    try {
                        const { data: response } = await api.get('/api/v3/admin/accounts')
                        this.remainderAccounts = response.data
                    } catch(err){
                        err.response.data.error.forEach(({text}) => {
                            polipop.add({content: text, title: `Ошибка`, type: 'error'})
                            console.error(text)
                        })
                    }
                },
                async removeRemainderRow(id){
                    try {
                        const response = await api.delete(`/api/v3/admin/accounts/balances/${id}`)
                            if (response.status === 204){
                                polipop.add({title: `Запись успешно удалено`, type: 'success'})
                                this.fetchData()
                            }
                    } catch(err){
                        err.response.data.error.forEach(({text}) => {
                            polipop.add({content: text, title: `Ошибка`, type: 'error'})
                            console.error(text)
                        })
                    }
                },
                async editRemainderRow(id, rowIndex){
                    try {
                        const response = await api.put(`/api/v3/admin/accounts/balances/${id}`, this.rows[rowIndex])
                        if (response.data.status === 'success'){
                            polipop.add({title: `Запись успешно изменено`, type: 'success'})
                            this.fetchData()
                            this.rows[rowIndex].isEditing = false
                        }
                    } catch(err) {
                        console.error(err.response)
                        err.response.data.error.forEach(({text}) => {
                            polipop.add({content: text,title: `Ошибка`, type: 'error'})
                            console.error(text)
                        })
                    }
                },
                // third tab end
                // ALL functionality
                fetchData() {
                    let params = {
                        page: this.pagination?.currentPage || undefined,
                        per_page: this.pagination?.perPage || undefined,
                    }
                    switch(this.clickedNavLink) {
                        case CONSTANTS.ACCOUNT_STATUS_NUMBER: {
                            this.fetchAccountData(params)
                            break
                        }
                        case CONSTANTS.MASK_STATUS_NUMBER:  {
                            this.fetchAccountMatchData(params)
                            break
                        }
                        case CONSTANTS.REMAINDERS_BY_ACCOUNTS:  {
                            this.fetchAccountRemainderData(params)
                            break
                        }
                    }
                },
                onRemoveRow(id){
                    if (!window.confirm("Вы действительно хотите удалить запись?")){
                        return
                    }
                    switch(this.clickedNavLink) {
                        case CONSTANTS.ACCOUNT_STATUS_NUMBER: {
                            this.removeAccountRow(id)
                            break
                        }
                        case CONSTANTS.MASK_STATUS_NUMBER:  {
                            this.removeAccountMatchRow(id)
                            break
                        }
                        case CONSTANTS.REMAINDERS_BY_ACCOUNTS:  {
                            this.removeRemainderRow(id)
                            break
                        }
                    }
                },
                onSaveEditedRow(id, rowIndex){
                    switch(this.clickedNavLink) {
                        case CONSTANTS.ACCOUNT_STATUS_NUMBER: {
                            this.editAccountRow(id, rowIndex)
                            break
                        }
                        case CONSTANTS.MASK_STATUS_NUMBER:  {
                            this.editAccountMatchRow(id, rowIndex)
                            break
                        }
                        case CONSTANTS.REMAINDERS_BY_ACCOUNTS:  {
                            this.editRemainderRow(id, rowIndex)
                            break
                        }
                    }
                },
                onCancelEdit (id, rowIndex) {
                    this.rows[rowIndex].isEditing = false
                },
                onEditRow(id, rowIndex){
                    this.rows[rowIndex].isEditing = true
                },
                prevButton () {
                    if (this.pagination.currentPage > 1) {
                        this.pagination.currentPage -= 1
                    }
                },
                nextButton () {
                    if (this.pagination.currentPage < this.pagination.pageCount) {
                        this.pagination.currentPage += 1
                    }
                },
            },
            created() {
                if (this.clickedNavLink === CONSTANTS.REMAINDERS_BY_ACCOUNTS) this.fetchRemainderAccounts()
                this.isRemainderModalDateMin = this.getCurrentDate(true)
                this.isRemainderModalDateMax = this.getCurrentDate()
                this.fetchData()
            }
        })
    </script>

@endsection
