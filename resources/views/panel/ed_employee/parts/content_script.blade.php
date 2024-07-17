<script>

const credit_table_cols = [
        { name: 'Дата документа', key: 'doc_time', width: '120px', customRender:(txt, raw)=> moment(txt, 'YYYY-MM-DD HH:mm:ss').format('DD.MM.YYYY HH:mm') },
        { name: 'Счет', key: 'corr_account' },
        { name: 'МФО', key: 'corr_mfo', width: '60px' },
        { name: 'Наименование', key: 'corr_name' },
        { name: 'Номер документа', key: 'doc_id', width: '60px' },
        // { name: 'Тип документа', key: 'doc_type', width: '60px' },
        // { name: 'Филиал', key: 'filial', width: '60px' },
        { name: 'Оборот Дебет', key: 'amount', width: '140px', customRender:(txt, raw)=> ed_employee.formatCurrency(txt)},
        // { name: 'Оборот Кредит', key: 'turnover_credit', width: '140px', customRender:(txt, raw)=> ed_employee.formatCurrency(txt)},
        { name: 'Назначение', key: 'purpose_of_payment', width: '220px'},
        { name: 'Кассовый символ', key: 'cash_symbol', width: '100px'},
        { name: 'ИНН', key: 'corr_inn', width: '100px' },
    ];
const debit_table_cols = [
        { name: 'Дата документа', key: 'doc_time', width: '120px', customRender:(txt, raw)=> moment(txt, 'YYYY-MM-DD HH:mm:ss').format('DD.MM.YYYY HH:mm') },
        { name: 'Счет', key: 'corr_account' },
        { name: 'МФО', key: 'corr_mfo', width: '60px' },
        { name: 'Наименование', key: 'corr_name' },
        { name: 'Номер документа', key: 'doc_id', width: '60px' },
        // { name: 'Тип документа', key: 'doc_type', width: '60px' },
        // { name: 'Филиал', key: 'filial', width: '60px' },
        // { name: 'Оборот Дебет', key: 'amount', width: '140px', customRender:(txt, raw)=> ed_employee.formatCurrency(txt)},
        { name: 'Оборот Кредит', key: 'amount', width: '140px', customRender:(txt, raw)=> ed_employee.formatCurrency(txt)},
        { name: 'Назначение', key: 'purpose_of_payment', width: '220px'},
        { name: 'Кассовый символ', key: 'cash_symbol', width: '100px'},
        { name: 'ИНН', key: 'corr_inn', width: '100px' },
    ];

// const credit_table_cols = [
//         { name: 'Учетная запись кошелька', key: 'created_at', customRender:(txt, raw)=> raw?.wallets?.account || 'Нет' },
//         { name: 'Статус', key: 'status'},
//         { name: 'ID контракта', key: 'contract_id'},
//         { name: 'Сумма', key: 'amount', customRender:(txt, raw)=> ed_employee.formatCurrency(txt)},
//         { name: 'Дата', key: 'created_at', customRender:(txt, raw)=> moment(txt).format('DD.MM.YYYY')}
//     ];

const analytics_dict = {
    credit: 'Банк выписка',
    debit: 'Платежи',
    close_bank: 'Сумма',
    close: 'Остаток',
}

const ed_employee = new Vue({
    el: '#ed_employee',
    components: {
        'SelectPagination': SelectPagination,
    },
    data: {
        reportLoading: false,
        analytics_dict,
        analytics: {
            credit: 0,
            debit: 0,
            close: 0,
            close_bank: 0,
        },
        tab_list: [
            { name: 'Банк выписка', key: 'credit'},
            { name: 'Платежи', key: 'debit'}
        ],
        tab_model: 'credit',
        apiToken: globalApiToken,
        tableLoading: true,
        table_cols: {
            credit: credit_table_cols,
            debit: debit_table_cols
        },
        // updateTransactionsLoading: false,
        date_from: {
            value: moment().subtract(1, 'days').format('YYYY-MM-DD'),
            min: `2020-01-01`,
            max: moment().format('YYYY-MM-DD'),
        },
        date_to: {
            value: moment().format('YYYY-MM-DD'),
            min: moment().subtract(1, 'days').format('YYYY-MM-DD'),
            max: moment().format('YYYY-MM-DD'),
        },
        table_rows: [],
        pagination: {
            totalCount: 0,
            currentPage: 1,
            pageCount: 0,
            perPage: 10,
        },
    },
    watch: {
        'pagination.currentPage': function (newVal, oldVal) {
            if (newVal != oldVal) {
                // sessionStorage.setItem('currentPage', this.pagination.currentPage)
                this.fetchData();
            }
        },
        'date_from.value': function (new_date_from, oldVal) {
            if (new_date_from != oldVal) {
                this.date_to.min = new_date_from
                this.fetchData();
            }
        },
        'date_to.value': function (new_date_to, oldVal) {
            if (new_date_to != oldVal) {
                this.date_from.max = new_date_to
                this.fetchData();
            }
        },
    },
    computed: {
        calculated_table_cols() {
            return this.table_cols[this.tab_model]
        },
    },
    methods: {
        async showPicker(e){
            try {
                await e.target.showPicker();
            } catch (error) {
                console.error(error);
            }
        },
        formatCurrency(val){
            if (!val) return '0'
            val = Number(val)/100;
            return val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ")
        },
        toggleTab(key){
            if (key == this.tab_model) return
            this.table_rows = []
            this.tab_model = key
            sessionStorage.setItem('ed_employee_tab', key)
            this.pagination.currentPage = 1
            this.fetchData();
        },
        prevButton() {
            if (this.pagination.currentPage > 1) {
                this.pagination.currentPage -= 1;
            }
        },
        nextButton() {
            if (this.pagination.currentPage < this.pagination.pageCount) {
                this.pagination.currentPage += 1;
            }
        },
        async fetchUrl(payload) {
            return axios.get('/api/v3/admin/ed-transaction/filter', {
                params: payload,
                headers: {
                    Authorization: `Bearer ${this.apiToken}`,
                    'Content-Language': window.Laravel.locale
                },
            });
        },
        // async updateTransactions() {
        //     this.updateTransactionsLoading = true
        //     try {
        //         const { data } = await axios.post('/api/v3/admin/ed-transaction/updateTransactions',{},{
        //             headers: {
        //                 Authorization: `Bearer ${this.apiToken}`,
        //                 'Content-Language': window.Laravel.locale
        //             },
        //         });
        //         await this.fetchData()
        //         this.updateTransactionsLoading = false
        //     } catch(e) {
        //         console.error(e);
        //         this.updateTransactionsLoading = false
        //     }
        // },
        // async fetchAnalytics() {
        //     let payload = {
        //         type: 'all',
        //         date: `${this.date_from.value},${this.date_to.value}`,
        //     }
        //     try {
        //         const { data } = await this.fetchUrl(payload)
        //         this.analytics = data
        //     } catch (e) {
        //         console.error(e);
        //     }
        // },

        async fetchData() {
            this.tableLoading = true
            let payload = {
                type: this.tab_model,
                date_from: moment(this.date_from.value, 'YYYY-MM-DD').format('DD.MM.YYYY'),
                date_to: moment(this.date_to.value, 'YYYY-MM-DD').format('DD.MM.YYYY') ,
                page: this.pagination?.currentPage || 1,
                per_page: this.pagination?.perPage || 10,
            }
            console.log(payload, 'payloadpayloadpayload');
            try {
                const { data: {data} } = await this.fetchUrl(payload)
                this.table_rows = data?.data
                this.analytics = data?.statistics
                this.pagination.totalCount = data?.links?.total
                this.pagination.pageCount = data?.links?.last_page
                this.tableLoading = false
            } catch (e) {
                console.error(e);
                this.tableLoading = false
            }
        },
        async downloadReport() {
            this.reportLoading = true

            let date_from = moment(this.date_from.value, 'YYYY-MM-DD').format('DD.MM.YYYY')
            let date_to = moment(this.date_to.value, 'YYYY-MM-DD').format('DD.MM.YYYY')

            try {
                const response = await axios.get(`/api/v3/admin/ed-transaction/download/report?date_from=${date_from}&date_to=${date_to}`, {responseType: 'blob', headers: {
                    Authorization: `Bearer ${globalApiToken}`
                }})

                let blob = new Blob([response.data], {type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'}),
                url = window.URL.createObjectURL(blob)
                let link = document.createElement('a');
                link.href = url;
                link.download = `${date_from}-${date_to}.xlsx`;
                document.body.appendChild(link);
                link.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(link);
                this.reportLoading = false
            } catch (e) {
                console.error(e)
                this.reportLoading = false
            }         
        }
    },
    async created() {
        this.tableLoading = true
        // let current_page = sessionStorage.getItem('currentPage')
        // if (current_page) this.pagination.currentPage = current_page
        let tab_model = sessionStorage.getItem('ed_employee_tab')
        if (tab_model) this.tab_model = tab_model
        // await this.fetchAnalytics()
        await this.fetchData()
        this.tableLoading = false
    },
});

</script>
