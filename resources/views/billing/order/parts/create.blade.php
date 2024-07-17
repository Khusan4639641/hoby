<script src="{{ asset('assets/js/underscore-min.js') }}"></script>

<script>
    const minLength = '{{ __('billing/buyer.guarants_name_length')  }}';
    const smartphonesLimit = 2
    const categoriesWithImei = [1331, 1328, 11950]
    const categoriesWithRequiredImei = [1331, 1328, 11950]
    let product_template = /*html*/`
<div class="position-relative">
    <div class="form-row align-items-center mb-3">
        <div class="col ">
            <div class="row align-items-end">
                <RecursiveMultiselect
                    :isoverlayed="isoverlayed"
                    :constants="constants"
                    :disablesmartphonescat="disablesmartphonescat"
                    :categoryId="0"
                    :noProperCategory="product.noProperCategory"
                    :selectedCategories="product.categories"
                    @select="updateCategories"
                />
            </div>
        </div>
        <div class="col-auto col-sm-auto form-group">
            <label>&nbsp;</label>
            <button
                v-if="index > 0"
                type="button"
                @click="$emit('delete-product', index)"
                class="btn-cancel bg-white"
            >
                &times;&nbsp;&nbsp;<span>{{__('app.btn_delete')}}</span>&nbsp;
            </button>
        </div>
    </div>

    <div class="form-row align-items-center " :class="{'inactive': !product.isCategoriesCompleted}">

        <div class="form-group col-12 col-sm-6">
            <label>{{__('billing/order.lbl_product_name')}}</label>
            <input
                v-on:keyup="searchBy($event.target.value)"
                autocomplete="off"
                :disabled="!isEditable"
                @keypress="productNameDown($event)"
                v-model="product.name"
                :class="'form-control modified ' + (product.name?.length >= 5 || 'is-invalid')"
                required
                name="product[][name]"
                type="text"
            >
            <div v-if="foundedBy.name.length > 0" class="dropdown-menu show">
                <a
                    v-for="(item, index) in foundedBy.name"
                    :key="item.id"
                    class="dropdown-item"
                    @click="update(item)"
                >
                @{{item.locale.title}} (@{{ item.price }})
            </a>
            </div>
        </div>

        <div class="form-group col-6 col-sm-1 amount">
            <label>{{__('billing/order.lbl_product_amount')}}</label>
            <input
                v-on:keyup="change(null, $event.target.value)"
                v-mask="'###'"
                autocomplete="off"
                v-model="productAmount"
                :class="'form-control modified ' + (product.amount || 'is-invalid')"
                required
                :disabled="hasProductImei(product)"
                name="product[][amount]"
            >
        </div>

        <div class="form-group col-6 col-sm-1">
            <label>Ед.изм</label>
            <select disabled v-model="productUnitIdModel" required name="product[][unit_id]" :class="'form-control modified ' + (productUnitIdModel || 'is-invalid')">
                <option :value="unit.id" v-for="(unit, unitIndex) in units" :key="unitIndex">@{{unit.title}}</option>
            </select>
        </div>
        <div class="form-group col-6 col-sm-2">
            <label>{{__('billing/order.lbl_product_price')}}</label>
            <input
                :disabled="product.id != null"
                v-on:keyup="change($event.target.value, null)"
                v-mask="'###############'"
                autocomplete="off"
                v-model="productPrice"
                :class="'form-control modified ' + (product.price || 'is-invalid')"
                required
                type="number"
                name="product[][price]"
            >
        </div>

        <div class="form-group col-6 col-sm-2">
            <label>{{__('billing/order.lbl_total')}}</label>
            <input
                disabled="disabled"
                type="text"
                class="form-control modified"
                :value="product.price * product.amount"
            >
        </div>

        <div v-if="product.needIMEI" class="form-group col-6 col-sm-3">
            <label>{{__('billing/order.lbl_product_imei')}} <span v-if="imeiError" class="error" v-html="imeiError"></span></label>
            <input type="text" :class="{'is-invalid': product.IMEIrequired && (product.imei?.length !== 15)}" class="form-control modified " v-model="product.imei" v-mask="'###############'">
        </div>


    </div>
    <div v-if="clarify" class="align-items-end form-group" style="display: flex;">
        <button class="btn btn-orange mr-2" type="button" @click="setTrustworth" >@{{ isTrustworthy ? 'Скрыть' : "Уточнить данные"  }}</button>
        <div v-if="isTrustworthy" class="col-sm-2">
            <label>Наименование</label>
            <input
                autocomplete="off"
                v-model="product.original_name"
                class="form-control modified"
                :class="{'is-invalid': !(product.original_name?.length > 0)}"
                required
                name="product[][name]"
                type="text"
            >
        </div>
        <button v-if="isTrustworthy" class="btn btn-orange mr-2" type="button" @click="addImei = !addImei">@{{ addImei ? "Скрыть IMEI" : "Добавить IMEI" }}</button>
        <div v-if="isTrustworthy && addImei" class="col-6 col-sm-3">
            <label>{{__('billing/order.lbl_product_imei')}} <span v-if="imeiCheck" class="error" v-html="imeiCheck"></span></label>
            <input v-model="product.original_imei" v-mask="'###############'" type="text" class="form-control modified" :class="{'is-invalid': product.original_imei?.length !== 15}">
        </div>
    </div>
    <div v-show="product.previewName" class="form-group col-12 border p-3" style="border-radius: 8px">
        <strong>{{__('billing/order.product_full_name')}}</strong> @{{ product.previewName }}@{{ product.name }} </div>
    </div>
</div>
`
</script>

<script>
    const IsCapableToClarify = @json($IsCapableToClarify);

    let Product = {
        props: ["product", "index", "units", "disablesmartphonescat", "isoverlayed", "clarify"],
        components: {
            'RecursiveMultiselect': RecursiveMultiselect,
        },
        data: function () {
            return {
                foundedBy: {
                    name: [],
                    code: ""
                },
                constants: { categoriesWithImei },
                isCategoriesCompletedLocal: false,
                foundedByCode: [],
                foundedByName: [],
                clicked: false,
                seeked: false,
                productAmount: 1,
                productPrice: '',
                isTrustworthy: false,
                addImei: false,
                isEditable: false,
                generalCompanyId: '{{ $partner->company->general_company_id }}',
            }
        },
        watch: {
            productAmount(val) {
                this.productAmount = val.replace(/^0+/gi);
            },

            productPrice(val) {
                this.productPrice = val.replace(/^0+/gi);
                if (this.isMfoPartner){
                    this.period = null
                    productApp.calculateOrder()
                }
            },
        },
        computed: {
            isMfoPartner() {
                return this.generalCompanyId == 3
            },
            imeiError() {
                const imeiCount = this.product.imei ? this.product.imei.length : 0;
                let result = '';
                switch (true) {
                    case imeiCount > 15:
                        result = `<span class="text-danger">{{ __('billing/order.text_product_imei_more') }} (${imeiCount})</span>`;
                        break;
                    case imeiCount === 15:
                        result = `<span class="text-success">{{ __('billing/order.text_product_imei_done') }}</span>`;
                        break;
                    default :
                        result = `<span class="text-danger">{{ __('billing/order.text_product_imei_less') }} (${imeiCount})</span>`
                        break
                }
                return result
            },
            productUnitIdModel: {
                get () { return this.product.unit_id },
                set (value) { this.$emit('update:unit', value ) },
            },

            imeiCheck() {
                const imeiCount = this.product.original_imei ? this.product.original_imei.length : 0;
                let result = '';
                switch (true) {
                    case imeiCount === 15:
                        result = `<span class="text-success">{{ __('billing/order.text_product_imei_done') }}</span>`;
                        break;
                    default :
                        result = `<span class="text-danger">{{ __('billing/order.text_product_imei_less') }} (${imeiCount})</span>`
                        break
                }
                return result
            }
        },
        methods: {
            hasProductImei(product) {
              return product?.categories.some((category) => categoriesWithImei.includes(category))
            },
            updateCategories(categories, completed = false) {
                const neededCategoryTitleIds = [0, 1, 2]
                const product = structuredClone(this.product)
                this.isEditable = !categories[0].is_definite
                product.previewName = ''
                if (completed) {
                    product.name =  this.isEditable ? '' : categories[0].title
                    for (let i = categories.length - 1; i >= 0; i--) {
                        if (neededCategoryTitleIds.includes(i)){
                            product.previewName += i === 0 ? '' : `${categories[i].title}/`
                        }
                    }
                }

                product.categories = categories.map(category => category.id)
                product.isCategoriesCompleted = completed
                this.isCategoriesCompletedLocal = completed
                this.selectCategory()
                this.$emit('update', product)
            },
            categoriesUpdated(category){
                this.product.category = category.id
                this.product.categoryObject = category
                this.selectCategory()
            },
            setTrustworth() {
                this.isTrustworthy = !this.isTrustworthy
                this.product.original_imei = null
                this.product.original_name = null
            },
            productNameDown(e){
                this.product.name = e.target.value.replace(/^\s+|['"`]/, '')
            },
            searchBy(val = "", type = "name") {
                this.foundedBy.name = this.foundedBy.type = [];

                if (val != "") {
                    this.processing = true;

                    let data = {
                        api_token: '{{$user->api_token}}',
                        user_id: {{Auth::user()->id}}
                    }

                    switch (type) {
                        case "code":
                            data.vendor_code__like = val;
                            break;
                        default:
                            data.title__like = val;
                            break;
                    }

                    if (data.title__like != null && data.title__like.length >= 4 && !this.seeked) {
                        axios.post('/api/v1/catalog/products/list', data).then(response => {
                            if (response.data.status === 'success') {
                                this.seeked = true;
                                this.foundedBy[type] = response.data.data;
                            }
                            this.processing = false;
                        })
                    }
                }
            },
            update(item) {
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

                productApp.calculateOrder();
            },
            calculateOrderDebounce: _.debounce(function (){productApp.calculateOrder()},700),
            change(price = null, amount = null) {
                if (amount > 100) {
                    confirm('{{__('billing/order.limit_alert')}}');
                    amount = '1';
                    this.productAmount = amount;
                }

                if (price != null)
                    this.product.price = price;

                if (amount != null)
                    this.product.amount = amount;
                this.calculateOrderDebounce()
            },
            selectCategory() {
                if (this.hasProductImei(this.product)) {
                    this.productAmount = 1
                }
                productApp.calculateOrder()
            },
        },
        created() {
            this.product.unit_id = this.units[0].id
        },
        template:product_template,

    };
</script>

<script>

    var productApp = new Vue({
        el: '#app',
        components: {
            'product': Product,
        },
        data: {
            clarify: IsCapableToClarify,
            categories: [],
            units: [],
            bonusAmount: 0,
            status: null,
            // thing: null,
            phonesCount: 0,
            loading: false,
            processing_user: false,
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
            seller_id: null,
            config_plans: {
                @foreach($plans as $plan => $percent)
                    {{$plan}}: {{$percent}},
                @endforeach
            },
            total: 0,
            deposit: 0,
            avalaibleLimit: 0,
            totalCredit: 0,
            paymentMonthly: 0,
            period: null,
            plan_graf: 1,
            partner_id: {!! $partner->id !!},
            company_id: {!! $partner->company->id !!},
            company_status: {!! $partner->company->status !!},
            partner_settings: '{!! $partner->settings !!}',
            limit_for_24: '{!! $partner->settings->limit_for_24 !!}',
            reverse_calc: '{!! $partner->company->reverse_calc !!}',
            company_promotion: '{!! $partner->company->promotion !!}',
            promotion_percent: '{!! $partner->settings->promotion_percent !!}',
            promotion_amount: 0,
            formValid: false,
            hashedSmsCode: null,
            smsCode: null,
            offer_preview: null,
            productEmpty: null,
            limitError: '',
            mfoPeriods: [],
            generalCompanyId: '{{ $partner->company->general_company_id }}',
        },
        computed: {
            isMfoPartner() {
                return this.generalCompanyId == 3
            },
            disableMobileCategory() {
                let smartphonesCount = 0
                if (this.products.length > 0) {
                  smartphonesCount = this.products.filter((item, index) => this.isItSmartphone(item)).length
                }
                return (smartphonesCount + this.phonesCount) >= smartphonesLimit
            },
            isLimitDone() {
                return product.amount < 10;
            },
            isSubmitAllowed(){
                return this.products.length > 0 && this.checkProducts() && this.period != null
            },
        },
        methods: {
            hasProductImei(product) {
              return product?.categories.some((category) => categoriesWithImei.includes(category))
            },
            updateProduct(index, product) {
                const products = structuredClone(this.products)
                if (this.hasProductImei(product)) product.amount = 1
                product.category = product.categories[0]
                product.needIMEI = product.categories.some((category) => categoriesWithImei.includes(category))
                product.IMEIrequired = product.categories.some((category) => categoriesWithRequiredImei.includes(category))

                if(!product.needIMEI) {
                  delete product.imei
                }
                products[index] = product
                this.products = products
            },
            isItSmartphone(product) { return this.hasProductImei(product) },
            updateProductUnit(id, index){
                this.products[index].unit_id = id
            },
            fetchUnits(){
                axios.get(`/api/v3/units/list?api_token=${globalApiToken}`, { headers: {'Content-Language':  window.Laravel.locale} }).then(response => {
                    if (response?.data?.data) {
                       this.units = response.data.data
                    }
                }).catch(err=> {
                    console.error(err);
                })
            },
            addProductManually(count) {
                let item = {
                    id: null,
                    category: 1,
                    name: null,
                    amount: 1,
                    categories: [],
                    isCategoriesCompleted: false,
                    unit_id: null,
                    mainCategory: null,
                    price: 0,
                    vendor_code: null,
                    imei: null,
                    limitSmartphone: false,
                    needIMEI: false,
                    IMEIrequired: false,
                    noProperCategory: false,
                }

                if (this.clarify) {
                    item = { ...item, original_imei: null, original_name: null }
                }

                this.products.push(item);
            },
            formatPrice(price = null) {
                let separator = ' ';
                price = price?.toString();
                return price?.replace(/(\d{1,3}(?=(\d{3})+(?:\.\d|\b)))/g, "\$1" + separator);
            },
            deleteProduct(productIndex) {
                if (this.products.length > 1) {
                    this.products.splice(productIndex, 1)
                    this.calculateOrder();
                }
            },
            checkProducts() {
                for (let i = 0; i < this.products.length; i++) {
                    if (
                        this.products[i].category == null ||
                        this.products[i].name == '' ||
                        this.products[i].name == null ||
                        this.products[i].isCategoriesCompleted == false ||
                        this.products[i].price == 0 ||
                        this.products[i].amount == 0
                    ) {
                        return false;
                    }
                    if (this.products[i].IMEIrequired) {
                        if (this.products[i].imei == null) return false;
                        if (this.products[i].imei.length != 15) return false;
                    }
                }
                return true;
            },
            setBuyer(index) {
                this.buyer = this.buyers[index];
                this.buyer.settings.original_balance = this.buyer.settings.balance
                this.strSearchPhone = null;
                this.buyers = [];
                this.errors = {};

                if (this.buyer && this.buyer.settings == null) {
                    this.errors.buyer = '{{ __('billing/order.buyer_error') }}'
                }

                if (this.reverse_calc == 1) {
                    this.buyer.settings.balance = Math.round(this.buyer.settings.balance * 1.42);
                }

                this.loading = true;

                axios.post('/api/v1/buyer/phones-count', {
                    api_token: '{{$user->api_token}}',
                    buyer_id: this.buyer.id
                }).then(({data}) => {
                    this.loading = false;
                    if (data.status === 'success') {
                        this.phonesCount = data.phones_count
                        if (this.products.length === 0) {
                            this.addProductManually(data.phones_count);
                        }
                    } else {
                        this.phonesCount = data.phones_count
                        if (this.products.length === 0) {
                            this.addProductManually(data.phones_count);
                        }
                    }
                })
                this.loading = false;
            },
            unsetBuyer() {
                this.buyer = null;
            },
            calculateOrder() {
                this.message = [];
                this.deposit_message = [];
                this.err = false;
                if (this.products.length > 0 && this.checkProducts()) {
                    this.calculating = true;

                    let formattedProducts = {};
                    formattedProducts[this.company_id] = this.products;

                    if (this.seller_id > 0) {
                        axios.post('/api/v1/order/calculate-bonus', {
                            api_token: '{{$user->api_token}}',
                            type: 'credit',
                            period: this.period,
                            products: formattedProducts,
                            partner_id: this.partner_id,
                            user_id: this.buyer.id,
                            seller_id: this.seller_id
                        }).then(response => {
                            this.bonusAmount = response.data.response.data.bonus_amount;
                        });
                    }
                    let calculateUrl = '/api/v1/order/calculate'
                    let calculateBody = {
                        api_token: '{{$user->api_token}}',
                        type: 'credit',
                        period: this.period,
                        products: formattedProducts,
                        partner_id: this.partner_id,
                        user_id: this.buyer.id
                    }
                    if (this.isMfoPartner) {
                        axios.defaults.headers.common['Authorization'] = `Bearer {{$user->api_token}}`
                        calculateUrl = '/api/v3/mfo/calculate'
                        calculateBody = {
                            partner_id: this.partner_id,
                            user_id: this.buyer.id,
                            products: formattedProducts[this.company_id].map((product) => {
                                return Object.assign({}, { amount: product.amount, price: product.price })
                            })
                        }
                    }
                    axios.post(calculateUrl, calculateBody).then(response => {
                        this.isLoading = false
                        if (response.data.status == 'success') {
                            if (this.isMfoPartner){
                                this.mfoPeriods = response.data.data
                                this.totalCredit = this.period?.total || 0
                                this.paymentMonthly = this.period?.month || 0
                            }else {
                                let data = response.data.data;
                                this.calculate = response.data.data;
                                this.total = data.price?.origin;
                                this.totalCredit = data.price?.total;
                                this.paymentMonthly = data.price?.month;
                            }

                        }

                        // доступный лимит
                        this.avalaibleLimit = Number(this.buyer.settings.personal_account) + Number(this.buyer.settings.balance);

                        // вычислим депозитный платеж
                        if (this.buyer && this.total > Number(this.buyer.settings.original_balance)) {
                            this.deposit = this.total - Number(this.buyer.settings.original_balance);

                            // если денег на ЛС недостаточно, вернем ошибку
                            if(this.buyer.settings.personal_account < this.deposit){
                                this.err = true;
                            }else{
                                this.err = false;
                            }

                        }else{
                            this.deposit = 0;
                        }

                        // если это трехмесячная акция по предоплате, вычислим сумму предоплаты
                        if(this.company_promotion == 1 && this.period == 3){
                            this.promotion_amount = (this.total - this.deposit) * this.promotion_percent/100;
                        }else{
                            this.promotion_amount = 0;
                        }

                        // проверка на превышение доступного лимита
                        if(Number(this.buyer.settings.balance) === 0 || this.total > (this.avalaibleLimit - this.promotion_amount)){
                            this.err = true;
                        }else{
                            this.err = false;
                        }

                        // если есть покажем ошибку
                        if (this.err) {
                            this.totalCredit = 0;
                            this.message.push({
                                type: 'danger',
                                text: '{{__('billing/order.err_limit')}}'
                            });
                        } else {
                            // если есть покажем первоначальный взнос
                            if(this.deposit > 0){
                                this.deposit_message.push({
                                    type: 'success',
                                    deposit: this.deposit + this.promotion_amount
                                });
                            }
                        }

                        if (this.buyer && this.period > this.buyer.settings.period) {
                            this.message.push({
                                type: 'danger',
                                text: '{{__('billing/order.err_period')}}'
                            });
                        } else if (this.period == 24 && this.total < this.limit_for_24) {
                            this.message.push({
                                type: 'danger',
                                text: '{{__('billing/order.err_period_is_not_enough')}}'
                            });
                        }

                        this.calculating = false;
                    })
                } else {
                    this.total = 0;
                    this.totalCredit = 0;
                    this.paymentMonthly = 0;
                }
            },
            createOrder(e) {
                this.errors = {};

                this.loading = true;

                let formattedProducts = {};
                formattedProducts[this.company_id] = this.products;
                this.products.map(product => product.name = product.previewName + product.name)
                if (!this.products.some(product => /^\s+$/.test(product.name))) {

                    let createOrderURL = '/api/v1/orders/add'
                    let createOrderBody = {
                        api_token: '{{$user->api_token}}',
                        user_id: this.buyer.id,
                        type: 'credit',
                        seller_id: this.seller_id,
                        period: this.period,
                        plan_graf: this.plan_graf,
                        partner_id: this.partner_id,
                        products: formattedProducts,
                        sms_code: this.smsCode,
                        offer_preview: this.offer_preview
                    }
                    if (this.isMfoPartner){
                        axios.defaults.headers.common['Authorization'] = `Bearer {{$user->api_token}}`
                        createOrderURL = '/api/v3/mfo/order'
                        createOrderBody = {
                            user_id: this.buyer.id,
                            period: this.period.tariff,
                            products: this.products
                        }
                    }

                    axios.post(createOrderURL , createOrderBody,   {headers: {'Content-Language': '{{app()->getLocale()}}'}}
                    ).then(response => {
                        this.loading = false;

                        if (response.data.status == 'success') {
                            this.status = 'success';
                            this.contract_id = response.data.data.contract_id;
                            // send sms
                            axios.post('/api/v1/buyers/send-code-sms', {
                                phone: this.buyer.phone,
                                api_token: Cookies.get('api_token'),
                                contract_id: this.contract_id,
                                flag: true
                            })
                                .then(response => {
                                    this.loading = false;
                                    this.hashedSmsCode = response.data

                                })

                            window.location.href = `{{localeRoute('billing.orders.index')}}`; // /*, response.data.data.order_id*/); // тут создается пдф - ????
                        } else {
                            if (response.data.errors) {
                                this.message = [
                                    {
                                        text: response.data.errors[0],
                                        type: 'danger'
                                    }
                                ]
                            }
                            this.message = response.data.response.message;
                        }
                    })
                } else {
                    this.loading = false;
                    this.productEmpty = '{{ __('billing/order.text_product_name_empty') }}'
                }

                e.preventDefault();
            },
            calculateOrderDebounce: _.debounce(function (){this.calculateOrder()},700),
        },
        watch: {
            strSearchPhone: _.debounce(function () {
                if (this.company_status == 1) {
                    if (this.strSearchPhone != null && this.strSearchPhone.length >= 19) {
                        this.processing_user = true;
                        axios.post('/api/v1/buyers/list', {
                                api_token: '{{$user->api_token}}',
                                phone__like: this.strSearchPhone,
                                //status: 4
                            },
                            {headers: {'Content-Language': '{{app()->getLocale()}}'}}
                        ).then(response => {
                            if (response.data.status === 'success') {
                                if (response.data.response.total === 0) {
                                    this.buyers = 404;
                                } else {
                                    if(response.data.response.vip_allowed === 0){  // проверка на вип клиента - за которого платит вендор сам (покупать может только у него)
                                        this.buyers = 14; // не повезло, не разрешено оформлять у этого вендора - 14 нужно поставить
                                    }else{

                                        if (response.data.response.black_list === 1) { // проверка на блэк лист
                                            this.buyers = 13; // ой! блэк лист! не повезло
                                        } else {
                                            // если не должен, может купить
                                            if (response.data.response.debs === 0) {
                                                this.buyers = response.data.data;
                                            } else {
                                                //если есть просрочка
                                                this.buyers = 403; // не повезло
                                            }
                                        }

                                    }

                                }
                            }
                            this.processing_user = false;
                        })
                    }
                }

            },500),
        },
        created(){
            this.fetchUnits()
        },
    })

    $('document').ready(function () {
        const $modal = $('#modalCreateOrder');
        const $submitButton = $('#submitOrder');
        function modalOpen() {
            if (productApp.products.length > 0 && productApp.checkProducts()) {
                if (productApp.period != null) {
                    $modal.modal('show');
                } else {
                    productApp.message = [];
                    productApp.message.push({
                        type: 'danger',
                        text: '{{ __('billing/order.err_select_period') }}'
                    })
                }
            } else {
                productApp.message = [];
                productApp.message.push({
                    type: 'danger',
                    text: '{{ __('billing/order.err_product_data') }}'
                })
            }
        }

        $submitButton.on('click', modalOpen);
    })
</script>
