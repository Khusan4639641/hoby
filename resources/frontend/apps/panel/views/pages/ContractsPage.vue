<template>
  <a-layout class="page contracts-page">
    <a-page-header title="Контракты" sub-title="Контракты района" @back="() => router.push({ name: 'dashboard' })">
      <template #extra>
        <router-link :to="{ name: 'logout' }">
          <a-button danger>Выйти</a-button>
        </router-link>
      </template>
    </a-page-header>
    <a-table :columns="columns" :data-source="contracts" :pagination="pagination" @change="onChange">
      <template #bodyCell="{ column, record }">
        <template v-if="column.key === 'name'">
          <router-link :to="{ name: 'contract', params: { contract: record.id } }">
            {{ record.id }}: {{ record.buyer.name }} {{ record.buyer.surname }} {{ record.buyer.patronymic }}
          </router-link>
        </template>
      </template>
    </a-table>
  </a-layout>
</template>

<script setup>
import { ref, reactive, onBeforeMount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import apiRequest from '../../utils/apiRequest'

const router = useRouter()
const route = useRoute()

const columns = [
  {
    title: 'Название',
    key: 'name'
  },
]

const contracts = ref([])
const pagination = reactive({
  current: 1,
  total: 1,
  pageSize: 15,
  showQuickJumper:true
})

const loadContacts = async () => {
  try {
    const { data } = await apiRequest.get('/v1/collector/contracts', {
      params: {
        local_region_id: route.query.local_region,
        page: pagination.current
      }
    })
    contracts.value = data.data
    pagination.current = data.current_page
    pagination.total = data.total
    pagination.pageSize = data.per_page
  } catch (e) {
    console.log(e)
  }
}

onBeforeMount(loadContacts)

const onChange = (newPagination) => {
  pagination.current = newPagination.current
  loadContacts()
}
</script>