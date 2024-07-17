<script>
  const collectorsApp = new Vue({
    el: '#collector-transactions',
    data() {
      return {
        transactions: [],
        collector: collectorData,
        contract: contractData,
        loading: false,
        pagination: {
            currentPage: 1,
            lastPage: 1,
        }
      }
    },
    mounted() {
      this.setPage()
    },
    methods: {
      fullName({ name, surname, patronymic } = {}) {
          return `${name} ${surname} ${patronymic}`
      },
      loadTransactions() {
        this.loading = true

        return new Promise((resolve, reject) => {
          axios.get(`/api/v1/recovery/collectors/${this.collector.id}/contracts/${this.contract.id}/transactions`, {
            params: {
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
            this.transactions = data.data
            this.pagination.lastPage = data.last_page

            resolve()
          })
          .finally(() => {
            this.loading = false
          })
        })
      },
      locationUrl(content) {
          const [ latitude, longitude ] = JSON.parse(content)
          return `https://www.google.com/maps/embed/v1/place?key=AIzaSyCmPVj8x_36_1XwHpJXHir4FpPkr7_xuZI&q=${latitude},${longitude}`
      },
      setPage() {
        this.loadTransactions()
            .then(() => {
                window.scrollTo({
                    top: 0,
                    left: 0,
                    behavior: 'smooth'
                })
            })
      }
    },
  })
</script>
