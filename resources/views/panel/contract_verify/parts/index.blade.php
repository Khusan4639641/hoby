<script>
    const noCatsTxt = 'Нет категории'
    const noUnitsTxt = 'Нет ед.изм'
    const navLinksLabels = {
        all: @json(__("table.all")),
        resus: @json(__('table.resus')),
        except_resus: @json(__('table.except_resus')),
        confirmed: @json(__('table.confirmed')),
        inConfirmation: @json(__('table.in_confirmation')),
        confirmedWithoutCheque: @json(__('table.confirmed_without_cheque')),
        mfo_with_resus: @json(__('table.mfo_with_resus')),
        mfo_without_resus: @json(__('table.mfo_without_resus'))
    }

    const companyIds = {
        resus: [216449, 216817]
    }

    var contractsVerifyApp = new Vue({
        el: '#contract_verify',
        components: {
            "DataTable": DataTable,
            "SelectPagination": SelectPagination
        },
        data(){
            return {
                loading: false,
                categories: [],
                allCategories: [],
                units: [],
                pagination: {
                    totalCount: 0,
                    currentPage: 0,
                    pageCount: 0,
                },
                asyncSearchTimer: null,
                editing_contract: null,
                editing_contract_index: null,
                rows: [],
                columns: {
                    default: [
                        { key: 'created_at', label: 'Дата', width:"250px"},
                        { key: 'id', label: 'ID Договора', width:"250px" },
                        { key: 'company', label: 'Продавец'},
                        { key: 'general_company', label: 'Главная компания'},
                        { key: 'actions', label: '', width:"250px"},
                    ],
                    withoutCheque: [
                        { key: 'created_at', label: 'Дата', width:"250px"},
                        { key: 'id', label: 'ID Договора', width:"250px" },
                        { key: 'company_name', label: 'Продавец ', width:"250px" },
                        { key: 'general_company_name', label: 'Главная компания', width:"250px" },
                        { key: 'uz_tax_error_caption', label: 'Ошибка', width: "250px"},
                    ]
                },
                psicCodeList: [],
                idInputVal: null,
                nameInputVal: null,
                selectedRow: null,
                editingProductId: null,
                navLinks: {
                    all: { id: 1, label: navLinksLabels.all, status: null, ordersCount: null, filter: null },
                    confirmed: {  id:2, label: navLinksLabels.confirmed, status: '1', ordersCount: null, filter: null },
                    inConfirmation:{ id:3, label: navLinksLabels.inConfirmation, status: '0', ordersCount: null, filter: null },
                    confirmedWithoutCheque:{ id:4,  label: navLinksLabels.confirmedWithoutCheque, status: null, ordersCount: null, filter: 3 }
                },
                companies: {
                    resus: { label: navLinksLabels.resus, id: companyIds.resus, except: false, mfo: 0},
                    except_resus: { label: navLinksLabels.except_resus, id: companyIds.resus, except: true, mfo: 0},
                    mfo_with_resus: { label: navLinksLabels.mfo_with_resus, id: companyIds.resus, except: false, mfo: 1},
                    mfo_without_resus: { label: navLinksLabels.mfo_without_resus, id: companyIds.resus, except: true, mfo: 1},
                },
                uzTaxErrorCodes: [
                    { label: @json(__('uz_tax.error_status_1')), id: 1 },
                    { label: @json(__('uz_tax.error_status_2')), id: 2 },
                    { label: @json(__('uz_tax.error_status_3')), id: 3 },
                    { label: @json(__('uz_tax.error_status_4')), id: 4 },
                    { label: @json(__('uz_tax.error_status_5')), id: 5 },
                    { label: @json(__('uz_tax.error_status_6')), id: 6 },
                    { label: @json(__('uz_tax.error_status_10')), id: 10 },
                    { label: @json(__('uz_tax.error_status_11')), id: 11 },
                    { label: @json(__('uz_tax.error_status_500')), id: 500 },
                    { label: @json(__('uz_tax.error_status_600')), id: 600 },
                ],
                filterByCompany: null,
                filterByUzTaxErrorCode: null,
                clickedNavLink: null,
                company_id: null,
            }
        },
        created(){
            this.clickedNavLink = this.navLinks.all
            this.pagination.currentPage = sessionStorage.getItem('currentPage')
            this.fetchUnits()
            this.fetchCategories()
            this.fetchAllCategories()
        },
        watch: {
            'pagination.currentPage': function (newVal, oldVal) {
                if (newVal != oldVal) {
                    sessionStorage.setItem('currentPage', this.pagination.currentPage)
                    this.fetchData()
                }
            },
            filterByCompany: function (newVal, oldVal) {
                if (newVal != oldVal) {
                    this.pagination.currentPage = 1
                    this.fetchData()
                }
            },
            filterByUzTaxErrorCode: function (newVal, oldVal) {
                if (newVal != oldVal) {
                    this.pagination.currentPage = 1
                    this.fetchData()
                }
            }
        },
        computed: {
            rowValidation(){
                let validation = {};
                let rowIndex = this.editing_contract_index
                if (rowIndex == null && rowIndex == undefined) return {};

                this.rows[rowIndex].order.products.forEach((prod, prodIndex) => {
                    if (prod.category_id) {
                        let isCatExists = this.allCategories.find(cat=> cat.id === prod.category_id)
                        if (!isCatExists) validation[`${prodIndex}.category`] = 'Выберите категорию'
                    } else validation[`${prodIndex}.category`] = 'Выберите категорию'

                    if (prod.unit_id) {
                        let isUnitExists = this.units.find(unit=> unit.id === prod.unit_id)
                        if (!isUnitExists) validation[`${prodIndex}.unit`] = 'Выберите ед.изм'
                    } else validation[`${prodIndex}.unit`] = 'Выберите ед.изм'

                    if (prod.name) {
                        if (prod.name.replace(/\s/g, "").length < 5) validation[`${prodIndex}.name`] = 'Минимальное количество символов 5'
                    } else validation[`${prodIndex}.name`] = 'Заполните поле'

                    if (prod.psic_code) {
                        if (prod.psic_code.replace(/\s/g, "").length < 10) validation[`${prodIndex}.psic_code`] = 'Минимальное количество символов 10'
                    } else validation[`${prodIndex}.psic_code`] = 'Заполните поле'
                })
                return validation;
            },
            tableColumns() {
                if (this.clickedNavLink?.id === this.navLinks.confirmedWithoutCheque.id) {
                    return this.columns.withoutCheque
                }
                return this.columns.default
            }
        },
        methods: {
            categoriesSelected(node, instanceId, productIndex, rowindex){
                this.setProdNameByCategoryIdRec(node.id, productIndex, rowindex, true)
            },
            compareCompanyAccounting(rowIndex, companyId) {
                if (companyIds.resus.includes(companyId) && !companyIds.resus.includes(this.filterByCompany?.id)) {
                    if (window.confirm('Вы точно хотите редактировать это договор ?')) {
                        this.editProducts(rowIndex)
                    }
                    return
                }
                this.editProducts(rowIndex)
            },
            treeSelectNormalizer(node){
                return { id: node.id, label: node.title, children: node.children }
            },
            loadTreeSelectOptions({ action, parentNode, searchQuery, callback }) {
                switch(action) {
                    case VueTreeselect.LOAD_CHILDREN_OPTIONS : {
                        if (parentNode.status == 0) {
                            callback()
                            break;
                        }
                        this.fetchCategoriesByParentId(parentNode.id).then(({data}) => {
                            if (data?.data) {
                                let child_data = data.data
                                child_data.forEach((cat, prodIndex) => {
                                    let hasChild = this.allCategories.find(el=> el.parent_id === cat.id )
                                    if (hasChild) cat.children = null
                                    if (cat.status == 0) cat.isDisabled = true
                                })
                                parentNode.children = child_data
                                callback()
                            }
                        }).catch(err=> {
                            console.error(err);
                            parentNode.children = []
                            callback()
                        })
                    }
                    break;
                    case VueTreeselect.ASYNC_SEARCH: {
                        clearTimeout(this.asyncSearchTimer)
                        this.asyncSearchTimer = setTimeout(async () => {
                            const data = await this.asyncTreeSearch(searchQuery)
                            data.forEach(el=> {
                                if (el.status == 0) el.isDisabled = true
                                else el.isDisabled = false
                            })
                            callback(null, data)
                        }, 1500)
                    }
                    break;
                }
            },
            async asyncTreeSearch(searchQuery) {
                if (searchQuery.replace(/\s/g, '').length < 2) return [{id: null, title: 'Введите минимум 2 символа для поиска',$isDisabled: true}]

                try {
                    const { data } = await axios.get(`/api/v3/categories/get-categories-hierarchy?`, {
                        params: {
                            search_value: searchQuery,
                            limit: 50,
                            offset: 0
                        }
                    } , {
                        headers: {'Content-Language':  window.Laravel.locale}
                    })
                    return data.data
                }catch(err){
                    console.log(err)
                }finally {
                }

            },
            // Pagination
            prevButton () {
                if (this.pagination.currentPage > 1) {
                    this.pagination.currentPage -= 1
                }
            },
            nextButton () {
                if (this.pagination.currentPage < this.pagination.pageCount) {
                    this.pagination.currentPage += 1
                }
            },
            getCategoryById(categoryId){
                if (!this.categories) return {title: `<span class="text-muted">${noCatsTxt}</span>`}
                let foundCat = this.allCategories.find(cat=> cat.id === categoryId)
                if(foundCat) return foundCat
                else return {title: `<span class="text-muted">${noCatsTxt}</span>`}
            },
            getUnitById(unitId){
                if (!this.units) return {title: `<span class="text-muted">${noUnitsTxt}</span>`}
                let foundUnit = this.units.find(unit=> unit.id === unitId)
                if(foundUnit) return foundUnit
                else return {title: `<span class="text-muted">${noUnitsTxt}</span>`}
            },
            getCategoryModelById(categoryId){
                if (!this.categories) return null;
                let foundCat = this.allCategories.find(cat=> cat.id === categoryId)
                if(foundCat) return foundCat
                else return null;
            },
            getUnitModelById(unitId){
                if (!this.units) return null;
                let foundUnit = this.units.find(unit=> unit.id === unitId)
                if(foundUnit) return foundUnit
                else return null;
            },
            cancelEditingAndSetBack(){
                if(this.editing_contract === null) return
                let rowindex = this.editing_contract_index
                Vue.set(this.rows, rowindex, this.editing_contract.data)
                this.rows[rowindex].isEditing = false
                this.editing_contract = null
                this.editing_contract_index = null
            },
            editProducts(rowindex){
                if (this.editing_contract) { //Проверяем изменены ли данные продукта
                    let editingContractOldData = this.editing_contract.data
                    let currentEditingContractNewData = this.rows[this.editing_contract_index]

                    if (JSON.stringify(editingContractOldData) != JSON.stringify(currentEditingContractNewData)){
                        let confirmation = confirm('Вы уверены? Измененные данные будут утеряны!')
                        if (!confirmation) return
                        else {
                            this.cancelEditingAndSetBack()
                        }
                    }
                    else this.rows[this.editing_contract_index].isEditing = false
                }


                this.rows[rowindex].isEditing = true

                const data = structuredClone(this.rows[rowindex])
                // data.order.products.forEach((product, i) => {
                //     data.order.products[i].psic_code = this.getCategoryById(product.category_id).psic_code
                // })

                this.$set(this.rows, rowindex, data)
                this.editing_contract = structuredClone({data, rowindex})

                this.editing_contract_index = rowindex
            },
            verifyContractProductChanges(rowindex){
                if (Object.keys(this.rowValidation).length > 0) {
                    alert('Заполните поля правильно!')
                    return
                }
                this.loading = true
                let currentTableRow = this.rows[rowindex]
                let products = JSON.parse(JSON.stringify(currentTableRow.order.products))

                let data = {
                    api_token: globalApiToken,
                    contract_id: currentTableRow.id,
                    order_products: products
                }
                axios.post(`/api/v3/contract-verify/verify`, data).then(response => {
                    this.loading = false
                    if (response?.data?.data) {
                        this.fetchData()
                    }
                }).catch(err=> {
                    console.error(err);
                    this.loading = false
                })

            },
            fetchData(){
                // filter, searchById, byName, common
                this.loading = true
                let params = {
                    api_token: globalApiToken,
                    status: this.clickedNavLink.id === 3 ? [1, 4] : 1, // 1 activated orders
                    page: this.pagination?.currentPage || undefined,
                    per_page: this.pagination?.perPage || undefined,
                    id: this.idInputVal || undefined,
                    'company|name__like' : this.nameInputVal || undefined,
                    verified: this.clickedNavLink?.status || undefined,
                    filter: this.clickedNavLink?.filter || undefined,
                    uz_tax_error_code: this.filterByUzTaxErrorCode || undefined
                }
                params =  (this.filterByCompany?.except ? {...params, company_id__not: this.filterByCompany?.id, mfo: this.filterByCompany?.mfo } : {...params, company_id: this.filterByCompany?.id, mfo: this.filterByCompany?.mfo })

                axios.get('/api/v3/contract-verify/list', { params })
                    .then(response => {
                        this.loading = false
                        if (response?.data?.data) {
                            let response_data = response.data
                            this.rows = response_data.data.map(el=> {return {psicSearchLoading: false, isEditing: false, ...el}})
                            this.editing_contract = null
                            this.editing_contract_index = null
                            this.pagination.totalCount = response_data.total
                            this.pagination.currentPage = response_data.current_page
                            this.pagination.pageCount = response_data.last_page
                            this.pagination.perPage = response_data.per_page
                            this.navLinks.all.ordersCount = response.data.all
                            this.navLinks.confirmed.ordersCount = response.data.verified
                            this.navLinks.inConfirmation.ordersCount = response.data.not_verified
                            this.navLinks.confirmedWithoutCheque.ordersCount = response.data.verified_without_cheque
                        }
                    }).catch(err=> {
                        console.error(err);
                        this.loading = false
                    })

            },
            fetchCategories(){
                this.fetchCategoriesByParentId().then(({data}) => {
                    if (data?.data) {
                        let resp_data = data.data
                        resp_data.forEach((cat, index) => {
                            cat.children = null
                        })
                        this.categories = resp_data
                    }
                }).catch(err=> {
                    console.error(err);
                })
            },
            setProdNameByCategoryIdRec(categoryId, productIndex, rowindex, isStartFn = true) {
                const currentCat = this.allCategories.find(({id}) => categoryId === id)
                if(isStartFn) { //Если это первый вызов функции (т.е не рекурсия)
                    this.rows[rowindex].order.products[productIndex].psic_code = ""
                    if (currentCat.psic_code) this.rows[rowindex].order.products[productIndex].psic_code = currentCat.psic_code
                }

                if (!String(this.rows[rowindex].order.products[productIndex].psic_code).replace(/\s/g, '').length) { //Если поле ИКПУ пустое
                    if (currentCat.psic_code) this.rows[rowindex].order.products[productIndex].psic_code = currentCat.psic_code //Проверяем есть ли ИКПУ у текущей родительской категории в рекурсии, если есть заполняем им поле для ИКПУ
                }
                if(currentCat?.parent_id && currentCat?.parent_id != 0)
                    this.setProdNameByCategoryIdRec(currentCat.parent_id, productIndex, rowindex, false)




            },
            fetchAllCategories(){
                this.fetchCategoriesByParentId('-1').then(({data}) => {
                    if (data?.data) {
                        let resp_data = data.data
                        resp_data.forEach((cat, index) => {
                            cat.children = null
                        })
                        this.allCategories = resp_data
                    }
                }).catch(err=> {
                    console.error(err);
                })
            },
            fetchCategoriesByParentId(parent_id = 0, search = null){
                let searchParam=''
                if (search) searchParam = `?search=${search}`

                return axios.get(`/api/v3/categories/panel-list${searchParam}`, {
                    headers: {'Content-Language':  window.Laravel.locale},
                    params: {
                        api_token: globalApiToken,
                        parent_id,
                        status: -1
                    }

                })
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
            categoryLabel(categoryId){
                const currentCat = this.allCategories.find(({id}) => categoryId === id)
                if (!currentCat){
                    return "Информация по выбранной категории не найдена"
                }
                return currentCat.title
            },
            ancestorsLabel(categoryId) {
                const currentCat = this.allCategories.find(({id}) => categoryId === id)
                if (!currentCat){
                    return ""
                }
                let categoriesTitle = ''

                if(currentCat.parent_id) {
                    categoriesTitle += this.ancestorsLabel(currentCat.parent_id)
                }
                categoriesTitle += `\\${currentCat.title}`
                return categoriesTitle
            },
            updatePsycCode(product, categoryId) {
                const category = this.getCategoryModelById(categoryId)
                if (category.psic_code) product.psic_code = category.psic_code
            },
            focusPsicCode(product){
                this.editingProductId = product.id;
                if (product.psic_code?.length) {
                    this.searchByPsicCode(product);
                } else {
                    this.psicCodeList = [];
                }
            },
            setPsicCode(product, cat, productIndex, rowindex) {
                this.rows[rowindex].order.products[productIndex].psic_code = cat.psic_code;
                this.rows[rowindex].order.products[productIndex].category_id = cat.id;
                this.setProdNameByCategoryIdRec(cat.id, productIndex, rowindex, true)
                this.psicCodeList = [];
            },
            searchByPsicCode(product, productIndex, rowindex) {
                if (product.psic_code?.length > 4) {
                    let row = this.rows[rowindex]
                    row.psicSearchLoading = productIndex
                    axios.get(`/api/v3/categories/search-by-psic_code?psic_code=${product.psic_code}`).then(resp => {
                        this.psicCodeList = resp.data.data
                        row.psicSearchLoading = false
                    }).catch((err)=>{
                        row.psicSearchLoading = false
                        console.error(err);
                    })
                }
            },
            filterByStatus(navlink) {
                this.pagination.currentPage = 1;
                this.clickedNavLink = navlink
                this.fetchData()
            },
        }
    })
</script>
