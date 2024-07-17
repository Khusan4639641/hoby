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
            perPage: 10,
            hash: '#active'
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
                        this.params,
                        {headers: {'Content-Language': '{{app()->getLocale()}}'}}
                    ).then(response => {
                        if (response.data.status === 'success') {
                            this.orders = response.data.data;
                            this.total = Math.ceil(response.data.response.total/this.perPage);

                            if (this.orders.length > 0)
                                for (let i = 0; i < this.orders.length; i++) {
                                    this.orders[i].detailLink = `{{localeRoute('cabinet.orders.index')}}/${this.orders[i].id}`;
                                }
                            else
                                this.orders = null;

                            //console.log(this.orders);
                        }
                        this.loading = false;
                    })
                }
            },
            buildParameters(){
                this.params = {}

                //Status
                switch (this.status) {
                    case 'credit':
                       this.params.params = [
                           {
                               'contract|status': 1,
                               status: [4, 6, 7, 8, 9],
                               user_id: {{Auth::user()->id}},
                           }
                       ];
                        break;
                    case 'complete':
                        this.params.params = [
                            {
                                status: [5, 9],
                                user_id: {{Auth::user()->id}}
                            },
                            {
                                query_operation: "or",
                                status: [5, 9],
                                'contract|status' : [5, 9],
                                user_id: {{Auth::user()->id}}
                            }
                        ];


                        break;
                    case 'approve':
                        this.params.params = [

                            {
                                query_operation: "or",
                                status: 1,
                                user_id: {{Auth::user()->id}}
                            }
                        ];
                        break;
                    default:
                        this.params.params = [
                            {
                                'contract|status': 1,
                                status: [2, 3, 4, 6, 7, 8],
                                user_id: {{Auth::user()->id}}
                            },
                            {
                                query_operation: "or",
                                status: [2, 3, 4, 6, 7, 8],
                                user_id: {{Auth::user()->id}}
                            }
                        ];
                        break;
                }

                //Search
                if(this.searchString != null)
                    this.params.id__like = this.searchString;

                //Offset && limit
                this.params.limit = this.perPage;
                this.params.offset = this.current*this.perPage;



                this.params.orderByDesc = 'created_at';

                this.params.api_token = Cookies.get('api_token');
            },
            changeStatus(status = null){
                this.status = status;
                this.hash = '#' + status;
                this.updateList();
            },
            paginate(page = null){
                if(page != null && page >=1 && page <= this.total){
                    this.current = page - 1;
                    this.updateList();
                }
            }
        },
        created: function () {
            this.hash = location.hash!=null?location.hash:'#active';
            this.changeStatus(this.hash.replace('#', ''));
        },
        computed: {
            hash: function (){
                this.hash = location.hash!=null?location.hash:'#active';
                this.changeStatus(this.hash.replace('#', ''));
            }
        }
    })
</script>
