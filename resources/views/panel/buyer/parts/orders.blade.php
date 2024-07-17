<div class="orders list" id="orders">
    <div v-if="loading" class="loading active"><img src="{{asset('images/loader.gif')}}"></div>
    <div v-else>
        <div v-if="orders != null" class="dataTables_wrapper">
            <div class="orders-list">
                <div class="item" v-for="item in orders" :key="item.id">
                    <div class="info">
                        <div class="row">
                            <div class="col-8">
                                <span class="number">{{__('cabinet/order.header_order')}} № @{{ item.id }}</span> <span class="date">{{__('cabinet/order.txt_from')}} @{{ item.created_at }}</span>
                            </div>

                            <div class="col-4 text-right">
                                <span v-if="item.status != 9" :class="'order-status status-'+ item.status">@{{ item.status_caption }}</span>
                                <span v-if="item.contract" :class="'contract-status status-'+ item.contract.status">@{{ item.contract.status_caption }}</span>
                            </div>
                        </div>
                    </div><!-- /.info -->

                    <div v-if="item.contract" class="params">
                        <div class="row">
                            <div v-if=" item.contract.next_payment" class="col part">
                                <div class="caption">{{__('cabinet/order.lbl_payment_amount')}}</div>
                                <div class="value">@{{ item.contract.next_payment.total }}</div>
                            </div>
                            <div v-if=" item.contract.next_payment" class="col part">
                                <div class="caption">{{__('cabinet/order.lbl_payment_date')}}</div>
                                <div class="value">@{{ item.contract.next_payment.payment_date }}</div>
                            </div>
                            <div class="col part">
                                <div class="caption">{{__('cabinet/order.lbl_payments_count')}}</div>
                                <div class="value">@{{ item.contract.active_payments.length }} / @{{ item.contract.schedule.length }}</div>
                            </div>
                            <div class="col part total">
                                <div class="caption">{{__('cabinet/order.lbl_balance')}}</div>
                                <div class="value">@{{ item.contract.balance }}</div>
                            </div>
                            <div class="col part total">
                                <div class="caption">{{__('cabinet/order.lbl_total')}}</div>
                                <div class="value">@{{ item.total }}</div>
                            </div>
                        </div>
                    </div><!-- /.params -->
                    <div v-else="item.contract" class="params">
                        <div class="row">
                            <div class="col part total">
                                <div class="caption">{{__('cabinet/order.lbl_total')}}</div>
                                <div class="value">@{{ item.total }}</div>
                            </div>
                        </div>
                    </div><!-- /.params -->

                    <div class="products">
                        <table class="table">
                            <thead>
                            <th>{{__('cabinet/order.lbl_product_name')}}</th>
                            <th>{{__('cabinet/order.lbl_price')}}</th>
                            <th>{{__('cabinet/order.lbl_amount')}}</th>
                            <th>{{__('cabinet/order.lbl_total')}}</th>
                            <th></th>
                            </thead>
                            <tbody>
                            <tr class="product" v-for="(product, index) in item.products">
                                <td class="name">@{{ product.name }}</td>
                                <td class="price">@{{ product.price }}</td>
                                <td class="amount">x@{{ product.amount }}</td>
                                <td class="total">
                                    @{{ product.price*product.amount }}
                                </td>
                                <td class="controls"></td>
                            </tr>
                            </tbody>
                        </table>
                    </div><!-- /.products -->

                </div><!-- /.item -->
            </div><!-- /.orders-list -->

            <div class="dataTables_paginate">
                <a @click="paginate(current - 1)" :class="'paginate_button previous ' + (current -1 < 1?'disabled':'')" :data-dt-idx="(current-1 >= 1?current-1:1)" tabindex="-1" id="DataTables_Table_0_previous">Предыдущая</a>
                <span v-for="n in total">
                    <a @click="paginate(n)" :class="'paginate_button ' + (n==(current + 1)?'current':'')" :data-dt-idx="n" tabindex="0">@{{ n }}</a>
                </span>
                <a @click="paginate(current + 1)" class="paginate_button next disabled" :data-dt-idx="(current+1 <= total?current+1:total)" tabindex="-1" id="DataTables_Table_0_next">Следующая</a>
            </div>
        </div>
        <div v-else>
            {{__('billing/order.txt_empty_list')}}
        </div>
    </div>
</div>

<script>
    var orders = new Vue({
        el: '#orders',
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


                this.params.params = [
                    {
                        user_id: {{$buyer->id}}
                    }
                ];

                this.params.orderByDesc = "created_at";

                //Offset && limit
                this.params.limit = this.perPage;
                this.params.offset = this.current*this.perPage;


                this.params.api_token = Cookies.get('api_token');
            },
            changeStatus(status = null){
                this.status = status;
                this.hash = '#' + status;
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
