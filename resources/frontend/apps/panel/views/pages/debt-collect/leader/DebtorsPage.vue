<template>
    <a-layout class="page dashboard-page">
      <a-page-header title="Должники" sub-title="Выберите должника">
      </a-page-header>
      <a-table bordered :columns="columns" :loading="loading" :dataSource="debtors" :pagination="pagination" @change="onChange">
        <template #bodyCell="{text, column, record }">
          <template v-if="column.key === 'name'">
            <router-link :to="{ name: 'debt-collect-leader-debtor', params: { debtorId: record.id } }">
              {{ record.full_name }}
            </router-link>
          </template>
          <template v-if="column.key === 'region'">
                  <div v-if="record.region"> {{record.region.name}}, {{ record.district.name}}</div>
                  <div v-else class="text-muted"> Район не привязан </div>
          </template>
          <template v-else-if="column.key === 'actions'">
              <a-button v-if="!record.region" @click="showRegionsAddModal({id:record.id, full_name:record.full_name})" type="primary" >Привязать район</a-button>
              <a-button v-else @click="showRegionsEditModal(record)"  >Изменить район</a-button>
          </template>
        </template>
        <template #title>
            <a-input-search
                ref="searchInput"
                placeholder="Поиск по должникам"
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
    <SetRegionModal :active="modals.regionsAdd" v-if="modals.regionsAdd && currentDebtor" :debtor="currentDebtor" @submit="handleRegionsAdd" @cancel="hideAddRegionsModal" />
    <SetRegionForExtendedModal  url-prefix="debt-collect-leader" :active="modals.regionsEdit" v-if="modals.regionsEdit && currentDebtor" :debtor="currentDebtor" @submit="handleRegionsEdit" @cancel="hideEditRegionsModal" />

  </template>

<script setup>
    import { ref, reactive, onBeforeMount } from 'vue'
    import apiRequest from '../../../../utils/apiRequest'
    import SetRegionModal from '../../../../components/Debtor/SetRegionModal'
    import SetRegionForExtendedModal from '../../../../components/Debtor/SetRegionForExtendedModal'
    const modals = reactive({
        regionsAdd: false,
        regionsEdit: false,
    })
    const columns = [
        {
            title: 'ID',
            dataIndex: 'id',
            key: 'id',
            width: 80
        },
        {
            title: 'Ф.И.О',
            dataIndex: 'full_name',
            key: 'name'
        },
        {
            title: 'Районы',
            dataIndex: 'region',
            key: 'region'
        },
        {
            title: '',
            key: 'actions',
            width: 100
        },
    ]
    const searchModel = ref('')
    const pagination = reactive({
        current: 1,
        total: 1,
        pageSize: 15,
        showSizeChanger:false,
        hidOnSinglePage:true,
        showQuickJumper:true
    })
    const currentDebtor = ref(null)
    const debtors = ref([])
    const loading = ref(false)
    const loadDebtors = async () => {
        loading.value = true
        let searchValue = searchModel.value
        let params = {page: pagination.current}
        if (searchValue.replace(/\s/g, '').length) params.search = searchValue

        try {
            const { data } = await apiRequest.get('/v3/debt-collect-leader/debtors', {params})
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
    const handleSearch = (value) => {
      pagination.current = 1
      loadDebtors(value)
    }
    const showRegionsAddModal=(debtor)=>{
        currentDebtor.value = debtor
        modals.regionsAdd = true
    }
    const hideAddRegionsModal=(debtor)=>{
        currentDebtor.value = null
    }
    const handleRegionsAdd = async () => {
        loadDebtors()
        modals.regionsAdd = false
    }

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
