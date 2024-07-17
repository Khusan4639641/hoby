
<script>
    let Product = {
        props: ["product", "index"],
        data: function(){
            return {
                foundedBy: {
                    name: [],
                    code: ""
                },
                foundedByCode: [],
                foundedByName: [],
                clicked: false,
                seeked: false,
            }
        },
        computed: {
            productAmount() {
                // if product category is << smartphones >> we will set amount = 1.
                return this.product.category == 1 ? this.product.amount = 1 : this.product.amount;
            },
        },
        methods: {
            update(item){
                //this.product.id = item.locale.title;
                this.product.name = item.locale.title;
                this.product.price = item.price;
                this.product.price_discount = item.price_discount;
                this.product.weight = item.weight;
                this.product.id = item.id;
                this.product.vendor_code = item.vendor_code;
                this.product.amount = 1;

                this.product.imei = item.imei;
                this.product.category = item.category;


                this.foundedBy.name = [];
                this.foundedBy.code = [];

                this.foundedByCode = [];
                this.foundedByName = [];

                app.calculateOrder();
            },
            change(price = null, amount = null){
                if (amount > 100) {
                    confirm('{{__('billing/order.limit_alert')}}');
                    amount = 1;
                }

                if(price != null)
                    this.product.price = price;

                if(amount != null)
                    this.product.amount = amount;

                app.calculateOrder();
            },
        },

        /**
         *
         *
         1	Телефоны и смартфоны
         2	Гаджеты   и аксессуары
         3	Кондиционеры
         4	Компьютерная техника
         5	Телевизоры
         6	Аудиотехника и Hi-Fi
         7	Техника для офиса
         8	Техника для дома
         9	Техника для кухни
         10	Товары для авто
         11	Красота и Спорт
         12	Прочее
         */

        template:
            `<div class="form-row align-items-center">

                <div class="form-group col-md-3 amount">
                    <label>{{__('billing/order.lbl_product_amount')}}</label>
                    <input
                        v-on:keyup="change(null, $event.target.value)"
                        autocomplete="off"
                        v-model="productAmount"
                        :class="product.amount?\'is-valid\':\'is-invalid\'"
                        required
                        type="number"
                        name="product[][amount]"
                        class="form-control modified"
                    >
                </div>

                <div class="form-group col-md-3">
                    <label>{{__('billing/order.lbl_product_price')}}</label>
                    <input
                        :disabled="product.id != null"
                        v-on:keyup="change($event.target.value, null)"
                        autocomplete="off"
                        v-model="product.price"
                        :class="product.price?\'is-valid\':\'is-invalid\'"
                        required
                        type="number"
                        name="product[][price]"
                        class="form-control modified"
                    >
                </div>

                <div class="form-group col-md-3">
                    <label>{{__('billing/order.lbl_total')}}</label>
                    <input
                        disabled="disabled"
                        type="text"
                        class="form-control modified"
                        :value="product.price*product.amount"
                    >
                </div>

                <div class="col-12 col-sm-1">
                    <button
                        v-if="index > 0"
                        type="button"
                        @click="$emit(\'delete-product\')"
                        class="btn-cancel bg-white"
                    >
                        &times;&nbsp;&nbsp;<span>{{__('app.btn_delete')}}</span>&nbsp;
                    </button>
                </div>
            </div>`
    };

    var app = new Vue({
        el: '#app',
        components: {
            'product': Product
        },
        computed: {
            isLimitDone() {
                return product.amount < 10;
            },
            /*
            *   checking << mobile and smartphones >> category picked more than - 2
            * */
            mobileLimit() {
                const techCategory = this.products.filter(product => product.category == 1);

                return techCategory.length > 2 || this.products.length > 2;
            },
        },
        data: {
            status: null,

            loading: false,
            processing_user:false,
            calculating: false,

            errors: {},
            message: [],
            deposit_message: [],
            buyers: [],

            strSearchPhone: '+998',

            buyer: null,
            products: [],

            resend: {
                interval: 60,
                indicator: false,
                timer: null
            },

            category: null,
            imei: null,

            calculate: null,
            preview_offer: null,
            seller_phone: null,

            config_plans: {
                @foreach($plans as $plan => $percent)
                {{$plan}}: {{$percent}},
                @endforeach
            },


            total: 0,
            deposit: 0,
            totalCredit: 0,
            paymentMonthly: 0,
            period: 0,
            plan_graf: 1,

            partner_id: {!! $partner->id !!},
            company_id: {!! $partner->company->id !!},
            partner_settings: '{!! $partner->settings !!}',

            formValid: false,
            hashedSmsCode: null,
            smsCode: null,
            offer_preview: null,
            productEmpty: null,
        },

        methods: {

            addProductManually(){

                let item = {
                    id: null,
                    amount: 1,
                    // weight: 0,
                    price: 0,
                    vendor_code: null,
                    limitSmartphone: false,
                }
                this.products.push(item);

                /*
                *   if products have more than 2 << mobile and smartphones >> category
                *   we will display none this category and set default next category
                * */
                if (this.mobileLimit) {
                    item.limitSmartphone = true;
                    item.category = 2
                }
            },

            formatPrice(price = null){
                let separator = ' ';
                price = price.toString();
                return price.replace(/(\d{1,3}(?=(\d{3})+(?:\.\d|\b)))/g,"\$1"+separator);
            },

            addProductFromCatalog(product){
                //todo: добить после готовности каталог контроллера
                let item = {
                    id:     product.id,
                    category: product.category,
                    name:   product.name,
                    amount: 1,
                    weight: product.weight,
                    price:  product.price,
                    imei: product.imei,
                }
                this.products.push(item);

                this.calculateOrder();
            },

            deleteProduct(productIndex){
                if(this.products.length > 1) {
                    this.products.splice(this.products.findIndex((product, index) => index === productIndex), 1);
                    this.calculateOrder();
                }
            },

            checkProducts(){

                for( let i = 0; i < this.products.length; i++ ) {

                    if (
                        this.products[i].price == 0 ||
                        this.products[i].amount == 0
                    ){
                        return false;
                    }

                    if ( this.products[i].category == 1){

                        if( this.products[i].imei == null) return false;

                        if( this.products[i].imei.length !=15 )  return false;

                    }
                }

                return true;
            },

            calculateOrder() {
                this.message = [];
                this.deposit_message = [];
                console.log('calculate');

                if(this.period > 0 && this.products.length > 0 && this.checkProducts()) {
                    this.calculating = true;

                    let formattedProducts = {};
                    formattedProducts[this.company_id] = this.products;

                    axios.post('/api/v1/order/calculate', {
                        api_token: '{{$user->api_token}}',
                        type: 'credit',
                        period: this.period,
                        products:formattedProducts,
                        partner_id: this.partner_id,
                        user_id: 225257 /* боевом 225257 локалке 215291 */
                    }).then(response => {
                        if(response.data.status == 'success'){
                            let data            = response.data.data;
                            this.calculate       = response.data.data;
                            this.total          = data.price.origin;
                            this.totalCredit    = data.price.total;
                            this.paymentMonthly = data.price.month;
                        }

                        if (this.buyer  && this.total > this.buyer.settings.balance) {

                            // тут вычислим если не хватает на депозитный взнос
                            if (this.buyer.settings.balance === 0 || this.total > this.buyer.settings.personal_account + this.buyer.settings.balance){
                                this.totalCredit = 0;
                                this.message.push({
                                    type: 'danger',
                                    message: '{{__('billing/order.err_limit')}}'
                                });
                            } else {
                                // если хватает вычислим депозит
                                this.deposit = this.total - this.buyer.settings.balance;

                                this.deposit_message.push({
                                    type: 'success',
                                    deposit: this.deposit
                                });
                            }

                        }

                        if(this.buyer && this.period > this.buyer.settings.period) {
                            this.message.push({
                                type: 'danger',
                                text: '{{__('billing/order.err_period')}}'
                            });
                        }

                        this.calculating = false;
                    })
                } else {
                    this.total          = 0;
                    this.totalCredit    = 0;
                    this.paymentMonthly = 0;
                }
            },

        },

        watch: {
            strSearchPhone: function(){
                if(this.strSearchPhone != null && this.strSearchPhone.length >= 13){
                    this.processing_user = true;
                    axios.post('/api/v1/buyers/list', {
                            api_token: '{{$user->api_token}}',
                            phone__like: this.strSearchPhone,
                            //status: 4
                        },
                        {headers: {'Content-Language': '{{app()->getLocale()}}'}}
                    ).then(response => {
                        if (response.data.status === 'success') {
                            if(response.data.response.total === 0){
                                this.buyers = 404;
                            }else{
                                if(response.data.response.debs === 0){
                                    this.buyers = response.data.data;
                                }else{
                                    // если есть просрочка
                                    this.buyers = 403;
                                }
                            }
                        }
                        this.processing_user = false;
                    })
                }
            }
        },

        created: function() {
            @if($product)
                let item = {
                    id: '{{$product->id}}',
                    category: {{$product->category_id??null}},
                    name: '{{$product->locale->title}}',
                    amount: 1,
                    weight: '{{$product->weight}}',
                    price: '{{$product->price}}',
                    vendor_code: '{{$product->vendor_code??null}}',
                    imei : null,
                }
                this.products.push(item);
            @else
                this.addProductManually();
            @endif
        }
    })

</script>


