<template>
    <a-layout class="page dashboard-page">
      <a-page-header title="Коллекторы" sub-title="Выберите коллектора">
      </a-page-header>
      <a-table bordered :columns="columns" :loading="loading" :data-source="collectors" :pagination="pagination" @change="onChange">
        <template #bodyCell="{text, column, record }">
          <template v-if="column.key === 'name'">
            <router-link :to="{ name: 'debt-collect-leader-collector', params: { collectorId: record.id } }">
              {{ record.full_name }}
            </router-link>
          </template>
          <template v-if="column.key === 'regions'">
                <div v-if="record.regions.length">
                    <div v-for="region in record.regions" :key="region.id" class="cont">
                        <h6 class="m-0">{{region.name}}</h6>
                        <div class="district-tags mb-2 mt-1">
                                <a-tag
                                v-for="district in region.districts"
                                :key="district"
                                color="grey"
                            >
                                {{ district.name}}
                            </a-tag>
                        </div>

                    </div>
                </div>
          </template>
          <template v-else-if="column.key === 'actions'">
            <a-button @click="showRegionsModal({id:record.id, full_name:record.full_name})" type="primary" >Редактировать районы</a-button>
        </template>
        </template>
        <template #title>
            <a-input-search
                ref="searchInput"
                placeholder="Поиск по коллекторам"
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
    <SetRegionModal :active="modals.regions" v-if="modals.regions && currentCollector" :collector="currentCollector" :unique="false" @submit="handleRegions" @cancel="hideRegionsModal" />
  </template>

<script setup>
    import { ref, reactive, onBeforeMount } from 'vue'
    import apiRequest from '../../../../utils/apiRequest'
    import SetRegionModal from '../../../../components/Collector/SetRegionModal'
    const modals = reactive({
        regions: false,
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
            title: 'Телефон',
            dataIndex: 'phone',
            key: 'phone'
        },
        {
            title: 'Районы',
            dataIndex: 'regions',
            key: 'regions'
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
        pageSize:15,
        showSizeChanger:false,
        hidOnSinglePage:true,
        showQuickJumper:true
    })
    const currentCollector = ref(null)
    const collectors = ref([])
    const loading = ref(false)
    const loadCollectors = async () => {
        loading.value = true
        let searchValue = searchModel.value
        let params = {page: pagination.current}
        if (searchValue.replace(/\s/g, '').length) params.search = searchValue
        try {
            const { data } = await apiRequest.get('/v3/debt-collect-leader/collectors', {params})
            collectors.value = data.data
            pagination.current = data.current_page
            pagination.total = data.total
            pagination.pageSize = data.per_page
            loading.value = false
        } catch (e) {
            loading.value = false
            console.log(e)
        }
    }

    onBeforeMount(loadCollectors)

    const onChange = (newPagination) => {
        pagination.current = newPagination.current
        loadCollectors()
    }
    const handleSearch = (value) => {
      pagination.current = 1
      loadCollectors(value)
    }
    const showRegionsModal=(collector)=>{
        currentCollector.value = collector
        modals.regions = true
    }
    const hideRegionsModal=(collector)=>{
        currentCollector.value = null
    }
    const handleRegions = async () => {
        loadCollectors()
        modals.regions = false
    }

</script>
