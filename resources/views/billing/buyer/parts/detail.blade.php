<script>
    var app = new Vue({
        el: '#app',
        data: {
            orders: null,
            loading: false,
            params: {},
            status: 'all',
            searchString: null,
            total: null,
            current: 0,
            perPage: 10
        },
        methods: {
            detail(link = null){
                if(link != null)  window.location.href = link;
            },
            updateList() {
                if (!this.loading) {
                    this.loading = true;
                    this.buildParameters();

                    axios.post('/api/v1/orders/list',
                        this.params
                    ).then(response => {
                        if (response.data.status === 'success') {
                            this.orders = response.data.data;
                            this.total = Math.ceil(response.data.response.total/this.perPage);

                            if (this.orders.length > 0)
                                for (i = 0; i < this.orders.length; i++) {
                                    this.orders[i].detailLink = `{{localeRoute('billing.orders.index')}}/${this.orders[i].id}`;
                                    this.orders[i].buyer.link = `{{localeRoute('billing.buyers.index')}}/${this.orders[i].buyer.id}`;
                                }
                            else
                                this.orders = null;
                        }
                        this.loading = false;
                    })
                }
            },
            buildParameters(){
                this.params = {}

                //Status
                switch (this.status) {
                    case 'complete':
                        this.params.params = [
                            {
                                status: [5, 9],
                                partner_id: {{@$partnersId}},
                                user_id: {{@$buyer->id}}
                            }
                        ];
                        break;
                    case 'payment':
                        this.params.params = [
                            {
                                status:     [4, 6,7,8, 9],
                                credit__more: 0,
                                partner_id: {{@$partnersId}},
                                user_id: {{@$buyer->id}}
                            },
                            {
                                query_operation: 'or',
                                status:     [4, 6, 7, 8, 9],
                                debit__more: 0,
                                partner_id: {{@$partnersId}},
                                user_id: {{@$buyer->id}}
                            },
                        ];
                        break;
                    case 'active':
                        this.params.params = [
                            {
                                status: [4, 6,7,8],
                                partner_id: {{@$partnersId}},
                                user_id: {{@$buyer->id}}
                            }
                        ];
                        break;
                    default:
                        this.params.params = [
                            {
                                status: 1,
                                partner_id: {{@$partnersId}},
                                user_id: {{@$buyer->id}}
                            }
                        ];
                        break;
                }

                //Offset && limit
                this.params.limit = this.perPage;
                this.params.offset = this.current*this.perPage;

                this.params.api_token = Cookies.get('api_token');
            },
            changeStatus(status = null){
                this.status = status;
                this.updateList();
            },
            paginate(page = null){
                if(page != null && page >=1 && page <=this.total){
                    this.current = page - 1;
                    this.updateList();
                }
            }
        },
        created: function () {
            this.updateList();
        }
    })
</script>
