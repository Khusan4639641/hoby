<template>
  <a-layout class="page dashboard-page">
    <a-page-header title="Районы" sub-title="Выберите район">
      <template #extra>
        <router-link :to="{ name: 'logout' }">
          <a-button danger>Выйти</a-button>
        </router-link>
      </template>
    </a-page-header>
    <a-table :columns="columns" :data-source="localRegions">
      <template #bodyCell="{ column, record }">
        <template v-if="column.key === 'name'">
          <router-link :to="{ name: 'contracts', query: { local_region: record.local_region } }">
            {{ record.local_region_name }}
          </router-link>
        </template>
      </template>
    </a-table>
  </a-layout>
</template>

<script setup>
import { ref, reactive, onBeforeMount } from 'vue'
import apiRequest from '../../utils/apiRequest'

const columns = [
  {
    title: 'ID',
    dataIndex: 'local_region',
    key: 'id'
  },
  {
    title: 'Название',
    dataIndex: 'local_region_name',
    key: 'name'
  },
]

const localRegions = ref([])

onBeforeMount(async () => {
  try {
    const { data } = await apiRequest.get('/v1/collector/local-regions')
    localRegions.value = data
  } catch (e) {
    console.log(e)
  }
})
</script>