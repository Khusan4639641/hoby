<template>
    <a-layout class="page dashboard-page">
      <a-page-header title="Должники" sub-title="Выберите должника">
      </a-page-header>
      <a-table :columns="columns" :loading="loading" :data-source="debtors" :pagination="pagination" @change="onChange">
        <template #bodyCell="{ text, column, record }">
          <template v-if="column.key === 'full_name'">
            <router-link :to="{ name: 'debt-collect-curator-extended-debtor', params: { debtorId: record.id } }">
              {{ record.full_name }}  
            </router-link>
          </template>
          <template v-else-if="column.key === 'actions'">
              <a-button v-if="record.region" @click="showRegionsEditModal(record)">Изменить район</a-button>
          </template>
          <template v-else>
              {{ text }}  
          </template>
        </template>
        <template #title>
          <p>В ожидании</p>
          <a-divider/>
          <a-input-search
              ref="searchInput"
              placeholder="Поиск должников"
              enter-button="Поиск"
              v-model:value="searchModel"
              style="width: 100%;"
              :allow-clear="true"
              @keyup.enter="e => handleSearch(e.target.value)"
              @search="(val, e) => handleSearch(val)"
          />
      </template>
      </a-table>
      <SetRegionForExtendedModal  url-prefix="debt-collect-curator-extended" :active="modals.regionsEdit" v-if="modals.regionsEdit && currentDebtor" :debtor="currentDebtor" @submit="handleRegionsEdit" @cancel="hideEditRegionsModal" />

    </a-layout>
  </template>
  
  <script setup>
    import { ref, reactive, onBeforeMount } from 'vue'
    import apiRequest from '../../../../utils/apiRequest'
    import SetRegionForExtendedModal from '../../../../components/Debtor/SetRegionForExtendedModal'
    const modals = reactive({
        regionsEdit: false,
    })
    const columns = [
        {
            title: 'ID',
            dataIndex: 'id',
            key: 'id',
            fixed:true,
            width: 50
        },
        {
            title: 'ФИО',
            key: 'full_name',
            width: 200
        },
        {
            title: 'Номер телефона',
            dataIndex: 'phone',
            key: 'phone',
            width: 200
        },
        {
            title: '',
            key: 'actions',
            width: 100
        },
    
    ]
    const pagination = reactive({
        current: 1,
        total: 1,
        pageSize: 15,
        showSizeChanger:false,
        hidOnSinglePage:true,
        showQuickJumper:true
    })
    const searchModel = ref("")
    const currentDebtor = ref(null)
    const debtors = ref([])
    const loading = ref(false)
    const loadDebtors = async () => {
        loading.value = true
        try {
            const { data } = await apiRequest.get('/v3/debt-collect-curator-extended/analytic/debtors', {
                params: {
                    page: pagination.current,
                    search: searchModel.value,
                }
            })
            debtors.value = data.data
            pagination.current = data.current_page
            pagination.total = data.total
            pagination.pageSize = data.per_page
            loading.value = false
        } catch (e) {
            loading.value = false
            console.log(e)
        }
    }
    onBeforeMount(loadDebtors)
    const onChange = (newPagination) => {
        pagination.current = newPagination.current
        loadDebtors()
    }
    const handleSearch = (val) => { loadDebtors()}
    
    const showRegionsEditModal=(debtor)=>{
        currentDebtor.value = debtor
        modals.regionsEdit = true
    }
    const hideEditRegionsModal=(debtor)=>{
        currentDebtor.value = null
    }
    const handleRegionsEdit = async () => {
        loadDebtors()
        modals.regionsEdit = false
    }
  </script>