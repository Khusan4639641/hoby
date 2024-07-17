<script>

$(document).ready(function () {
    function locationHashChanged() {
        switch (location.hash) {
            case '#complete':
                app.status = 'complete';
                break;
            case '#payment':
                app.status = 'payment';
                break;
            case '#active':
                app.status = 'active';
                break;
            case '#act_needed':
                app.status = 'act_needed';
                break;
            case '#expired':
                app.status = 'expired';
                break;
            case '#cancel':
                app.status = 'cancel';
                break;
        }

        $(location.hash).tab('show');
        app.updateList();
    }

    window.onhashchange = locationHashChanged;
});

const withNds = '{{ !empty($isPartnerNDS) && $isPartnerNDS }}';

var app = new Vue({
    el: '#app',
    data: {
        sourcePath: '{{ App\Helpers\FileHelper::sourcePath() }}',
        visiblePopup: false,
        checkedOrder: null,
        orders: null,
        loading: false,
        imeiSuccess: false,
        actSuccess: false,
        cancelActSuccess: false,
        params: {},
        status: 'complete',
        searchString: null,
        total: null,
        current: 0,
        perPage: 10,
        error: '',
        hash: '#complete',
        fileAct: null,
        itemContractId: null,
        act: {
            new: null,
            name: null,
            choose: null,
        },
        imei: {
            new: null,
            name: null,
            choose: null,
        },
        cancel_act: {
            new: null,
            name: null,
            choose: null,
        },
        searchInputValue: '',
        cancellationReason: '',
        withNds,
    },
    methods: {
        addMonths(date) {
            // var d = date.getDate();
            // date.setMonth(date.getMonth() + +months);
            // if (date.getDate() != d) {
            //     date.setDate(0);
            // }
            // return date;
        },
        reduceSum(products) {
            const sums = products.map(product => {
                return Math.round(product.price_discount / product.nds * 100) / 100 * product.amount;
            });
            if (sums.length > 0) {
                const reducer = (acc, value) => acc + value;
                return sums.reduce(reducer);
            }
        },
        reduceNDS(products) {
            const ndsSummary = products.map(product => {
                if (this.nds === 1) {
                    return 0;
                }
                return Math.round(product.price_discount * product.amount / this.nds * this.ndsPercent * 100) / 100;
            });
            if (ndsSummary.length > 0) {
                const reducer = (acc, value) => acc + value;
                return ndsSummary.reduce(reducer);
            }
        },
        detail(link = null) {
            if (link != null) window.location.href = link;
        },

        updateList(statusChanged = false) {
            if (!this.loading) {
                this.loading = true;
                this.buildParameters();

                if(this.searchInputValue !== ''){
                    // searching by phone number
                    const { partner_id, status } = this.params['params'][0]

                    this.params['params'][0]['user|phone__like'] = [this.searchInputValue];

                    // 'contract|status': 4
                    if(this.params['params'][1] && this.params['params'][1]['contract|status']) {
                        this.params['params'][1]['query_operation'] = 'or';
                        this.params['params'][1]['user|phone__like'] = [this.searchInputValue];
                        // searching by contract id
                        // 'contract|status': 3
                        this.params['params'][2] = {
                            'query_operation': 'or',
                            'contract|status': this.params['params'][0]['contract|status'],
                            'contract|id': [this.searchInputValue],
                            'partner_id': partner_id,
                        }

                        this.params['params'][3] = {
                            'query_operation': 'or',
                            'contract|status': this.params['params'][1]['contract|status'],
                            'contract|id': [this.searchInputValue],
                            'partner_id': partner_id,
                        }
                    }else{
                        this.params['params'][1] = {
                            'contract|status': this.params['params'][0]['contract|status'],
                            'query_operation': 'or',
                            status,
                            partner_id,
                            'contract|id': [this.searchInputValue],
                        }
                    }
                    this.searchInputValue = ''
                }

                if(statusChanged){
                    this.params.offset = 0;
                }

                axios.post('/api/v1/orders/list',
                    this.params,
                    { headers: { 'Content-Language': '{{app()->getLocale()}}' } },
                ).then(response => {
                    if (response.data.status === 'success') {
                        // const imeiCategories = [
                        //     1, // TODO: Выпилить через n-дней. Остаётся временно для старых контрактов
                        //     1331, // Смартфоны
                        //     1328, // Кнопочные
                        // ]
                        const orders = response.data.data;
                        this.orders = orders;
                        this.total = Math.ceil(response.data.response.total / this.perPage);

                        let isCategoryMobile;

                        const filteredOrders = orders.map((order) => {
                            if (withNds){
                                order.nds = moment(order.created_at, 'DD.MM.YYYY') >= moment('01.01.2023', 'DD.MM.YYYY') ? 0.12 : 0.15
                            }else {
                                order.nds = 0
                            }

                            order.total_price = 0;
                            order.nds_sum = 0

                            order.products.map(product => {
                                    product.price_without_nds = (product.price_discount / (order.nds + 1)).toFixed(2)
                                    product.total_price = ((product.price_discount / (1 + order.nds) * 100) / 100 * product.amount).toFixed(2)
                                    product.nds_percent = this.withNds ? order.nds * 100 : 0
                                    product.nds_sum = ((product.price_discount * product.amount) / (1 + order.nds) * order.nds).toFixed(2)

                                    order.total_price += Number(product.total_price)
                                    order.nds_sum += Number(product.nds_sum)
                            })

                            order.products.some(product => product.is_phone)
                                ? isCategoryMobile = true
                                : isCategoryMobile = false;

                            return {
                                ...order,
                                isCategoryMobile,
                            };
                        });

                        this.orders = filteredOrders;


                        if (this.orders.length > 0) {

                            for (i = 0; i < this.orders.length; i++) {
                                if (
                                    this.orders[i]
                                    && this.orders[i].detailLink
                                    && this.orders[i].id
                                    && this.orders[i].buyer
                                    && this.orders[i].buyer.link
                                    && this.orders[i].buyer.id
                                ) {
                                    this.orders[i].detailLink = `{{localeRoute('billing.orders.index')}}/${this.orders[i].id}`;
                                    this.orders[i].buyer.link = `{{localeRoute('billing.buyers.index')}}/${this.orders[i].buyer.id}`;
                                }
                            }

                        }
                        else {
                            this.orders = null;
                        }
                    }
                    this.loading = false;
                })
                    .catch(error => this.error = error.message);
            }
        },
        formatPrice(price = null) {
            let separator = ' ';
            price = price.toString();
            return price.replace(/(\d{1,3}(?=(\d{3})+(?:\.\d|\b)))/g, '\$1' + separator);
        },
        numberFormat(price) {
            return new Intl.NumberFormat().format(price);
        },
        phoneFormat(phone) {
            if (phone.length > 10) {
                phone = phone.replace(/[^\d]/g, '');
                return phone.replace(/(\d{3})(\d{2})(\d{3})(\d{2})(\d{2})/, '+$1 ($2) $3-$4-$5');
            } else {
                return phone;
            }
        },

        uploadAct(e, contractId) {
            this.loading = true;
            this.act.message = [];

            if (this.act.new !== null) {
                formData = new FormData();
                formData.append('api_token', '{{Auth::user()->api_token}}');
                formData.append('id', contractId);
                formData.append('act', this.act.new);

                axios.post('/api/v1/contracts/upload-act', formData, { headers: { 'Content-Language': '{{app()->getLocale()}}' } })
                    .then(response => {
                        if (response.data.status === 'success') {
                            this.act.status = 1;
                            this.actSuccess = true;
                        } else {
                            this.act.status = 0;
                            this.act.new = null;
                            this.act.message = response.data.response.message;
                        }

                        this.loading = false;
                        window.location.reload();
                    });
            } else {
                this.act.message.push({
                    'type': 'danger',
                    'text': '{{__('app.btn_choose_file')}}',
                });
            }

            this.loading = false;


        },
        updateFiles(event) {
            let files = event.target.files;

            if (files.length > 0) {
                this.act.new = files[0];
                this.act.name = files[0].name;
            }


            if (this.act.old) {
                this.files_to_delete.push(this.act.old);
            }
        },
        labelAct(e) {
            this.act.choose = e.target.parentElement.parentElement.parentElement.firstChild.dataset.contractid
                || e.target.parentElement.parentElement.firstChild.dataset.contractid;
        },

        uploadCancelAct(e, contractId) {
            this.loading = true;
            this.cancel_act.message = [];

            if (this.cancel_act.new !== null) {
                formData = new FormData();
                formData.append('api_token', '{{Auth::user()->api_token}}');
                formData.append('id', contractId);
                formData.append('cancel_act', this.cancel_act.new);

                axios.post('/api/v1/contracts/upload-cancel-act', formData, { headers: { 'Content-Language': '{{app()->getLocale()}}' } })
                    .then(response => {
                        if (response.data.status === 'success') {
                            this.cancel_act.status = 1;
                            this.cancelActSuccess = true;
                        } else {
                            this.cancel_act.status = 0;
                            this.cancel_act.new = null;
                            this.cancel_act.message = response.data.response.message;
                        }

                        this.loading = false;
                        window.location.reload();
                    });
            } else {
                this.cancel_act.message.push({
                    'type': 'danger',
                    'text': '{{__('app.btn_choose_file')}}',
                });
            }

            this.loading = false;


        },
        updateFilesCancelAct(event) {
            let files = event.target.files;

            if (files.length > 0) {
                this.cancel_act.new = files[0];
                this.cancel_act.name = files[0].name;
            }

            if (this.cancel_act.old) {
                this.files_to_delete.push(this.cancel_act.old);
            }
        },
        labelCancelAct(e) {
            this.cancel_act.choose = e.target.parentElement.parentElement.parentElement.firstChild.dataset.contractid
                || e.target.parentElement.parentElement.firstChild.dataset.contractid;
        },

        uploadImei(e, contractId) {
            this.loading = true;
            this.imei.message = [];

            if (this.imei.new !== null) {
                formData = new FormData();
                formData.append('api_token', '{{Auth::user()->api_token}}');
                formData.append('id', contractId);
                formData.append('imei', this.imei.new);

                axios.post('/api/v1/contracts/upload-imei', formData, { headers: { 'Content-Language': '{{app()->getLocale()}}' } })
                    .then(response => {
                        if (response.data.status === 'success') {
                            this.imei.status = 1;
                            this.imeiSuccess = true;
                        } else {
                            this.imei.status = 0;
                            this.imei.new = null;
                            this.imei.message = response.data.response.message;
                        }

                        this.loading = false;
                        window.location.reload();
                    });
            } else {
                this.imei.message.push({
                    'type': 'danger',
                    'text': '{{__('app.btn_choose_file')}}',
                });
            }

            this.loading = false;


        },
        updateImeiFiles(event) {
            let files = event.target.files;

            if (files.length > 0) {
                this.imei.new = files[0];
                this.imei.name = files[0].name;
            }

            if (this.imei.old) {
                this.files_to_delete.push(this.imei.old);
            }
        },
        labelImei(e) {
            this.imei.choose = e.target.parentElement.parentElement.parentElement.firstChild.dataset.contractid
                || e.target.parentElement.parentElement.firstChild.dataset.contractid;
        },

        buildParameters() {
            this.params = {};

            //Status
            switch (this.status) {

                case 'orders_for_cancellation':
                    this.params.params = [
                        {
                            'cancellation_status': 1,
                            partner_id: {{@$partnersId}}
                        }
                    ]
                break;

                case 'complete':
                    this.params.params = [
                        {
                            status: [0, 5, 9],
                            partner_id: {{@$partnersId}},
                        },
                    ];
                    break;

                case 'payment':
                    this.params.params = [
                        {
                            'contract|status': 0,
                            // credit__more: 0,
                            partner_id: {{@$partnersId}}
                        },
                        {{--{--}}
                        {{--    query_operation: 'or',--}}
                        {{--    status: [4, 6, 7, 8, 9],--}}
                        {{--    debit__more: 0,--}}
                        {{--    partner_id: {{@$partnersId}}--}}
                        {{--},--}}
                    ];
                    break;

                case 'active':
                    this.params.params = [
                        {
                            'contract|status': 1,
                            partner_id: {{@$partnersId}}
                        },
                    ];
                    break;

                case 'act_needed':
                    this.params.params = [
                        {
                            'contract|act_status': [0, 2],
                            partner_id: {{@$partnersId}}
                        },
                    ];
                    break;

                case 'expired':
                    this.params.params = [
                        {
                            'contract|status': 4,
                            partner_id: {{@$partnersId}}
                        },
                        {
                            query_operation: 'or',
                            'contract|status': 3,
                            partner_id: {{@$partnersId}}
                        },
                    ];
                    break;

                case 'cancel':
                    this.params.params = [
                        {
                            'contract|status': 5,
                            partner_id: {{@$partnersId}}
                        },
                    ];
                    break;

                default:
                    this.params.params = [
                        {
                            status: 1,
                            partner_id: {{@$partnersId}}
                        },
                    ];

                    break;
            }

            //Search
            if (this.searchString != null)
                this.params.id__like = this.searchString;

            //Offset && limit
            this.params.limit = this.perPage;
            this.params.offset = this.current * this.perPage;

            this.params.orderByDesc = 'created_at';

            this.params.api_token = '{{Auth::user()->api_token}}';
        },
        changeStatus(status = null) {
            this.status = status;
            this.hash = '#' + status;
            this.updateList(true);
        },
        paginate(page = null, increment = false ) {
            if (page != null && page >= 1 && page <= this.total) {
                this.current = page - 1;
                this.updateList();
            }
        },
        showPopup(status, id = null){
            this.visiblePopup = status;
            this.checkedOrder = id
        }
    },
    created: function () {
        const pathArray = window.location.pathname.split("/");
        const segment_3 = pathArray[3];
        if(segment_3 == 'contracts_for_cancellation'){
            this.changeStatus('orders_for_cancellation');
        }
        this.updateList();

    },
});

</script>
