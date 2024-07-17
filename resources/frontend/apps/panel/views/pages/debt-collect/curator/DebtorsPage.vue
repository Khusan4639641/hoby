<template>
    <a-layout class="page dashboard-page">
      <a-page-header title="Должники" sub-title="Выберите должника">
      </a-page-header>
      <a-table :columns="columns" :loading="loading" :data-source="debtors" :pagination="pagination" @change="onChange">
        <template #bodyCell="{ text, column, record }">
          <template v-if="column.key === 'full_name'">
            <router-link :to="{ name: 'debt-collect-curator-debtor', params: { debtorId: record.id } }">
              {{ record.full_name }}  
            </router-link>
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
    </a-layout>
  </template>
  
  <script setup>
    import { ref, reactive, onBeforeMount } from 'vue'
    import apiRequest from '../../../../utils/apiRequest'

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
    const debtors = ref([])
    const loading = ref(false)
    const loaddebtors = async () => {
        loading.value = true
        try {
            const { data } = await apiRequest.get('/v3/debt-collect-curator/analytic/debtors', {
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
    onBeforeMount(loaddebtors)
    const onChange = (newPagination) => {
        pagination.current = newPagination.current
        loaddebtors()
    }
    const handleSearch = (val) => { loaddebtors()}
  </script>