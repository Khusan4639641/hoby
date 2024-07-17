@extends('templates.panel.app')

@section('title', __('panel/contract.header_contracts_recovery'))
@section('class', 'collectors')

@section('content')
    <div id="collector-contracts" class="collectors__wrapper">
        <div class="collectors__filters">
            <label class="collectors__filter-block">
                <span>Регион:</span>
                <multiselect
                    v-model="currentRegion"
                    trackBy="region"
                    label="region_name"
                    :options="regions"
                    :hideSelected="true"
                    :allowEmpty="false"
                    selectLabel="Нажмите Enter для выбора"
                    selectedLabel="Выбрано"
                    deselectLabel="Нажмите Enter для удаления"
                    @input="currentLocalRegion = null"
                    placeholder="Выберите регион"
                ></multiselect>
            </label>

            <label class="collectors__filter-block">
                <span>Район:</span>
                <multiselect
                    v-model="currentLocalRegion"
                    trackBy="local_region"
                    label="local_region_name"
                    :options="localRegionsFiltered"
                    :disabled="!currentRegion"
                    :hideSelected="true"
                    :allowEmpty="false"
                    selectLabel="Нажмите Enter для выбора"
                    selectedLabel="Выбрано"
                    deselectLabel="Нажмите Enter для удаления"
                    placeholder="Выберите район"
                ></multiselect>
            </label>
            <button
                class="btn btn-primary collectors__filter-block collectors__filter-block--button"
                @click="filterContracts"
            >
                Фильтровать
            </button>
        </div>
        <table class="table contract-list">
            <thead>
                <tr>
                    <th>{{__('panel/contract.date')}}</th>
                    <th>{{__('panel/contract.contract_id')}}</th>
                    <th>Регион</th>
                    <th>Район</th>
                    <th>{{__('panel/contract.partner')}}</th>
                    <th>{{__('panel/contract.client')}}</th>
                    <th>{{__('cabinet/profile.gender_title')}}</th>
                    <th>{{__('cabinet/profile.birthday')}}</th>
                    <th>{{__('panel/contract.phone')}}</th>
                    <th>{{__('panel/contract.sum')}}</th>
                    <th>{{__('panel/contract.paid_off')}}</th>
                    <th>{{__('panel/contract.debt')}}</th>
                    <th>{{__('panel/contract.day')}}</th>
                    <th>{{__('panel/contract.status')}}</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="(contract, i) in contracts"
                    :key="contract.id"
                    class="contracts-table__row"
                    :class="{ 'has-collector': contract.has_collector }"
                >
                    <td>@{{ contract.created_at }}</td>
                    <td class="contracts-table__column contracts-table__column--numbers">
                        <a v-if="contract.has_collector" :href="transactionsUrl(contract.id)" class="btn-block">@{{ contract.id }}</a>
                        <span v-else>@{{ contract.id }}</span>
                    </td>
                    <td>@{{ regionName(contract.buyer.region) }}</td>
                    <td>
                        <template v-if="localRegionName(contract.buyer.local_region) !== undefined">
                            @{{ localRegionName(contract.buyer.local_region) }}
                        </template>
                        <template v-else>
                            <button @click="attachingRegion(contract)" class="btn btn-primary">Добавить</button>
                        </template>
                    </td>
                    <td>@{{ contract.company.name }}</td>
                    <td>@{{ fullName(contract.buyer) }}</td>
                    <td>@{{ contract.buyer.gender === 1 ? 'М' : 'Ж' }}</td>
                    <td>@{{ dateFormat(contract.buyer.birth_date) }}</td>
                    <td>@{{ contract.buyer.phone }}</td>
                    <td class="contracts-table__column contracts-table__column--numbers">
                        <span>@{{ priceFormat(contract.total) }} сум</span>
                        <br>
                        <span>@{{ monthFormat(contract.period) }} месяцев</span>
                    </td>
                    <td class="contracts-table__column contracts-table__column--numbers">
                        @{{ priceFormat(contract.payment_sum) }} сум
                    </td>
                    <td class="contracts-table__column contracts-table__column--numbers">
                        @{{ priceFormat(contract.delay_sum) }} сум
                    </td>
                    <td class="contracts-table__column contracts-table__column--numbers">
                        @{{ contract.expired_days }}
                    </td>
                    <td>@{{ contract.status_caption }}</td>
                </tr>
            </tbody>
            <tfoot>
                <tr v-if="pagination.lastPage > 1">
                    <td colspan="13">
                        <paginate
                            :page-count="pagination.lastPage"
                            v-model="pagination.currentPage"
                            :click-handler="setPage"
                            prev-text="< Назад"
                            next-text="Следующая >"
                            container-class="pagination"
                            prev-class="pagination__prev"
                            next-class="pagination__next"
                            page-class="pagination__page"
                            active-class="pagination__page--active"
                            disabled-class="pagination__page--disabled"
                        >
                        </paginate>
                    </td>
                </tr>
            </tfoot>
        </table>

        <div id="attach-region-modal" class="modal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Контракт №@{{ attaching.currentContract && attaching.currentContract.id }}</h5>
                        <button
                            type="button"
                            class="close"
                            data-dismiss="modal"
                            aria-label="Close"
                            @click="resetRegionAttaching"
                        >
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <label>
                            <span>Регион:</span>
                            <multiselect
                                v-model="attaching.currentRegion"
                                trackBy="region"
                                label="region_name"
                                :options="regions"
                                :hideSelected="true"
                                :allowEmpty="false"
                                selectLabel="Нажмите Enter для выбора"
                                selectedLabel="Выбрано"
                                deselectLabel="Нажмите Enter для удаления"
                                @input="attaching.currentLocalRegion = null"
                                placeholder="Выберите регион"
                            ></multiselect>
                        </label>

                        <label v-if="attaching.currentRegion">
                            <span>Район:</span>
                            <multiselect
                                v-model="attaching.currentLocalRegion"
                                trackBy="local_region"
                                label="local_region_name"
                                :options="attachingLocalRegionsFiltered"
                                :hideSelected="true"
                                :allowEmpty="false"
                                selectLabel="Нажмите Enter для выбора"
                                selectedLabel="Выбрано"
                                deselectLabel="Нажмите Enter для удаления"
                                placeholder="Выберите район"
                            ></multiselect>
                        </label>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-secondary"
                            data-dismiss="modal"
                            @click="resetRegionAttaching"
                        >
                            Закрыть
                        </button>
                        <button
                            type="button"
                            class="btn btn-primary"
                            :disabled="!attaching.currentLocalRegion"
                            @click="setKatmRegionId"
                        >
                            Сохранить
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .collectors__filters {
            display: grid;
            grid-template-columns: 2fr 2fr 1fr;
            grid-column-gap: 20px;
            align-items: flex-end;
            margin-bottom: 30px;
        }
        .collectors__filter-block {

        }
        .collectors__filter-block--button {
            margin-bottom: 0.25rem;
            min-height: 40px;
        }

        .collectors__contracts-button {
            position: fixed;
            width: 50px;
            height: 50px;
            bottom: 50px;
            right: 50px;
            background-color: orange;
            border-radius: 50%;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }

        .contracts-table__row {

        }
        .contracts-table__row.has-collector {
          background-color: rgb(66 255 76 / 20%);
        }

        .contracts-table__column {

        }

        .contracts-table__column--numbers {
            text-align: right;
        }

        .pagination {
            display: flex;
            gap: 12px;
        }

        .pagination__prev,
        .pagination__next,
        .pagination__page {
            color: var(--orange);
        }
        .pagination__page--active {
            color: #212529;
        }

        .pagination__page--disabled {
            color: #000;
        }

        .pagination__prev {
            margin-right: 20px;
        }
        .pagination__next {
            margin-left: 20px;
        }
    </style>

    <script>
        const transactionsRawUrl = '{{localeRoute('panel.recovery.collectors.transactions', ['contract' => 'contract_id'])}}'
        const regions = @json($regions);
        const localRegions = @json($local_regions);
        const apiToken = @json(Auth::user()->api_token);
    </script>

    @include('panel.recovery.collectors.parts.contractsVue')
@endsection
