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
            <a-card-meta title="Регионы">
                    <template #description>
                        <template v-if="collector.districts.length > 0">
                        <p v-for="district in collector.districts" :key="district.region_id">
                            {{ district.region_name }} {{ district.name }}
                            <br>
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
                            <a-col :span="11">
                                <a-table
                                    bordered
                                    :loading="CDTData.loading"
                                    :row-key="(record)=>{return record.id}"
                                    :row-selection="{selectedRowKeys: CDTData.selectedRowKeys, onChange:(SRK, SR)=> onTablesSelectChange(SRK, SR, 'left')}"
                                    :scroll="{  y: '400px' }"
                                    :columns="columns"
                                    :data-source="collectorsDebtors"
                                    size="small"
                                    :pagination="CDTData.pagination"
                                    @change="handleCDTPagination"
                                >
                                <template #title>
                                  <div class="d-flex align-items-center justify-content-between">
                                    <p class="m-0">Привязанные должники <b>({{CDTData.pagination.total}}/{{collector?.debtor_limit || defaultDebtorLimit}})</b></p>
                                    <span v-if="CDTData.selectedRowKeys.length">Выбрано <b>{{CDTData.selectedRowKeys.length}}</b> из <b>{{CDTData.pagination.total}}</b></span>
                                  </div>
                                    
                                    <a-divider/>
                                    <a-input-search
                                        ref="searchInput"
                                        placeholder="Поиск должников"
                                        enter-button="Поиск"
                                        v-model:value="CDTData.searchModel"
                                        style="width: 100%;"
                                        :allow-clear="true"
                                        @keyup.enter="e => handleSearch(e.target.value, 'left')"
                                        @search="(val, e) => handleSearch(val, 'left')"
                                    />
                                </template>
                                <template #bodyCell="{ text, column, record }">
                                  <template v-if="column.key === 'full_name'">
                                    <router-link :to="{ name: 'debt-collect-curator-debtor', params: { debtorId: record.id } }">
                                      {{ record.full_name }}
                                    </router-link>
                                  </template>
                                  <template v-if="column.key === 'address'">
                                    {{ record.address_registration?.address }}
                                  </template>
                                </template>
                                </a-table>
                            </a-col>
                            <a-col span="auto" >
                                <a-space align="center" direction="vertical">
                                  <a-tooltip
                                    :title="CDTData.selectedRowKeys.length > 0 ? `Отвязать ${CDTData.selectedRowKeys.length} должников` : ''"
                                    color="primary"
                                  >
                                    <a-button  @click="transfer('toRight')" :disabled="CDTData.selectedRowKeys.length === 0 || ADTData.loading" type="primary" size="sm">
                                        <template #icon>
                                          <RightOutlined />
                                        </template>
                                    </a-button>
                                  </a-tooltip>

                                  <a-tooltip
                                    v-if="!hideAttachButton"
                                    :title="ADTData.selectedRowKeys.length > 0 ? `Привязать ${ADTData.selectedRowKeys.length} должников` : ''" 
                                    color="primary"
                                  >
                                    <a-button  @click="transfer('toLeft')" :disabled="ADTData.selectedRowKeys.length === 0 || CDTData.loading" type="primary" size="sm">
                                        <template #icon>
                                          <LeftOutlined />
                                        </template>
                                    </a-button>
                                  </a-tooltip>

                                </a-space>
                            </a-col>
                            <!-- :row-selection="isDebtorLimitExceeded ? false : {selectedRowKeys: ADTData.selectedRowKeys, onChange:(SRK, SR)=> onTablesSelectChange(SRK, SR, 'right')}" -->

                            <a-col :span="11">
                                <a-table
                                    bordered
                                    :loading="ADTData.loading"
                                    :row-key="(record)=>{return record.id}"
                                    :row-selection="isDebtorLimitExceeded ? false : {
                                      hideSelectAll,
                                      selectedRowKeys: ADTData.selectedRowKeys,
                                      onChange:(SRK, SR)=> onTablesSelectChange(SRK, SR, 'right'),
                                      getCheckboxProps: (record) => watchCheckboxProps(record),
                                    }"
                                    :scroll="{  y: '400px' }"
                                    :columns="columns"
                                    :data-source="allDebtors"
                                    size="small"
                                    :pagination="ADTData.pagination"
                                    @change="handleADTPagination"
                                >
                                    <template #title>
                                        <div class="d-flex align-items-center justify-content-between">
                                          <p class="m-0">
                                            <span v-if="ADTData.selectedRowKeys.length">Выбрано <b>{{ADTData.selectedRowKeys.length}}</b> из <b>{{ADTData.pagination.total}}</b></span>
                                            <span v-else>В ожидании <b>({{ADTData.pagination.total}})</b></span>
                                            <a-tag class="ml-2" v-if="isDebtorLimitExceeded || hideAttachButton || isSelectionLimitReached" color="red">

                                              <span v-if="isSelectionLimitReached && !isDebtorLimitExceeded">Достигнут лимит привязки</span>
                                              <span v-else>Превышен лимит привязки</span>
                                            </a-tag>
                                          </p>
                                          <a-select
                                              ref="delayFilterSelect"
                                              v-model:value="ADTData.delayFilterModel"
                                              style="width: 120px"
                                              @change="delayFilterChange"
                                          >
                                            <a-select-option  :value="61">61+</a-select-option>
                                            <a-select-option selected :value="90">90+</a-select-option>
                                          </a-select>
                                        </div>

                                        <a-divider/>
                                        <a-input-search
                                            ref="searchInput"
                                            placeholder="Поиск должников"
                                            enter-button="Поиск"
                                            v-model:value="ADTData.searchModel"
                                            style="width: 100%;"
                                            :allow-clear="true"
                                            @keyup.enter="e => handleSearch(e.target.value, 'right')"
                                            @search="(val, e) => handleSearch(val, 'right')"
                                        />
                                    </template>
                                    <template #bodyCell="{ text, column, record }">
                                      <template v-if="column.key === 'full_name'">
                                        <router-link :to="{ name: 'debt-collect-curator-debtor', params: { debtorId: record.id } }">
                                          {{ record.full_name }}
                                        </router-link>
                                      </template>
                                      <template v-if="column.key === 'address'">
                                        {{ record.address_registration?.address }}
                                      </template>
                                    </template>
                                </a-table>
                            </a-col>
                        </a-row>
                    </template>
            </a-card-meta>
        </a-card-grid>
      </a-card>
    </a-layout>
  </template>

  <script setup>

    import { ref, onBeforeMount, reactive, computed, compile } from 'vue'
    import { useRoute, useRouter } from 'vue-router'
    import apiRequest from '../../../../utils/apiRequest'
    import dayjs from "dayjs";

    const defaultDebtorLimit = 150
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
            title: 'Адрес',
            dataIndex: 'address_registration.address',
            key: 'address',
            width: 300
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
    const route = useRoute()
    const router = useRouter()
    const loading = ref(false)
    const collector = ref({})

    const allDebtors  = ref([]);
    const collectorsDebtors  = ref([]);

    // CDT - collectorsDebtorsTable
    const CDTData = reactive({
        selectedRowKeys: [],
        loading: [],
        searchModel: "",
        pagination: {
            current: 1,
            total: 0,
            pageSize:15,
            showSizeChanger:false,
            hidOnSinglePage:true,
            showQuickJumper:true
        }
    })
    // ADT - allDebtorsTable
    const ADTData = reactive({
        selectedRowKeys: [],
        loading: [],
        searchModel: "",
        delayFilterModel: 90,
        pagination: {
            current: 1,
            total: 1,
            pageSize: 15,
            showSizeChanger:false,
            hidOnSinglePage:true,
            showQuickJumper:true
        }
    })

    const collector_debtor_limit = computed(()=> {
      let debtor_limit = defaultDebtorLimit;
      if (collector?.debtor_limit || collector?.debtor_limit == 0)
          debtor_limit = collector?.debtor_limit
      return debtor_limit
    })

    const hideSelectAll = computed(()=> ADTData.pagination.pageSize > (collector_debtor_limit.value - CDTData.pagination.total))
    const hideAttachButton = computed(()=>ADTData.selectedRowKeys.length > (collector_debtor_limit.value - CDTData.pagination.total) )
    const isSelectionLimitReached = computed(()=>ADTData.selectedRowKeys.length == (collector_debtor_limit.value - CDTData.pagination.total))
    const isDebtorLimitExceeded = computed(()=>CDTData.pagination.total >= collector_debtor_limit.value)
    const columns = ref(tableColumns);

    const loadCollectorsDebtors = async () => {
        CDTData.loading = true
        try {
            const { data } = await apiRequest.get(`/v3/debt-collect-curator/collectors/${route.params.collectorId}/debtors`, {params: {
              search: CDTData.searchModel,
              page: CDTData.pagination.current
            }})
            collectorsDebtors.value = data.data
            CDTData.pagination.current = data.current_page
            CDTData.pagination.total = data.total
            CDTData.pagination.pageSize = data.per_page
            CDTData.loading = false
        } catch (e) {
            CDTData.loading = false
            collectorsDebtors.value = []
            console.log(e)
        }
    }
    const loadAllDebtors = async () => {
        ADTData.loading = true
        try {
            const { data } = await apiRequest.get(`/v3/debt-collect-curator/collectors/${route.params.collectorId}/potential-debtors`, {params: {
              search: ADTData.searchModel,
              page: ADTData.pagination.current,
              delays: ADTData.delayFilterModel,
            }})
            allDebtors.value = data.data
            ADTData.pagination.current = data.current_page
            ADTData.pagination.total = data.total
            ADTData.pagination.pageSize = data.per_page
            ADTData.loading = false
        } catch (e) {
            ADTData.loading = false
            allDebtors.value = []
            console.log(e)
        }
    }
    const attachDebtors = async (debtors) => {
      ADTData.loading = true
      CDTData.loading = true
      try {
        const { data } = await apiRequest.post(`/v3/debt-collect-curator/collectors/${route.params.collectorId}/debtors`, {debtor_id: debtors} )
        ADTData.selectedRowKeys = []
        await loadDebtorsData()
        // ADTData.loading = false
        // CDTData.loading = false
      } catch (e) {
        ADTData.loading = false
        CDTData.loading = false
        console.log(e)
      }
    }
    const detachDebtors = async (debtors) => {
      ADTData.loading = true
      CDTData.loading = true
      try {
        const { data } = await apiRequest.delete(`/v3/debt-collect-curator/collectors/${route.params.collectorId}/debtors`,{
          params: {
            debtor_id: debtors
          }
        })
        CDTData.selectedRowKeys = []
        await loadDebtorsData()
        // ADTData.loading = false
        // CDTData.loading = false
      }catch (e) {
        ADTData.loading = false
        CDTData.loading = false
        console.log(e)
      }
    }
    const loadCollectorData = async () => {
        loading.value = true
        try {
            const { data } = await apiRequest.get(`/v3/debt-collect-curator/collectors/${route.params.collectorId}`)
            collector.value = data
            loading.value = false
        } catch (e) {
            console.log(e)
        }
    }
    const loadDebtorsData = () => {
      loadAllDebtors()
      loadCollectorsDebtors()
    }

    const goBack = () => {
      window.history.length > 0 ? router.back() : router.push({ name: 'debt-collect-curator-collectors' })
    }

    const handleCDTPagination = (paganation) => {
      CDTData.pagination.current = paganation.current
      loadCollectorsDebtors()
    }

    const handleADTPagination = (paganation) => {
      ADTData.pagination.current = paganation.current
      loadAllDebtors()
    }

    onBeforeMount(()=> {
        loadCollectorData()
        loadAllDebtors()
        loadCollectorsDebtors()
    })
    
    const handleSearch = (val, type) => {
        if (type === 'left') loadCollectorsDebtors()
        else loadAllDebtors()
    }
    const delayFilterChange = ()=> {
      loadAllDebtors()
    }
    const onTablesSelectChange = (selectedRowKeys, selectedRows, table) => {
        // left - таблица привязанных должников
        // right - таблица должников в ожидании
        if (table === 'left') {
            CDTData.selectedRowKeys = selectedRowKeys;
        }else {
            ADTData.selectedRowKeys = selectedRowKeys;
        }
    };
    const watchCheckboxProps = (record) => {
        let calc = ADTData.selectedRowKeys.length >= (collector_debtor_limit.value - CDTData.pagination.total)
        return {

          disabled: calc && !ADTData.selectedRowKeys.includes(record.id),
          name: record.name,
        }
    };

    const transfer = (direction)=> {
        if (!direction) return
        // direction = toRight --- с таблицы привязанных должников в таблицу должников в ожидании
        // direction = toLeft --- с таблицы должников в ожидании в таблицу привязанных должников
        if (direction === 'toLeft') attachDebtors(ADTData.selectedRowKeys)
        else detachDebtors(CDTData.selectedRowKeys)
    }
  </script>
