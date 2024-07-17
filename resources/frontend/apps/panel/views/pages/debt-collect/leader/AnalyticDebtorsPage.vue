<template>
  <a-layout class="page dashboard-page">
    <a-page-header title="Должники" sub-title="Выберите должника">
      <template #extra>
        <a-date-picker v-model:value="currentMonth" :disabled-date="monthsLimit" picker="month" />
      </template>
    </a-page-header>
    <a-table bordered :columns="columns" :loading="loading" :dataSource="debtors" :pagination="pagination" @change="onChange">
      <template #bodyCell="{text, column, record }">
        <template v-if="column.key === 'name'">
          <router-link :to="{ name: 'debt-collect-leader-debtor', params: { debtorId: record.id } }">
            {{ record.full_name }}
          </router-link>
        </template>
        <template v-if="column.key === 'district'">
          <div v-if="record.region"> {{record.region.name}}, {{ record.district.name}}</div>
          <div v-else class="text-muted"> Район не привязан </div>
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
    title: 'Район',
    dataIndex: 'district',
    key: 'district'
  },
  {
    title: 'Просроченные / Все контракты',
    dataIndex: 'debt_collect_contracts',
    customRender: ({text, record, index})=> `${record.expired_contracts_count} / ${record.contracts_count}`,
    key: 'contracts'
  },
  {
    title: 'Выплаченная / Актуальная задолженность',
    dataIndex: 'debt_collect_sums',
    customRender: ({text, record, index})=> `${record.debt_collected_sum} / ${record.debt_collect_sum} сум`,
    key: 'debt_collect_sums'
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
    const { data } = await apiRequest.get('/v3/debt-collect-leader/analytic/debtors', {params})
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
