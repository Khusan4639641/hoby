<template>
  <a-layout class="page dashboard-page">
    <a-page-header title="Отправленные письма">
      <template #extra>
        <a-space>
          <a-select
            v-model:value="senders"
            mode="multiple"
            :field-names="{ label:'sender_fio', value: 'sender_id' }"
            style="width: 250px"
            :max-tag-count="1"
            :max-tag-text-length="13"
            placeholder="Выберите отправителей"
            :options="sendersList"
            :show-arrow="true"
            :loading="sendersListLoading"
            :disabled="sendersListLoading"
            :filter-option="searchSender"
            @change="reloadLetters"
          />
          <!-- <a-date-picker v-model:value="dateRangeModel" :disabled-date="monthsLimit" picker="date" /> -->
          <a-range-picker @change="reloadLetters" v-model:value="dateRangeModel" :placeholder="['С даты', 'По дате']" prefix-icon="с" :disabled-date="monthsLimit"  />
          <a-button :href="downloadLink" target="_blank" type="primary">Excel</a-button>
        </a-space>
      </template>
    </a-page-header>
    <a-table bordered :columns="columns" :loading="loading" :dataSource="letters" :pagination="pagination" @change="onChange">
      <template #bodyCell="{text, column, record }">
        <template v-if="column.key === 'debtor'">
          <router-link :to="{ name: 'debt-collect-leader-debtor', params: { debtorId: record.debtor_id | 0 } }">
            {{ record.debtor }}
          </router-link>
        </template>
        <template v-if="column.key === 'contract_id'">
          <router-link :to="{ name: 'debt-collect-leader-contract', params: { contractId: record.contract_id } }">
            {{ record.contract_id }}
          </router-link>
        </template>
        <template v-if="column.key === 'district'">
          <div v-if="record.region"> {{record.region.name}}, {{ record.district.name}}</div>
          <div v-else class="text-muted"> Район не привязан </div>
        </template>
      </template>
      <!-- <template #title>
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
      </template> -->
      <template #footer>
        <b>Общее кол-во писем:</b> {{ pagination.total }} шт <br>
        <b>Период:</b> {{dayjs(dateRangeModel[0]).locale("ru").format('MMM D, YYYY') }} - {{ dayjs(dateRangeModel[1]).locale("ru").format('MMM D, YYYY') }} <br>
        <b>Выбранные отправители:</b> {{ sendersSelected }}
        <br>
      </template>
    </a-table>
  </a-layout>
  <SetRegionModal :active="modals.regions" v-if="modals.regions && currentDebtor" :debtor="currentDebtor" @submit="handleRegions" @cancel="hideRegionsModal" />
</template>

<script setup>
import { ref, reactive, onBeforeMount, computed } from 'vue'
import apiRequest from '../../../../utils/apiRequest'
import SetRegionModal from '../../../../components/Debtor/SetRegionModal'
import localeData  from 'dayjs/plugin/localeData'
dayjs.extend(localeData)
import dayjs from "dayjs";
const modals = reactive({
  regions: false,
})
const columns = [
  {
    title: 'ID',
    dataIndex: 'sender_id',
    key: 'sender_id'
  },
  {
    title: 'Отправитель',
    dataIndex: 'sender',
    key: 'sender'
  },
  {
    title: 'Должник',
    dataIndex: 'debtor',
    key: 'debtor'
  },
  {
    title: 'Контракт',
    dataIndex: 'contract_id',
    key: 'contract_id'
  },
  {
    title: 'Куда отправлено',
    dataIndex: 'region',
    key: 'region',
    customRender:({text, record, index})=> {
      return `${record.region}, ${record.area}`;
    },
  },
  {
    dataIndex: 'created_at',
    key: 'created_at',
    width: 150,
    title: 'Создан',
    customRender:({text, record, index})=> {
      return `${dayjs(text).format('DD.MM.YYYY HH:MM:ss')}`;
    },
  },
]
// const searchModel = ref('')
const dateRangeModel = ref([dayjs().date(1), dayjs()])
const pagination = reactive({
  current: 1,
  total: 1,
  pageSize: 15,
  showSizeChanger:false,
  hidOnSinglePage:true,
  showQuickJumper: true,
})

const monthsLimit = (current) => {
  return current && current >= dayjs()
}
const searchSender = (inputValue, option) => {
  console.log(option, 'sfsf');
  return option.sender_fio.toLowerCase().includes(inputValue.toLowerCase())
}

const debounceTimeoutId = ref(null)
const currentDebtor = ref(null)
const letters = ref([])
const senders = ref([])
const sendersList = ref([])
const sendersListLoading = ref(false)
const lettersTotal = ref(0)
const loading = ref(false)

const sendersSelected = computed(()=>{
  if (!senders.value.length) return 'Все'
  let sendersNames = senders.value.map(sender_id => {
    const foundSender = sendersList.value.find(el => el.sender_id === sender_id);
    return foundSender.sender_fio
  });

  return sendersNames.join(', ')

})

const loadLetters = async () => {
  clearTimeout(debounceTimeoutId.value);

  debounceTimeoutId.value = setTimeout(async () => {

    loading.value = true
    // let searchValue = searchModel.value
    let params = {page: pagination.current, senders: senders.value}

    // if (searchValue.replace(/\s/g, '').length) params.search = searchValue

    if(dateRangeModel.value?.length) {
      params.date_from = dateRangeModel.value[0].format('YYYY-MM-DD')
      params.date_to = dateRangeModel.value[1].format('YYYY-MM-DD')
    }
    try {
      const { data } = await apiRequest.get('/v3/debt-collect-leader/analytic/letters', {params})
      letters.value = data.data
      pagination.current = data.meta.current_page
      pagination.total = data.meta.total
      pagination.pageSize = data.meta.per_page
      loading.value = false
    } catch (e) {
      loading.value = false
      console.log(e)
    }

  }, 500);
}

const downloadLink = computed(() => {
  const baseLink = '/api/v3/debt-collect-leader/analytic/letters/export'
  const params = {
    api_token: window.globalApiToken
  }

  if(dateRangeModel.value?.length) {
    params.date_from = dateRangeModel.value[0].format('YYYY-MM-DD')
    params.date_to = dateRangeModel.value[1].format('YYYY-MM-DD')
  }
  const paramsURL = new URLSearchParams(params)
  senders.value.forEach((senderId) => {
    paramsURL.append('senders[]', senderId)
  })
  const paramsURI = paramsURL.toString()

  return `${baseLink}?${paramsURI}`
})
const loadLetterSenders = async () => {
  sendersListLoading.value = true
  try {
    const { data } = await apiRequest.get('/v3/debt-collect-leader/analytic/letter-senders')
    sendersList.value = data.data

    sendersListLoading.value = false
  } catch (e) {
    sendersListLoading.value = false
    console.log(e)
  }
}

onBeforeMount(async ()=>{
  await loadLetterSenders()
  await loadLetters()
})

const reloadLetters = () => {
  pagination.current = 1
  loadLetters()
}

const onChange = (newPagination) => {
  pagination.current = newPagination.current
  loadLetters()
}
const handleSearch = (value) => {
  pagination.current = 1
  loadLetters(value)
}
const showRegionsModal=(debtor)=>{
  currentDebtor.value = debtor
  modals.regions = true
}
const hideRegionsModal=(debtor)=>{
  currentDebtor.value = null
}
const handleRegions = async () => {
  loadLetters()
  modals.regions = false
}

</script>
<style>
.ant-select-selection-item-remove{
  display: inline-flex !important;
  align-items: center;
}
</style>

