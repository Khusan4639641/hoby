<script>
  const collectorsApp = new Vue({
    el: '#collector-contracts',
    data() {
      return {
        contracts: [],
        regions,
        localRegions,
        currentRegion: null,
        currentLocalRegion: null,
        loading: false,
        pagination: {
            currentPage: 1,
            lastPage: 1,
        },
        attaching: {
          currentRegion: null,
          currentLocalRegion: null,
          currentContract: null,
        }
      }
    },
    mounted() {
      this.setPage()
    },
    methods: {
      transactionsUrl(contractId) {
          return transactionsRawUrl.replace('contract_id', contractId)
      },
      fullName({ name, surname, patronymic } = {}) {
        return `${name} ${surname} ${patronymic}`
      },
      regionName(regionId) {
        return this.regions.find((region) => region.region === regionId)?.region_name
      },
      localRegionName(localRegionId) {
        return this.localRegions.find((region) => region.local_region === localRegionId)?.local_region_name
      },
      loadContracts() {
        this.loading = true

        return new Promise((resolve, reject) => {
          axios.get('/api/v1/recovery/contracts', {
            params: {
              region: this.currentRegion && this.currentRegion.region,
              local_region: this.currentLocalRegion && this.currentLocalRegion.local_region,
              page: this.pagination.currentPage
            },
            headers: {
              Authorization: `Bearer ${apiToken}`,
            },
          })
          .catch((error) => {
            console.log(error)
            reject(error)
          })
          .then(({ data }) => {
            this.contracts = data.contracts
            this.pagination.lastPage = data.last_page

            resolve()
          })
          .finally(() => {
            this.loading = false
          })
        })
      },
      attachingRegion(contract) {
        this.attaching.currentContract = contract
        $('#attach-region-modal').modal('show')
      },
      resetRegionAttaching() {
        this.attaching.currentContract = null
        $('#attach-region-modal').modal('hide')
      },
      setKatmRegionId() {
        console.log(this.attaching)
          axios.patch(`/api/v1/recovery/contracts/${this.attaching.currentContract.id}/katm-region`, 
          {
              katm_region_id: this.attaching.currentLocalRegion.id
          }, {
              headers: {
                  Authorization: `Bearer ${apiToken}`,
              }
          })
          .then(() => {
              this.loadContracts()
              this.resetRegionAttaching()
          })
      },
      resetContracts() {
        this.pagination.currentPage = 1
      },
      filterContracts() {
        this.resetContracts()
        this.loadContracts()
      },
      priceFormat(price) {
        return new Intl.NumberFormat('ru-Ru', {
          minimumFractionDigits: 2,
        }).format(price)
      },
      dateFormat(date) {
        return moment(date, null, true).isValid() ? moment(date, null, true).format('DD.MM.YYYY') : ''
      },
      monthFormat(month) {
        return new Intl.NumberFormat('ru-Ru', {
          minimumIntegerDigits: 2,
        }).format(month)
      },
      setPage() {
        this.loadContracts()
            .then(() => {
                window.scrollTo({
                    top: 0,
                    left: 0,
                    behavior: 'smooth'
                })
            })
      }
    },
    computed: {
      localRegionsFiltered() {
        return this.localRegions.filter((localRegion) => localRegion.region === (this.currentRegion && this.currentRegion.region))
      },
      attachingLocalRegionsFiltered() {
        return this.localRegions.filter((localRegion) => localRegion.region === (this.attaching.currentRegion && this.attaching.currentRegion.region))
      }
    }
  })
</script>
