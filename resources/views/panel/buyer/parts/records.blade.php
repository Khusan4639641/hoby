<style>
    .buyers.show .center-body .tab-content{
        padding: 0;
    }

    #records {
        margin: 0 -2rem;
    }

    #records .table th {
        font-size: 14px !important;
        background: #f2f2f2;
        padding: 0.5rem;
        font-weight: 500;
    }

    #records .table p {
        margin: 0;
    }

    #records .table > tbody > tr > td {
        padding: 1rem;
        font-size: 14px;
        vertical-align: middle !important;
        height: 60px;
    }

    #records .table tr td table {
        padding: 0;
        margin: 0;
    }

    #records .ymap-container {
        height: 500px;
        width: 100%;
    }

    #records .my-modal {
        width: 600px;
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

<div id="records" class="position-relative">
    <table class="table table-hover">
        <thead>
        <tr>
            <th class="col-md-1">{{__('panel/contract.contract_id')}}</th>
            <th class="col-md-2">{{__('panel/contract.employee')}}</th>
            <th class="col-md-1">{{__('panel/contract.date')}}</th>
            <th class="col-md-1">{{__('panel/contract.action_type')}}</th>
            <th class="col-md-1">{{__('panel/contract.call_result')}}</th>
            <th class="col-md-3">{{__('panel/contract.content')}}</th>
        </tr>
        </thead>
        <tbody v-if="dataRows.length">
            <tr
                v-for="(row, idx) in computedDataRows"
                :key="idx"
                class="p-2"
            >
                <td>@{{ row.contract_id ? row.contract_id : "-" }}</td>
                <td>@{{ row.user ?? "-" }}</td>
                <td>@{{ row.created_at ?? "-" }}</td>
                <td>@{{ typeList[row.type] ?? "-" }}</td>
                <td>@{{ row.call_result ?? "-" }}</td>
                <td>
                    <template v-if="row.type === 'date'">
                        <p  class="d-inline" v-for="(value, key, index) in JSON.parse(row.content)">
                            <strong> @{{ contentTranslations[key] }}:</strong> @{{ value }}
                            <span v-show="index !== Object.keys(JSON.parse(row.content)).length - 1">/</span>
                        </p>
                    </template>
                    <button v-else-if="row.type === 'photo'" class="btn btn-sm btn-primary" @click="makePhotoViewer(row.files.url)">{{__('panel/contract.btn_show')}}</button>
                    <button v-else-if="row.type === 'location'" class="btn btn-sm btn-primary" @click="makeMapViewer(row.content)">{{__('panel/contract.btn_show')}}</button>
                    <p v-else>
                        <strong>@{{ contentTranslations.date }}:</strong> @{{ row.payment_date ?? "-"}} / <strong>@{{ contentTranslations.amount }}:</strong> @{{ row.payment_value ?? "-" }} /  @{{ row.content.length ? row.content : "-" }} /  @{{ row.info ?? "-" }}
                    </p>
                </td>
            </tr>
        </tbody>

        <tbody v-else>
        <tr>
            <td colspan="12">
                <h4 class="text-center">@{{ isLoading ? "Загрузка..." : "Таблица пуста" }}</h4>
            </td>
        </tr>
        </tbody>
        <tfoot>
            <tr v-if="pagination.lastPage > 1">
                <td colspan="13">
                    <paginate
                        :page-count="pagination.lastPage"
                        v-model="pagination.currentPage"
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

    <div v-if="showOverlay" class="overlay">
        <div class="my-modal">
            <h4 class="my-modal__title">{{__('panel/contract.marked_location')}}</h4>
            <template v-if="coords">
                <yandex-map
                    :coords="coords"
                    zoom=10
                >
                    <ymap-marker
                        :coords="coords"
                        marker-type="placemark"
                    />
                </yandex-map>
            </template>
            <div class="my-modal__footer">
                <button @click="toggleOverlay(false)" class="btn btn-primary">
                    {{__('panel/contract.btn_close')}}
                </button>
            </div>
        </div>
    </div>

</div>

<script src="https://unpkg.com/vue-yandex-maps@0.11.13/dist/vue-yandex-maps.min.js"></script>

<script>
    const typeList = {
        'location': @json(__('panel/contract.added_location')),
        'photo':  @json(__('panel/contract.added_photo')),
        'text':  @json(__('panel/contract.added_text')),
        'date':  @json(__('panel/contract.added_date')),
        'call_in_iiv':  @json(__('panel/contract.added_comments')),
        'delta': @json(__('panel/contract.call_center')),
        'merchant': @json(__('panel/contract.merchant')),
    };

    const contentTranslations = {
        'date': @json(__('panel/contract.date')),
        'amount':  @json(__('panel/contract.amount')),
        'comment':  @json(__('panel/contract.comments')),
    }

    let app = new Vue({
        el: '#records',
        data: {
            apiToken: globalApiToken,
            buyerId: @json($buyer->id),
            dataRows: [],
            isLoading: false,
            selectValue: null,
            showOverlay: false,
            coords: [],
            typeList,
            contentTranslations,
            pagination: {
                perPage: 12,
                currentPage: 1,
                lastPage: 1,
            },
        },
        computed: {
            computedDataRows(){
                let endIndex = this.pagination.perPage*this.pagination.currentPage
                let startIndex = endIndex-this.pagination.perPage
                return this.dataRows.slice(startIndex, endIndex)
            }
        },
        methods: {
            toggleOverlay(boolean){
                this.showOverlay = boolean
            },
            makePhotoViewer(src) {

                const items = [
                    { src, title: "Фото" },
                ];

                const options = {
                    index: 0,
                    fixedModalSize: true,
                    modalWidth: 600,
                    modalHeight: 600,
                    resizable: false,
                };
                new PhotoViewer(items, options);
            },
            makeMapViewer(coords) {
                this.showOverlay = true
                this.coords = JSON.parse(coords)
            },
            async fetchCollectorInfo(){
                this.isLoading = true
                try {
                    const resp = await axios.get(`/api/v3/debt-collector/debtors/${this.buyerId}/actions`, {
                        params: {
                            api_token: this.apiToken
                        }
                    })
                    this.dataRows = resp.data
                    this.pagination.lastPage = Math.ceil(this.dataRows.length/this.pagination.perPage)
                }catch (err) {
                    console.error(err)
                }finally {
                    this.isLoading = false
                }
            }
        },
        async mounted() {
            await this.fetchCollectorInfo()
        }
    })

</script>

