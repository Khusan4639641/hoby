<template>
  <a-layout class="page contracts-page">
    <a-page-header title="Должники" sub-title="Должники района" @back="() => router.push({ name: 'dashboard' })">
    </a-page-header>
    <a-table sticky :scroll="{ x: 1000 }" :loading="loading" :columns="columns" :data-source="contracts" :pagination="pagination" @change="onChange">
      <template #bodyCell="{text, record, index, column}">
        <template v-if="column.key === 'full_name'">
          <router-link :to="{ name: 'debtor', params: { debtor: record.id } }">
            {{ record.full_name}}
          </router-link>
        </template>
        <template v-if="column.key === 'address'">
          {{ record.address_registration.address }}
        </template>
      </template>
    </a-table>
  </a-layout>
</template>

<script setup>
import { ref, reactive, onBeforeMount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import apiRequest from '../../utils/apiRequest'
import dayjs from "dayjs";

const router = useRouter()
const route = useRoute()
const loading = ref(false)
const columns = [
  {
    title: 'ID',
    dataIndex: 'id',
    key: 'id',
    width: 50,
    fixed: true
  },
  {
    title: 'Ф.И.О',
    dataIndex: 'full_name',
    key: 'full_name',
    width: 150
  },
  {
    title: 'Адрес',
    dataIndex: 'address_registration.address',
    key: 'address',
    width: 300
  },
  {
    title: 'Сумма долга (сум)',
    dataIndex: 'debt_collect_sum',
    key: 'debt_collect_sum',
    width: 100
  },
  {
    title: 'Всего контрактов (шт)',
    dataIndex: 'contracts_count',
    key: 'contracts_count',
    width: 50
  },
  {
      dataIndex: 'processed_at',
      key: 'processed_at',
      width: 80,
      title: 'Обработан',
      customRender:({text, record, index})=> {
      if (!text || text == false || text == 'false') return `НЕТ`;
      return `${dayjs(text).format('DD.MM.YYYY')}`;
      },
  },
]

const contracts = ref([])
const pagination = reactive({
  current: 1,
  total: 1,
  pageSize: 15,
  showSizeChanger:false,
  hidOnSinglePage:true,
  showQuickJumper:true
})

const loadContacts = async () => {
  loading.value = true
  try {
    const { data } = await apiRequest.get('/v3/debt-collector/debtors', {
      params: {
        district_id: route.query.district_id,
        page: pagination.current
      }
    })
    contracts.value = data.data
    pagination.current = data.current_page
    pagination.total = data.total
    pagination.pageSize = data.per_page
    loading.value = false
  } catch (e) {
    loading.value = false
    console.log(e)
  }
}

onBeforeMount(loadContacts)

const onChange = (newPagination) => {
  pagination.current = newPagination.current
  loadContacts()
}
</script>
