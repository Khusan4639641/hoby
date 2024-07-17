@extends('templates.panel.app')

@section('title', 'Коллекторы')
@section('class', 'collectors')

@section('content')
    <div id="collectors" class="collectors__wrapper">
        <form class="input-group mb-3 collectors__search" @submit="startSearch">
            <input v-model="search" type="search" class="form-control" placeholder="Поиск коллектора..." aria-label="Поиск коллектора..." aria-describedby="collectors-search">
            <div class="input-group-append">
                <button :disabled="loading" class="btn btn-primary" type="submit" id="collectors-search">Поиск</button>
            </div>    
        </form>
        <table class="table contract-list">
            <thead>
                <tr>
                    <th>№</th>
                    <th>Ф.И.О.</th>
                    <th>{{ __('panel/contract.phone') }}</th>
                    <th>Район</th>
                    <th>Регион</th>
                    <th>Баланс</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(collector, i) in collectors" :key="collector.id">
                    <td>@{{ collectorIndex(i) }}</td>
                    <td>@{{ fullName(collector) }}</td>
                    <td>@{{ collector.phone }}</td>
                    <td>
                        <p v-for="katmRegion in collector.katm_regions" :key="katmRegion.id">
                            @{{ katmRegion.local_region_name }}
                        </p>
                    </td>
                    <td>
                        <p v-for="katmRegion in collector.katm_regions" :key="katmRegion.id">
                            @{{ katmRegion.region_name }}
                        </p>
                    </td>
                    <td>@{{ collector.balance }} сум</td>
                    <td>
                        <button
                            class="btn btn-primary btn-plus btn-sm btn-block"
                            data-toggle="modal" data-target="#change-region-modal"
                            @click="currentCollector = collector"
                        >
                            Назначить регион
                        </button>
                    </td>
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

        <div id="change-region-modal" class="modal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">@{{ currentCollector && fullName(currentCollector) }}</h5>
                        <button
                            type="button"
                            class="close"
                            data-dismiss="modal"
                            aria-label="Close"
                            @click="reset"
                        >
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <label>
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

                        <label v-if="currentRegion">
                            <span>Район:</span>
                            <multiselect
                                v-model="currentLocalRegion"
                                trackBy="local_region"
                                label="local_region_name"
                                :options="localRegionsFiltered"
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
                            @click="reset"
                        >
                            Закрыть
                        </button>
                        <button
                            type="button"
                            class="btn btn-primary"
                            :disabled="!currentLocalRegion"
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
        const regions = @json($regions);
        const localRegions = @json($local_regions);
        const apiToken = @json(Auth::user()->api_token);
    </script>

    @include('panel.recovery.collectors.parts.collectorsVue')
@endsection
