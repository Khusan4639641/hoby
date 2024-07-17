<template>
  <a-layout class="page dashboard-page">
    <a-page-header title="Коллекторы" sub-title="Выберите коллектора">
      <template #extra>
        <a-date-picker v-model:value="currentMonth" :disabled-date="monthsLimit" picker="month" />
      </template>
    </a-page-header>
    <a-table bordered :columns="columns" :loading="loading" :dataSource="debtors" :pagination="pagination" @change="onChange">
      <template #bodyCell="{text, column, record }">
        <template v-if="column.key === 'name'">
          <router-link :to="{ name: 'debt-collect-curator-collector', params: { collectorId: record.id } }">
            {{ record.full_name }}
          </router-link>
        </template>
        <template v-if="column.key === 'districts'">
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
        <template v-if="column.key === 'actions'">
          Локация: {{ record.action_location_count }}
          <br>
          Дата: {{ record.action_date_count }}
          <br>
          Фото: {{ record.action_photo_count }}
          <br>
          Комментарий: {{ record.action_text_count }}
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
  <SetRegionModal :active="modals.regions" v-if="modals.regions && currentDebtor" :debtor="currentDebtor" @submit="handleRegions" @cancel="hideRegionsModal" />
</template>

<script setup>
import { ref, reactive, onBeforeMount } from 'vue'
import apiRequest from '../../../../utils/apiRequest'
import SetRegionModal from '../../../../components/Debtor/SetRegionModal'
import dayjs from "dayjs";
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
    title: 'Районы',
    key: 'districts'
  },
  {
    title: 'Обработанные / Все должники (контракты)',
    customRender: ({text, record, index})=> `${record.processed_debtors_count} (${record.processed_contracts_count}) / ${record.debtors_count} (${record.contracts_count})`,
    key: 'contracts'
  },
  {
    title: 'Действия',
    key: 'actions'
  },
  {
    title: 'Привлеченная / Премиальная суммы',
    customRender: ({text, record, index})=> `${record.debt_collected_sum} / ${record.remunerations} сум`,
    key: 'debt_collect_sums'
  },
]
const searchModel = ref('')
const currentMonth = ref(dayjs())
const pagination = reactive({
  current: 1,
  total: 1,
  pageSize: 15,
  showSizeChanger:false,
  hidOnSinglePage:true,
  showQuickJumper:true
})

const monthsLimit = (current) => {
  return current && (
      current < dayjs().subtract(1, 'month').endOf('month')
      ||
      current >= dayjs().add(1, 'month').startOf('month')
  )
}

const currentDebtor = ref(null)
const debtors = ref([])
const loading = ref(false)
const loadDebtors = async () => {
  loading.value = true
  let searchValue = searchModel.value
  let params = {page: pagination.current}
  if (searchValue.replace(/\s/g, '').length) params.search = searchValue
  if(currentMonth.value === null) {
    params.month = dayjs().format('YYYY-MM')
  }  else {
    params.month = currentMonth.value.format('YYYY-MM')
  }
  try {
    const { data } = await apiRequest.get('/v3/debt-collect-curator/analytic/collectors', {params})
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
const showRegionsModal=(debtor)=>{
  currentDebtor.value = debtor
  modals.regions = true
}
const hideRegionsModal=(debtor)=>{
  currentDebtor.value = null
}
const handleRegions = async () => {
  loadDebtors()
  modals.regions = false
}

</script>
