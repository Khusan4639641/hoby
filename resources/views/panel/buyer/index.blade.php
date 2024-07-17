@extends('templates.panel.app')

@section('title', __('panel/buyer.header'))
@section('class', 'buyers list')

@section('content')
    <ul class="nav nav-tabs m-0 p-0">
        <li class="nav-item" v-for="tab in tabs" >
            <a :class="tab.status == clickedTab ? 'nav-link active' : 'nav-link'" :href="tab.name" @click="filterBy(tab.status)">@{{ tab.label }}</a>
        </li>
    </ul>

    <form class="dataTablesSearch mt-3" @submit.prevent="buyerSearch(false)">
        <div class="input-group">
            <input type="search" v-model.trim="buyer.id" class="form-control" placeholder="{{__('panel/buyer.search_id')}}">
            <input type="search" v-model.trim="buyer.surname" class="form-control ml-2" placeholder="{{__('panel/buyer.search_by_surname')}}">
            <input type="search" v-model.trim="buyer.name" class="form-control ml-2" placeholder="{{__('panel/buyer.search_by_name')}}">
            <input type="search" v-model.trim="buyer.phone" class="form-control  ml-2 mr-2" placeholder="{{__('panel/buyer.search_by_phone')}}">
            <input type="search" v-model.trim="buyer.passportNumber" v-mask="'AA#######'" class="form-control  ml-2 mr-2" placeholder="{{__('panel/buyer.search_by_passport')}}">
            <div class="input-group-append">
                <button class="btn btn-primary" type="submit" :disabled="buyer.passportNumber?.length && buyer.passportNumber?.length < 9 || isLoading">
                    {{__('app.btn_find')}}
                </button>
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="my-table table table-hover mt-4">
            <thead>
            <tr>
                <th>{{__('app.status')}}</th>
                <th>{{__('app.kyc_user')}}</th>
                <th>{{__('app.date_changed')}}</th>
                <th>{{__('panel/buyer.buyer_id')}}</th>
                <th>{{__('panel/buyer.buyer_fio')}}</th>
                <th>{{__('panel/buyer.passport')}}</th>
                <th>{{__('cabinet/profile.gender_title')}}</th>
                <th>{{__('cabinet/profile.birthday')}}</th>
                <th>{{__('panel/buyer.phone')}}</th>
                <th>{{__('panel/buyer.limit')}}</th>
                <th>{{__('panel/buyer.debt')}}</th>
                <th>{{__('panel/buyer.black_list')}}</th>
            </tr>
            </thead>
            <tbody v-if="buyersList?.length">
            <tr
                v-for="buyer in buyersList"
                :key="buyer.id"
                @click="() => window.open(`{{localeRoute('panel.buyers.index')}}/${buyer.id}`)"
                class="cursor-pointer"
            >
                <td :class="{'passport' : buyer.reason}">
                    <div class="d-flex align-items-center mb-0">
                        <img class="mr-2" :src="buyer.icon" width="20" height="20" :alt="buyer.status"/>
                        <span>@{{ buyer.status }}</span>
                    </div>
                    <span v-show="buyer.reason" class="text-red font-size-12">@{{ buyer.reason }}</span>
                </td>
                <td>@{{ buyer.kyc_user }}</td>
                <td>@{{ buyer.updated_data }}</td>
                <td>@{{ buyer.id }}</td>
                <td>@{{ buyer.fio }}</td>
                <td class="text-red">@{{ buyer.passport_number }}</td>
                <td>@{{ buyer.gender }}</td>
                <td>@{{ buyer.birth_date }}</td>
                <td>@{{ buyer.phone }}</td>
                <td>@{{ buyer.limit }}</td>
                <td :class="{'text-danger' : buyer.totalDebt > 0}">@{{ buyer.totalDebt }}</td>
                <td>
                    <img v-if="buyer.black_list == 1" src="{{asset('/images/black_list.png')}}" width="25" height="25">
                </td>
            </tr>
            </tbody>
            <tbody v-else>
            <tr>
                <td colspan="12">
                    <h4 class="text-center">Таблица пуста</h4>
                </td>
            </tr>
            </tbody>
        </table>
    </div>

    <div v-show="isLoading" class="loading active"><img src="{{asset('images/media/loader.svg')}}" alt="loader"></div>
    <div v-if="pageCount > 1" class="d-flex justify-content-between align-items-center flex-wrap">
        <span v-if="!isLoading" class="text-secondary">страница @{{ currentPage || 0 }} из @{{ pageCount || 0 }}</span>
        <span v-else ></span>
        <div class="d-flex justify-content-end align-items-center pagination">
            <button
                class="page-item page-link"
                :disabled="isLoading"
                @click="prevButton"
            >
                Предыдущая
            </button>
            <div class="page-item page-item-select page-link d-flex align-items-center justify-content-between" >
                <select id="my-select" v-model="currentPage" onfocus='this.size=5;' onblur='this.size=1;' onchange='this.size=1; this.blur();'>
                    <option  v-for="page in pageCount" :value="page">@{{ page }}</option>
                </select>
                <span>из @{{ pageCount }}</span>
            </div>
            <button
                @click="nextButton"
                class="page-item page-link"
            >
                Следующая
            </button>
        </div>
    </div>


    @include('panel.buyer.parts.list')

@endsection
