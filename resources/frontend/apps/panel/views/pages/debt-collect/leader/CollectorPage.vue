<template>
    <a-layout class="page contract-page">
      <a-page-header
        :title="`Коллектор  ${collector.full_name || ''}`"
        @back="goBack"
        >
      </a-page-header>
      <a-card :loading="loading">

        <a-card-grid :hoverable="false" style="width: 100%">
          <a-card-meta title="Личные данные">
            <template #description>
              <p>
                <b>Ф.И.О.:</b> {{ collector.full_name }}
                <br>
                <b>Телефон:</b> {{ collector.phone }}
              </p>
            </template>
          </a-card-meta>
        </a-card-grid>
        <a-card-grid :hoverable="false" style="width: 100%">
          <a-card-meta title="Кураторы">
            <template #description>
              <p v-for="curator in collector.curators" :key="curator.id">
                <b>Ф.И.О.:</b> {{ curator.full_name }}
                <br>
                <b>Телефон:</b> {{ curator.phone }}
              </p>
            </template>
          </a-card-meta>
        </a-card-grid>
        <a-card-grid :hoverable="false" style="width: 100%">
            <a-card-meta title="Регионы">
                    <template #description>
                        <template v-if="collector.regions.length > 0">
                        <p v-for="region in collector.regions" :key="region.id">
                          <b>{{ region.name }}</b>
                          <br>
                          <template v-for="district in region.districts" :key="district.id">
                            - {{ district.name }}
                          </template>
                        </p>
                        </template>
                        <template v-else>
                        <p>
                            <b>Регионы отсутствуют</b>
                        </p>
                        </template>
                    </template>
            </a-card-meta>
        </a-card-grid>
        <a-card-grid :hoverable="false" style="width: 100%">
          <a-card-meta title="Управление должниками коллектора">
            <template #description>
              <a-row justify="space-between" >
                <a-col :span="24">
                  <a-table
                      bordered
                      :loading="debtorsData.loading"
                      :row-key="(record)=>{return record.id}"
                      :columns="columns"
                      :data-source="debtorsData.data"
                      size="small"
                      :pagination="debtorsData.pagination"
                      @change="handlePagination"
                  >
                  
                    <template #title>
                      <a-input-search
                          ref="searchInput"
                          placeholder="Поиск должников"
                          enter-button="Поиск"
                          v-model:value="debtorsData.search"
                          style="width: 100%;"
                          :allow-clear="true"
                          @keyup.enter="loadDebtors"
                          @search="loadDebtors"
                      />
                    </template>
                    <template #bodyCell="{ text, column, record }">


                      <template v-if="column.key === 'full_name'">
                        <router-link :to="{ name: 'debt-collect-leader-debtor', params: { debtorId: record.id } }">
                          {{ record.full_name }}
                        </router-link>
                      </template>
                      <!-- <template  v-else-if="column.key === 'region'">
                        <div style="float:left;">{{ record.region?.name }}, {{ record.district?.name }} </div>
                        <a-button type="dashed" style="float:right;"  @click="showRegionsModal(record, 'left')" size="small">
                            Изменить
                        </a-button>
                      </template> -->


                    </template>
                  </a-table>
                </a-col>
              </a-row>
            </template>
          </a-card-meta>
        </a-card-grid>
      </a-card>
      
      <!-- <SetRegionForExtendedModal url-prefix="debt-collect-leader" :active="modals.regions" v-if="modals.regions && currentDebtor" :type="currentDebtorTableType" :debtor="currentDebtor" @submit="handleRegions" @cancel="hideRegionsModal" /> -->
    </a-layout>
  </template>

  <script setup>

    import { ref, onBeforeMount, reactive } from 'vue'
    import { useRoute, useRouter } from 'vue-router'
    import apiRequest from '../../../../utils/apiRequest'
    // import SetRegionForExtendedModal from '../../../../components/Debtor/SetRegionForExtendedModal'
    import dayjs from "dayjs";

    const route = useRoute()
    const router = useRouter()
    const loading = ref(false)
    const currentDebtor = ref(null)
    const modals = reactive({
        regions: false,
    })

    const loadCollectorData = async () => {
      loading.value = true
      try {
        const { data } = await apiRequest.get(`/v3/debt-collect-leader/collectors/${route.params.collectorId}`)
        collector.value = data
        loading.value = false
      } catch (e) {
        console.log(e)
      }
    }

    const collector = ref({})
    const tableColumns = [
      {
        dataIndex: 'id',
        key: 'id',
        title: 'ID',
        width: 50,
        fixed: true,
      },
      {
        dataIndex: 'full_name',
        key: 'full_name',
        width: 150,
        title: 'Ф.И.О',
      },
      {
        dataIndex: 'region',
        key: 'region',
        width: 150,
        customRender:({text, record, index})=> `${record.region?.name}, ${record.district?.name}`,
        title: 'Район',
      },
      {
        dataIndex: 'expired_days',
        key: 'expired_days',
        width: 100,
        title: 'Просрочено',
        customRender:({text, record, index})=> `${text} дн`,
      },
      {
        dataIndex: 'debt_collect_sum',
        key: 'debt_collect_sum',
        width: 150,
        title: 'Долг, сум',
      },
      {
        dataIndex: 'processed_at',
        key: 'processed_at',
        width: 150,
        title: 'Обработан',
        customRender:({text, record, index})=> {
          if (!text || text == false || text == 'false') return `НЕТ`;
          return `${dayjs(text).format('DD.MM.YYYY')}`;
        },
      },
    ];

    const columns = ref(tableColumns);
    const debtorsData = reactive({
      data: [],
      loading: false,
      search: '',
      pagination: {
        current: 1,
        total: 1,
        pageSize: 15,
        showSizeChanger:false,
        hidOnSinglePage:true,
        showQuickJumper:true
      }
    })


    const loadDebtors = async () => {
      debtorsData.loading = true
      try {
        const { data } = await apiRequest.get(`/v3/debt-collect-leader/collectors/${route.params.collectorId}/debtors`, {params: {
          search: debtorsData.search,
          page: debtorsData.pagination.current
        }})
        debtorsData.data = data.data
        debtorsData.pagination.current = data.current_page
        debtorsData.pagination.total = data.total
        debtorsData.pagination.pageSize = data.per_page
        debtorsData.loading = false
      } catch (e) {
        debtorsData.loading = false
        debtorsData.data = []
        console.log(e)
      }
    }

    const handlePagination = (paganation) => {
      debtorsData.pagination.current = paganation.current
      loadDebtors()
    }

    const goBack = () => {
      window.history.length > 0 ? router.back() : router.push({ name: 'debt-collect-leader-collectors' })
    }

    // const showRegionsModal=(debtor)=>{
    //   currentDebtor.value = debtor
    //   modals.regions = true
    // }
    // const hideRegionsModal=(debtor)=>{
    //   currentDebtor.value = null
    // }
    // const handleRegions = async () => {
    //   loadDebtors()
    //   modals.regions = false
    // }

    onBeforeMount(()=> {
      loadCollectorData()
      loadDebtors()
      
    })
  </script>
