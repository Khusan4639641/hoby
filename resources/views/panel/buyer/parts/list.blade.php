<script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/3.1.9-1/core.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/3.1.9-1/md5.js"></script>
<script>
    const apiToken = Cookies.get('api_token');
    const verifiedIconPath = @json(asset('images/icons/icon_ok_circle_green.svg'));
    const alertIconPath = @json(asset('images/icons/icon_attention.svg'));

    const tabNames = {
        all: "{{__('panel/buyer.tab_status_all')}}",
        need_verification: "{{__('panel/buyer.tab_status_2')}}",
        verified:"{{__('panel/buyer.tab_status_4')}}",
        photo: "{{__('panel/buyer.tab_status_5')}}",
        denied: "{{__('panel/buyer.tab_status_8')}}"
    };

    const app = new Vue({
        el: "#app",
        data: {
            apiToken,
            perPageOptions: [10, 15, 20, 25, 30],
            totalCount: 1,
            currentPage: 1,
            perPage: 15,
            isLoading: false,
            clickedTab: null,
            tabs: [
                { label: tabNames.all, name:"#all", status: null },
                { label: tabNames.need_verification, name:"#need_verification", status: 2 },
                { label: tabNames.verified, name:"#verified", status: 4 },
                { label: tabNames.photo, name:"#photo", status: 5 },
                { label: tabNames.denied, name:"#denied", status: 8 }
            ],
            status: 2,
            isOrder: false,
            buyer: {
                id: "",
                name: "",
                surname: "",
                phone: "",
                passportNumber: "",
            },
            buyersList: [],
        },
        computed: {
            pageCount() {
             return Math.ceil(this.totalCount / this.perPage)
            },
        },
        watch: {
          currentPage() {
              this.buyerSearch(true)
          },
        },
        methods: {
            async buyerSearch(isPaginated =  false) {
                let buyerData =  {
                    buyer_id: this.buyer.id != '' ? this.buyer.id : undefined,
                    name: this.buyer.name != '' ? this.buyer.name : undefined,
                    surname: this.buyer.surname != '' ? this.buyer.surname : undefined,
                    phone: this.buyer.phone != '' ? this.buyer.phone : undefined,
                    passport_number: this.buyer.passportNumber != ''  ? CryptoJS.MD5(this.buyer.passportNumber).toString() : undefined,
                    status: this.clickedTab ?? undefined,
                }
                isPaginated ? buyerData = {...buyerData, page: this.currentPage} : buyerData
                try {
                    this.isLoading = true
                    const { data: response } = await axios.post(`/api/v1/employee/buyers/search`, buyerData,
                        {
                            headers: {
                                'Authorization': `Bearer ${this.apiToken}`,
                                'Content-Language': '{{app()->getLocale()}}'
                            }
                        }
                    );
                    this.buyerList = []
                    this.buyersList = Array.isArray(response.data) ? response.data : [response.data];
                    this.totalCount = response.meta?.total ? response.meta?.total : [response.data].length;
                }catch (err) {
                    this.buyersList = []
                    this.totalCount = 0
                    err.response.data.error.forEach( error => polipop.add({content: error.text, title: `Ошибка`, type: 'error'}))
                }
                finally {
                    this.isLoading = false
                }

            },
            filterBy(status) {
                this.clickedTab = status;
                this.currentPage = 1;
                this.buyerSearch(false);
            },
            // pagination
            prevButton () {
                if (this.currentPage > 1) {
                    this.currentPage -= 1
                }
            },
            nextButton () {
                if (this.currentPage < this.pageCount) {
                    this.currentPage += 1
                }
            },
        },
        async created() {
            await this.buyerSearch()
        },
    });

</script>
