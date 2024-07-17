<template>
    <a-layout class="page dashboard-page">
      <a-page-header title="Кураторы" sub-title="Выберите куратора">
      </a-page-header>
      <a-table bordered :columns="columns" :loading="loading" :data-source="curators" :pagination="pagination" @change="onChange">
        <template #bodyCell="{text, column, record }">
          <template v-if="column.key === 'regions'">
                <div v-if="record.regions.length">
                    <div v-for="region in record.regions" :key="region.id" class="cont">
                        <h6 class="m-0">{{region.name}}</h6>
                        <div class="district-tags mb-2 mt-1" v-if="!region.all_districts_attached">
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
          <template v-else> {{text}}
          </template>
        </template>
        <template #title>
            <a-input-search
                ref="searchInput"
                placeholder="Поиск по кураторам"
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
    <SetRegionModal :active="modals.regions" v-if="modals.regions && currentCurator" :curator="currentCurator" @submit="handleRegions" :unique="true" @cancel="hideRegionsModal" />
  </template>

<script setup>
    import { ref, reactive, onBeforeMount } from 'vue'
    import apiRequest from '../../../../utils/apiRequest'
    import SetRegionModal from '../../../../components/Curator/SetRegionModal'
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
        pageSize: 15,
        showSizeChanger:false,
        hidOnSinglePage:true,
        showQuickJumper:true
    })
    const currentCurator = ref(null)
    const curators = ref([])
    const loading = ref(false)
    const loadCurators = async () => {
        loading.value = true
        let searchValue = searchModel.value
        let params = {page: pagination.current}
        if (searchValue.replace(/\s/g, '').length) params.search = searchValue
        try {
            const { data } = await apiRequest.get('/v3/debt-collect-leader/curators', {params})
            curators.value = data.data
            pagination.current = data.current_page
            pagination.total = data.total
            pagination.pageSize = data.per_page
            loading.value = false
        } catch (e) {
            loading.value = false
            console.log(e)
        }
    }

    onBeforeMount(loadCurators)

    const onChange = (newPagination) => {
        pagination.current = newPagination.current
        loadCurators()
    }
    const handleSearch = (value) => {
      pagination.current = 1
      loadCurators(value)
    }
    const showRegionsModal=(curator)=>{
        currentCurator.value = curator
        modals.regions = true
    }
    const hideRegionsModal=(curator)=>{
        currentCurator.value = null
    }
    const handleRegions = async () => {
        loadCurators()
        modals.regions = false
    }

</script>
