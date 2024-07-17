<script>
    const collectorsApp = new Vue({
        el: '#collectors',
        data() {
            return {
                collectors: [],
                regions,
                localRegions,
                currentCollector: null,
                currentRegion: null,
                currentLocalRegion: null,
                pagination: {
                    currentPage: 1,
                    lastPage: 1,
                    perPage: 1
                },
                search: '',
                loading: true
            }
        },
        mounted() {
            this.setPage()
        },
        methods: {
            setPage() {
                this.loadCollectors()
                    .then(() => {
                        window.scrollTo({
                            top: 0,
                            left: 0,
                            behavior: 'smooth'
                        })
                    })
            },
            fullName({ name, surname, patronymic }) {
                return `${name} ${surname} ${patronymic}`
            },
            reset() {
                this.currentCollector = null
                this.currentLocalRegion = null
                this.currentRegion = null
            },
            loadCollectors() {
                this.loading = true
                return new Promise((resolve, reject) => {
                    axios.get('/api/v1/recovery/collectors', {
                        params: {
                            page: this.pagination.currentPage,
                            search: this.search
                        },
                        headers: {
                            Authorization: `Bearer ${apiToken}`,
                        },
                    })
                    .then(({ data }) => {
                        this.collectors = data.data
                        this.pagination.currentPage = data.current_page
                        this.pagination.perPage = data.per_page
                        this.pagination.lastPage = data.last_page
                        resolve()
                    })
                    .catch((error) => {
                        console.log(error)
                        reject(error)
                    })
                    .finally(() => {
                        this.loading = false
                    })
                })
            },
            setKatmRegionId() {
                axios.post(`/api/v1/recovery/collectors/${this.currentCollector.id}/katm-regions`, 
                {
                    katm_region_id: this.currentLocalRegion.id
                }, {
                    headers: {
                        Authorization: `Bearer ${apiToken}`,
                    }
                })
                .then(() => {
                    this.reset()
                    this.loadCollectors()
                    $('#change-region-modal').modal('hide')
                })
            },
            startSearch(e) {
                e.preventDefault()
                this.pagination.currentPage = 1
                this.loadCollectors()
            },
            collectorIndex(i) {
                return ((this.pagination.currentPage - 1) * this.pagination.perPage) + (i + 1)
            }
        },
        computed: {
            localRegionsFiltered() {
                return this.localRegions.filter((localRegion) => localRegion.region === this.currentRegion.region)
            }
        }
    })
</script>
