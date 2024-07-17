@extends('templates.panel.app')

@section('title', __('panel/contract.header_contracts'))
@section('class', 'contracts list')

@section('content')
<style>
    #contract_verify .products-container span.validation-error{
        color: red;
        font-size: 12px;
        line-height: 12px;
        display: block;
        margin-top: 4px;
        position: absolute;
        bottom: -18px;
    }
    .form-group {
        margin-bottom: 1.5rem;
        position: relative;
    }
    #contract_verify .product-table tr:first-child td {
        border-top: none;
        border-bottom: none;
    }
    #contract_verify .product-table tr:last-child td {
        border-bottom: none;
    }
    #contract_verify .product-table thead th {
        border:none;
        padding: 8px 12px;
        vertical-align: middle;
        font-size: 12px;
    }
    #contract_verify .product-table tbody td {
     padding: 8px 12px;
    }
    span.product-data:not(.empty){
        border: 1px dashed #c4c4c4;
    }
    span.product-data.empty{
        min-width: 100%;
        display: inline-flex;
        justify-content: center;
        padding: 12px;
        height: 50px;
        font-size: 16px;
        line-height: 50px;
        letter-spacing: 0.01em;
        color: var(--orange);
        max-width: 250px;
        width: 250px;
        overflow: hidden;
        align-items: center;
    }
    span.product-data{

    }
    #contract_verify .product-table tr.editing, #contract_verify .product-table tr.editing:hover  {
       background-color: rgba(255, 118, 67, 0.15);
    }
    #contract_verify .product-table.editing tr td  {
        border-color: transparent;
    }

    #contract_verify :not(.product-table) tr th:first-child {
       width: 100px;
    }
    .multiselect.categories, .form-control.modified {
        width: 250px;
        max-width: 250px;
    }
    .multiselect.units {
        width: 150px;
        max-width: 150px;
    }
    #contract_verify table:not(.product-table) tbody tr:hover{
        background-color: #fafafa;
    }
</style>
<style>
    .pagination__container {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0 20px;
        padding: 1rem 2rem;
    }

    .pagination.modified {
        margin: 0;
        height: 100%;
        padding: 4px;
        border-radius: 8px;
        border: 1px solid #dee2e6;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .pagination__txt {
        margin: 0;
        color: var(--secondary);
        font-size: 16px;
    }
    .pagination__item {
        color: var(--orange);
    }
    .pagination__item.active {
        color: #fff;
    }
    .pagination__item a{
        border-radius: 4px;
        color: var(--orange);
        box-shadow: none;
        border: none;
        cursor: pointer;
        background-color: #ff764317;
        transition: all .25s ease-in;
        display: inline-flex;
        align-items: center;
        padding: 8px 14px;
    }
    .pagination__item.active a{
        background-color: var(--orange);
        color: #fff;
    }
    .pagination__item.disabled a, .pagination__container.disabled{
        pointer-events: none;
        filter: grayscale(1);
        opacity: 0.6;
    }
    .pagination__item a:hover{
        background-color: #ff76432b;
    }
    .pagination__item.active a:hover{
        background-color: var(--orange);
    }
    .pagination__item a:active, .pagination__item a:focus{
        box-shadow: none;
        outline: none;
    }
    .content .center .center-body {
        background: transparent;
        padding: 0;
    }
    .center-body {
        min-height: auto !important;
    }
    #contract_verify{
        background: #fff;
        border-radius: 8px;
        padding: 0;
    }
    #contract_verify .table thead th {
        padding: .75rem 2rem;
        background-color: #f8f8f8;
    }
    #contract_verify .table tbody td {
        padding: 1.5rem 2rem;
    }
    #contract_verify table.contract-list tbody tr.main-row.expanded{
        border-top: 2px solid var(--orange);
        border-right: 2px solid var(--orange);
        border-left: 2px solid var(--orange);
    }
    #contract_verify table.contract-list tbody tr.main-row.expanded+tr{
        border-bottom: 2px solid var(--orange);
        border-right: 2px solid var(--orange);
        border-left: 2px solid var(--orange);
    }
    #contract_verify table.contract-list tbody tr.expandable-row td{
        background-color: var(--peach);
    }
    tbody {
        border: none !important;
    }
    .content .container-fluid .container-row .left-menu.active {
        overflow: hidden;
        max-height: 100vh;
    }
    .treeselect-custom-value-label .treeselect-custom-ancestors-label{
        position: absolute;
        top: calc(100% - 14px);
        left: 0;
        color: var(--orange);
        font-size: 12px;
        line-height: 14px;
        padding: 0 12px;
        background: #fff;
        border-radius: 0 0 8px 8px;
        width: 100%;
        white-space: break-spaces;
        border-top: none;
        box-shadow: 0 13px 8px #0000000d;
    }
    .vue-treeselect__control:hover {
        overflow: visible;
    }
    .vue-treeselect__control:hover .vue-treeselect__single-value{
        overflow: visible;
    }
    .treeselect-custom-value-label:hover .treeselect-custom-ancestors-label{
        height: calc(100% - 12px);
    }
    .dropdown-menu {
        max-height: 200px;
        overflow-y: scroll
    }

    .dropdown-menu::-webkit-scrollbar {
        width: 5px;
        background-color: #F5F5F5;
    }

    .dropdown-menu::-webkit-scrollbar-thumb {
        border-radius: 10px;
        -webkit-box-shadow: inset 0 0 6px  var(--primary);
        background-color:  var(--primary);
    }

    .product-name {
        position: relative;
    }
    .product-name input {
        width: 100% !important;
        max-width: 100% !important;
    }

    .product-name__info {
        pointer-events: none;
        opacity: 0;
        position: absolute;
        top: 100%;
        transition: all 300ms ease-out;
        padding: 12px;
        z-index: 99;
        margin-top: .5rem;
        border-radius: 8px;
        word-wrap: break-word;
        width: 100%;
        transform: scale(0.95) translateY(.5rem);
    }

    .product-name input:focus ~ .product-name__info {
        opacity: 1;
        transform: scale(1) translateY(0);
        background: #fff;
    }

    .filter {
        color: var(--primary);
        border-color: var(--primary)!important;
        width: 266px;
    }

    .filter select {
        width: 150px;
        white-space: normal;
    }

    .psic-dropdown.dropdown-menu{
        max-height: 200px;
        overflow-y: auto;
        overflow-x: hidden;
        margin-top: 0.5rem;
        width:100%;
        animation: zoom-in-up .3s ease-out;
    }
    .psic-dropdown.dropdown-menu.show{
        background: #fff;
    }
    .psic-dropdown .dropdown-item:active {
        color: #fff !important;
        text-decoration: none;
        background-color: var(--orange);
    }
    .psic-dropdown .dropdown-item:active span{
        color: #fff !important;
    }

    @keyframes zoom-in-up {
        from {
            opacity: 0;
             transform: scale(0.95) translateY(.5rem);
        }
        to{
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }
    .form-group .spinner-border {
        width: 24px;
        height: 24px;
        border: 2px solid currentColor ;
        border-right-color: transparent;
        color: var(--orange);
        position: absolute;
        right: 12px;
        top: 42px;
        display: none;
    }
    .form-group.processing .spinner-border {
        position: absolute;
        display: inline-block;
    }
    .vue-treeselect__list-item.vue-treeselect__indent-level-0>.vue-treeselect__option{
        padding-top: 8px;
        padding-bottom: 8px;
    }
    .vue-treeselect.vue-treeselect--open .vue-treeselect__menu .vue-treeselect__option--highlight:not(.vue-treeselect__option--selected) {
        background: #f9f9f9;
    }
    .vue-treeselect__list-item.vue-treeselect__indent-level-0:not(:last-child) {
        border-bottom: 1px solid #e8e8e8;
    }
    .vue-treeselect.vue-treeselect--open .vue-treeselect__menu .vue-treeselect__option .vue-treeselect__option-arrow-container {
        width: 36px;
    }
    .vue-treeselect.vue-treeselect--open .vue-treeselect__menu .vue-treeselect__option.vue-treeselect__option--disabled .vue-treeselect__option-arrow-container{
        pointer-events: none;
        opacity: 0;
        cursor: none;
    }

    input::-webkit-outer-spin-button,
    input::-webkit-inner-spin-button {
        /* display: none; <- Crashes Chrome on hover */
        -webkit-appearance: none;
        margin: 0; /* <-- Apparently some margin are still there even though it's hidden */
    }

    input[type=number] {
        -moz-appearance:textfield; /* Firefox */
    }

</style>
    <div id="contract_verify">

        <div class="top p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <ul class="nav nav-tabs m-0">
                    <li class="nav-item cursor-pointer" v-for="navLink in navLinks" >
                        <a
                            :class="navLink.id === clickedNavLink?.id ? 'nav-link active' : 'nav-link'"
                            href="#"
                            @click.prevent="filterByStatus(navLink)"
                        >
                            @{{ navLink.label }} (@{{ navLink.ordersCount }})
                        </a>
                    </li>
                </ul>
                <div class="d-flex align-items-center border rounded p-2 filter">
                    <p class="m-0 mr-1">Компания:</p>
                    <select class="my-select border-0" v-model="filterByCompany" style="width: 166px">
                        <option :value="null">Все</option>
                        <option :value="company" v-for="company in companies">@{{ company.label }}</option>
                    </select>
                </div>
            </div>


            <div class="d-flex w-50">
                <form
                    class="dataTablesSearch d-flex pt-0 pb-0 m-0"
                    style="width: 500px"
                    @submit.prevent="fetchData({id: idInputVal})"
                >
                    <input v-model="idInputVal"  class="form-control mr-1" type="number" placeholder="{{__('panel/buyer.search_id')}}">
                    <button class="btn btn-primary" type="submit">{{__('billing/order.search')}}</button>
                </form>
                <form
                    class="dataTablesSearch d-flex pt-0 pb-0 m-0 ml-3"
                    @submit.prevent="fetchData({companyName: nameInputVal})"
                    style="width: 500px"
                >
                    <input v-model="nameInputVal" class="form-control mr-1" type="text" placeholder="{{__('offer.seller')}}">
                    <button class="btn btn-primary" type="submit">{{__('billing/order.search')}}</button>
                </form>
            </div>
            <div
                class="w-50 d-flex align-items-center border rounded p-2 mt-4 filter"
                v-if="clickedNavLink?.id === navLinks.confirmedWithoutCheque.id"
            >
                <p class="m-0 mr-1" style="width: 110px">Тип ошибки:</p>
                <select
                    class="my-select border-0"
                    style="width: 100%"
                    v-model="filterByUzTaxErrorCode"
                >
                    <option :value="null">Все</option>
                    <option :value="uzTaxErrorCode.id" v-for="uzTaxErrorCode in uzTaxErrorCodes">@{{ uzTaxErrorCode.label }}</option>
                </select>
            </div>
        </div>

        <div class="table-responsive" >
            <data-table :columns="tableColumns" :rows="rows">

                <template v-slot:company="{item}">
                    @{{item?.company?.name || 'Нет данных'}}
                </template>

                <template v-slot:products="{item, rowindex}" >
                    <div class="products-container" v-if="item?.order">
                        <div class="row" :class="{'editing':item.isEditing}" v-for="(product, productIndex) in item.order.products" :key="productIndex">
                            <div class="col-auto">
                                <div class="form-group">
                                    <label style="height: 24px;"></label>
                                    <span class="w-auto px-0 border-0 bg-transparent product-data form-control modified">
                                        @{{  productIndex + 1 }}
                                    </span>
                                </div>
                            </div>
                            <div class="col-3 pl-0">
                                <div class="form-group product-name">
                                    <label>{{__('billing/order.lbl_product_name')}}</label>
                                    <input v-if="item.isEditing" :readonly="item.verified === 1" :title="product.name" type="text" v-model="product.name" class="form-control modified bg-white">
                                    <span v-show="product.name.length" class="product-name__info shadow-lg">
                                        @{{ product.name }}
                                    </span>
                                    <span :title="product.name"  v-else class="product-data form-control modified">
                                        @{{  product.name }}
                                    </span>
                                    <span class="validation-error" v-if="item.isEditing && rowValidation.hasOwnProperty(`${productIndex}.name`)">@{{rowValidation[`${productIndex}.name`]}}</span>
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="form-group" style="width: 500px;">
                                    <label>{{__('billing/order.lbl_product_category')}}</label>
                                    <treeselect
                                        :append-to-body="true"
                                        bg-color="white"
                                        :instanceId="`cat_${rowindex}_${productIndex}`"
                                        :clearable="false"
                                        search-prompt-text="Начните печатать для поиска..."
                                        v-if="item.isEditing"
                                        v-model="product.category_id"
                                        :multiple="false"
                                        class="modified"
                                        placeholder="{{__('billing/order.search')}}"
                                        :async="true"
                                        loading-text=" Загрузка..."
                                        :options="categories"
                                        search-nested
                                        :normalizer="treeSelectNormalizer"
                                        :load-options="loadTreeSelectOptions"
                                        @select="(node, instanceId)=>categoriesSelected(node, instanceId, productIndex, rowindex)"
                                        @input="(categoryId) => updatePsycCode(product, categoryId)"
                                        :disabled="item.verified === 1"
                                    >
                                        <div slot="value-label" slot-scope="{ node }">
                                            <div class="treeselect-custom-value-label" :title="`${categoryLabel(node.id).ancestorsLabel}`">
                                                <span v-if="ancestorsLabel(node.id)" class="treeselect-custom-ancestors-label">@{{ancestorsLabel(node.id)}}</span>
                                                @{{ categoryLabel(node.id) }}. @{{ getCategoryById(node.id).psic_code }}
                                            </div>
                                        </div>
                                        <label :title="node.label" slot="option-label" slot-scope="{ node, shouldShowCount, count, labelClassName, countClassName }" style="line-height: normal;" :class="labelClassName">
                                            <span  class="d-flex" >@{{node.label}}</span>
                                            <span v-if="node.raw.hierarchy_title" style="font-size: 12px; color: #a0a0a0; white-space: normal;">@{{node.raw.hierarchy_title}}</span>
                                            <span v-if="shouldShowCount" :class="countClassName">(@{{ count }})</span>
                                        </label>
                                    </treeselect>
                                    <span style="width:350px; max-width: 350px;" :title="getCategoryById(product.category_id).title" v-else class="product-data form-control modified" v-html="`${getCategoryById(product.category_id).title} . ${ getCategoryById(product.id).psic_code }`">
                                    </span>
                                    <span class="validation-error" v-if="item.isEditing && rowValidation.hasOwnProperty(`${productIndex}.category`)">@{{rowValidation[`${productIndex}.category`]}}</span>
                                </div>
                            </div>
                            <div class="col-auto pl-0">
                                <div class="form-group" :class="{'processing': String(item.psicSearchLoading) == String(productIndex)}">
                                    <label>ИКПУ</label>
                                    <input
                                        v-if="item.isEditing"
                                        type="tel"
                                        class="form-control modified bg-white"
                                        v-mask="'########################'"
                                        v-model="product.psic_code"
                                        @focus="focusPsicCode(product)"
                                        @keyup="searchByPsicCode(product, productIndex, rowindex)"
                                        :readonly="item.verified === 1"
                                    >
                                    <div class="spinner-border" role="status">
                                        <span class="sr-only">Загрузка...</span>
                                    </div>
                                    <span :title="product.psic_code" v-else class="product-data form-control modified">
                                        @{{product.psic_code}}
                                    </span>
                                    <div v-if="psicCodeList.length && product.id === editingProductId" style="border-radius: 8px;" class="psic-dropdown dropdown-menu border-0 p-0 shadow-lg show">
                                        <ul class="m-0 p-0">
                                            <li
                                                v-for="(cat, index) in psicCodeList"
                                                :key="index" class="dropdown-item py-2 px-3 cursor-pointer"
                                                @click="setPsicCode(product, cat, productIndex, rowindex)"
                                            >
                                            <span class="d-flex">@{{ cat.psic_code }}</span>
                                            <span  style="font-size: 12px; color: var(--orange)">@{{ cat.title }}</span>
                                            </li>
                                        </ul>
                                    </div>
                                    <span class="validation-error" v-if="item.isEditing && rowValidation.hasOwnProperty(`${productIndex}.psic_code`)">@{{rowValidation[`${productIndex}.psic_code`]}}</span>
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="form-group" style="max-width: 150px;">
                                    <label>Ед.изм</label>
                                    <treeselect
                                        :append-to-body="true"
                                        bg-color="white"
                                        :instanceId="`unit_${rowindex}_${productIndex}`"
                                        :clearable="false"
                                        :disable-branch-nodes="true"
                                        v-if="item.isEditing"
                                        v-model="product.unit_id"
                                        :multiple="false"
                                        placeholder="Выберите ед.изм"
                                        class="modified"
                                        :loading-text="'Загрузка...'"
                                        :options="units"
                                        :normalizer="treeSelectNormalizer"
                                        :load-options="loadTreeSelectOptions"
                                        :disabled="item.verified === 1"
                                    ></treeselect>
                                    <span style="max-width: 150px;" :title="getUnitById(product.unit_id).title" v-else class="product-data form-control modified" v-html="getUnitById(product.unit_id).title">
                                    </span>
                                    <span class="validation-error" v-if="item.isEditing && rowValidation.hasOwnProperty(`${productIndex}.unit`)">@{{rowValidation[`${productIndex}.unit`]}}</span>
                                </div>
                            </div>
                            <div class="col text-right" v-if="productIndex === (item.order.products.length - 1)">
                                <div class="form-group">
                                    <label style="height: 24px;"></label>
                                    <button style="margin-right: 17px;" v-if="item.isEditing && item.verified === 0" :disabled="Object.keys(rowValidation).length > 0" class="btn btn-orange float-right" @click="verifyContractProductChanges(rowindex)">Сохранить</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="empty-container" v-else>
                        <span class="product-data empty rounded text-orange" >Нет товаров</span>
                    </div>
                </template>

                <template v-slot:actions="{item, rowindex}">
                    <div v-if="item?.order?.products?.length" class="action-buttons">
                        <button v-if="item.isEditing"  style="padding: 0 0.2rem; line-height: 20px; height: 20px" class="btn btn-red-light float-right" @click="cancelEditingAndSetBack()">@{{ item.verified === 0 ? "Отменить" : "Закрыть" }}</button>
                        <button v-if="!item.isEditing" style="padding: 0 0.2rem; line-height: 20px; height: 20px" class="btn float-right" :style="`color: ${item.verified === 1 ? 'var(--primary)' : 'var(--orange)'}`" @click="compareCompanyAccounting(rowindex,item.company.id )">@{{ item.verified === 0 ? "Редактировать" : "Подтвержден" }}</button>
                    </div>
                    <div v-else class="empty-products text-muted px-2 text-right">
                        Нет товаров
                    </div>
                </template>

            </data-table>
        </div>

        <div v-if="pagination.pageCount > 1" class="pagination__container" :class="{'disabled': loading}" >
            <span v-if="!loading"  class="pagination__txt">страница @{{ pagination.currentPage || 0 }} из @{{ pagination.pageCount || 0 }}</span>
            <span v-else ></span>
            <select-pagination
                @prev-button="prevButton"
                @next-button="nextButton"
                :pagination="pagination"
            ></select-pagination>
        </div>

        <div class="loading " :class="{'active' : loading}"><img src="{{asset('images/media/loader.svg')}}"></div>

    </div>

    @include('panel.components.SelectPagination')
    @include('panel.contract_verify.parts.tableComponent')
    @include('panel.contract_verify.parts.index')
@endsection
