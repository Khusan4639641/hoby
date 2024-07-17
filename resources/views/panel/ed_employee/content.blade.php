@extends('templates.billing.app')
@section('content')

    <style>
        .nav-link.disabled {
            pointer-events: none;
            filter: grayscale(1);
            opacity: .3;
        }
        .left-menu {
            display: none;
        }
        .pagination__container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0 20px;
            padding: 1rem 2rem;
        }
        .badge{
            display: inline-flex;
            align-items: center;
            text-align: center;
            justify-content: center;
            padding: 4px 12px;
            width: 107px;
            height: 24px;
            border-radius: 6px;
            font-family: 'Gilroy';
            font-style: normal;
            font-weight: 400;
            font-size: 12px;
            line-height: 16px;
        }
        .badge.badge-success {
            background-color: #53DB8C1A !important;
            color: #53DB8C;
        }
        .badge.badge-danger {
            background-color: #F84343 !important;
            color: #F84343;
        }
        .content .center .center-body {
            padding: 0;
        }
        .table-loader {
            position: relative;
            height: 2px;
            overflow: hidden;
            margin-top: -2px;
        }
        .table-loader.processing::before {
            animation: q-linear-progress--indeterminate 2.1s cubic-bezier(.65,.815,.735,.395) infinite;
            background: var(--orange);
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            transform-origin: 0 0;
        }
        .table-loader.processing::after {
            transform: translate3d(-101%,0,0) scaleZ(1);
            animation: q-linear-progress--indeterminate-short 2.1s cubic-bezier(.165,.84,.44,1) infinite;
            animation-delay: 1.15s;
            background: var(--orange);
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            transform-origin: 0 0;
        }
        @keyframes q-linear-progress--indeterminate{
            0%{
                transform:translate3d(-35%,0,0) scale3d(.35,1,1)
            }
            60%{
                transform:translate3d(100%,0,0) scale3d(.9,1,1)
            }
            to{
                transform:translate3d(100%,0,0) scale3d(.9,1,1)
            }
        }
        @keyframes q-linear-progress--indeterminate-short {
            0%{
                transform:translate3d(-101%,0,0) scaleZ(1)
            }
            60%{
                transform:translate3d(107%,0,0) scale3d(.01,1,1)
            }
            to{
                transform:translate3d(107%,0,0) scale3d(.01,1,1)
            }
        }
        input::-webkit-datetime-edit-day-field:focus,
        input::-webkit-datetime-edit-month-field:focus,
        input::-webkit-datetime-edit-year-field:focus {
            background-color: var(--orange);
            color: white;
            outline: none;
        }
        .input-with-prepend{
            display: flex;
            align-items: center;
        }
        .input-with-prepend input.form-control.modified{
            padding-left: 40px;
        }
        .input-with-prepend::before{
            content: attr(data-prefix);
            position: absolute;
            padding: 0 12px;
            display: inline-flex;
            align-items: center;
            transition: 0.4s;
            color: #b1b1b1;
        }
        .input-with-prepend:focus-within:before{
            color: var(--orange)
        }
        .table-hover tbody tr:hover {
            color: #212529;
            background-color: rgb(0 0 0 / 3%);
        }
        .btn:focus {
            border: 1px solid transparent;
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

        .modal-backdrop.show {
            opacity: .2;
        }

        .modal-content {
            border: none !important;
            border-radius: 16px;
            outline: 0;
            box-shadow: 0px 16px 20px rgb(0 0 0 / 20%);
        }

        .modal__title {
            font-style: normal;
            font-weight: 700;
            font-size: 18px;
            line-height: 19px;
            color: #1E1E1E;
        }

        .btn.disabled {
            cursor: not-allowed;
            pointer-events: none;
            opacity: .65;
        }

        .form-control.modified {
            border-radius: 14px;
            /* height: 56px; */
            /* line-height: 56px; */
            /* padding: 16px !important; */
        }

        .form-control.modified.is-invalid {
            box-shadow: none !important;
            border: 1px solid #ff97a1 !important;
        }

        select.form-control.modified {
            cursor: pointer;
        }

        section {
            margin-bottom: 1rem;
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

        .form-group {
            width: 100%;
            margin-bottom: 1.5rem;

        }

        .btn {
            border-radius: 14px !important;
            /* padding: 15px 42px !important; */
        }
        .sticky-header {
            position: sticky;
            top: 88px;
            background: #fff;
            z-index: 9;
            margin: 0;
        }
        table.ed-table th {
            font-family: 'Gilroy';
            font-style: normal;
            font-weight: 500;
            font-size: 12px;
            line-height: 16px;
            color: #888888;
            vertical-align: middle;
            border: none;
        }

        table.ed-table td {
            font-family: 'Gilroy';
            font-style: normal;
            font-weight: 500;
            font-size: 12px;
            line-height: 16px;
            color: #1E1E1E;
            vertical-align: middle;
            padding: 8px;
        }

        table.ed-table tr td:first-child, table.ed-table tr th:first-child {
            padding-left: 1.5rem !important;
        }

        table.ed-table tr td:last-child, table.ed-table tr th:last-child {
            padding-right: 1.5rem !important;
        }

        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
        }
        .loading-overlay::after {
            content: 'Загрузка...';
            color: var(--orange)
        }
        .analytics_card {
            padding: 16px;
            background: var(--peach);
            border-radius: 14px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.04);
            margin-bottom: 1rem;
            transition: all .3s ease-out
        }
        .analytics_card.processing {
            min-height: 84px;
            box-shadow: none;
            position: relative;
            overflow: hidden;
            width: 100%;
        }
        .analytics_card.processing .title, .analytics_card.processing .amount {
            display: none;
        }
        .analytics_card.processing:after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            height: 100%;
            background: linear-gradient(
                120deg,
                var(--peach) 30%,
                #6610f529 38%,
                #6610f529 40%,
                var(--peach) 48%
            );
            background-size: 200% 100%;
            background-position: 100% 0;
            animation: run 2s infinite;
        }
        .analytics_card .title{
            font-style: normal;
            font-weight: 400;
            font-size: 14px;
            line-height: 17px;
            color: #7000ffad;
            /* color: #1E1E1E; */
        }
        .analytics_card .amount{
            font-style: normal;
            font-weight: 700;
            font-size: 16px;
            line-height: 19px;
            color: var(--orange);
            margin: 0;
            text-align: right;
        }
        @keyframes run {
            100% {
                background-position: -100% 0;
            }
        }
        .btn.processing {
            cursor: disabled;
            position: relative;
            padding-left: 3rem;
            pointer-events: none;
            opacity: .7;
        }
        .btn .spinner {
            width: 1rem;
            height: 1rem;
            border: 2px solid currentColor;
            border-right-color: transparent;
            display: none;
            position: absolute;
            top: calc(50% - 0.5rem);
            left: 1rem;
        }
        .btn.processing .spinner {
            display: inline-block;
        }

    </style>

    <section class="ed" id="ed_employee">
        <div class="page-header sticky-header p-4 border-bottom" >
            <div class="row align-items-center justify-content-between">
                <div class="left col-md-6">
                    <ul class="nav nav-tabs m-md-0" role="tablist">
                        <li class="nav-item" v-for="(tab, i) in tab_list" :key="`nav_link_${i}`">
                            <a class="nav-link cursor-pointer" @click="toggleTab(tab.key)" :class="{'active': tab.key === tab_model, 'disabled':tableLoading}" id="home-tab">@{{tab.name}}</a>
                        </li>
                    </ul>
                </div>
                <div class="right col-md-6 d-inline-flex justify-content-end" style="gap: 16px;">
                    {{-- <button @click="updateTransactions" class="btn btn-orange" :class="{'processing': updateTransactionsLoading }" style="height: 50px">
                        <div class="spinner-border spinner mr-4" role="status">
                            <span class="sr-only">Загрузка...</span>
                        </div>
                        <span v-if="!updateTransactionsLoading">Обновить транзакции</span>
                        <span v-else >Обновляется...</span>
                        
                        
                    </button> --}}
                    <label class="input-with-prepend" data-prefix="с">
                        <input @keydown.prevent="return false" @click="showPicker" v-model="date_from.value" type="date" :min="date_from.min" :max="date_from.max" class="form-control modified">
                    </label>
                    <label class="input-with-prepend" data-prefix="по">
                        <input  @keydown.prevent="return false" @click="showPicker" v-model="date_to.value" type="date" :min="date_to.min" :max="date_to.max" class="form-control modified">
                    </label>
                    <button @click="downloadReport" :class="{'processing': reportLoading }" class="btn btn-orange d-inline-flex align-items-center justify-content-center" style="height:50px;">
                        <div class="spinner-border spinner mr-4" role="status">
                            <span class="sr-only">Загрузка...</span>
                        </div>
                        Скачать отчет
                    </button>
                </div>
            </div>
        </div>

        <div class="cards-container p-4">
            <div class="row">
                <div class="col-sm-4 col-md-3 col-xl-2" v-for="(value, key) of analytics">
                    <div class="analytics_card" :class="{'processing':tableLoading}">
                        <p class="title">@{{analytics_dict[key]}}</p>
                        <p class="amount">@{{formatCurrency(value)}}</p>

                    </div>
                </div>
            </div>
        </div>

        <hr class="mb-0 h-auto">
        <div class="table-loader" :class="{'processing':tableLoading}"></div>
        <div class="table-responsive pb-4 position-relative" >
            <div class="loading-overlay" v-if="tableLoading"></div>
            <table class="table ed-table" :class="{'table-hover':!tableLoading && table_rows.length}">
                <thead>
                <tr>
                    <th v-for="(col, i) in calculated_table_cols" :style="{'width': col.width  || 'auto'}" :key="i">
                        @{{col.name}}
                    </th>
                </tr>
                </thead>
                <tbody v-if="!tableLoading && table_rows.length">
                    <tr v-for="(data, i) in table_rows" :key="i">
                        <td v-for="(col, k) in calculated_table_cols"
                            :style="{'width': col.width  || 'auto'}"
                            :class="col.class  || 'auto'"
                            :key="k">
                            <span v-if="col.hasOwnProperty('customRender')">@{{ col.customRender(data[col.key], data) }}</span>
                            <span v-else-if="col.key === 'status'">
                                <span v-if="data[col.key] == 1" class="badge badge-success"> Подтвержден</span>
                                <span v-else class="badge badge-error">Не подтвержден</span>
                            </span>
                            <span v-else>@{{ data[col.key] }}</span>
                        </td>
                    </tr>
                </tbody>
                <tbody v-else>
                    <tr>
                        <td :colspan="calculated_table_cols.length">
                            <div class="empty d-flex align-items-center justify-content-center text-muted" style="min-height: 50vh">
                                <h5>Нет @{{ tab_model == 'credit' ? 'выписек':'платежей' }}</h5>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="pagination.pageCount > 1" class="pagination__container p-4 " :class="{'disabled': tableLoading}">
            <span v-if="!tableLoading" class="pagination__txt">страница @{{ pagination.currentPage || 0 }} из @{{ pagination.pageCount || 0 }}</span>
            <span v-else></span>
            <select-pagination
                @prev-button="prevButton"
                @next-button="nextButton"
                :pagination="pagination"
            ></select-pagination>
        </div>

    </section>
    @include('panel.contract_verify.parts.SelectPagination')
    @include('panel.ed_employee.parts.content_script')
@endsection
