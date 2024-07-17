<template>
  <a-layout class="page dashboard-page">
    <a-page-header title="Районы" sub-title="Выберите район">

    </a-page-header>
    <a-table sticky :scroll="{ x: 1000 }"  :loading="loading" :columns="columns" :data-source="districts">
      <template #bodyCell="{ column, record }">
        <template v-if="column.key === 'name'">
          <router-link :to="{ name: 'debtors', query: { district_id: record.id } }">
            {{record.region.name}}, {{ record.name }}
          </router-link>
        </template>
        <template v-else-if="column.key === 'action'">
          <a-button @click="dataExport(record)" type="primary" style="float:right;"><file-excel-outlined /> Экспорт</a-button>
        </template>
      </template>
    </a-table>
  </a-layout>
</template>

<script setup>
import { ref, onBeforeMount } from 'vue'
import apiRequest from '../../utils/apiRequest'
import { useAuthStore } from '../../stores/authStore';
const authStore = useAuthStore()
const columns = [
  {
    title: 'ID',
    dataIndex: 'id',
    key: 'id',
    width: 50,
    fixed: true
  },
  {
    title: 'Название',
    dataIndex: 'name',
    key: 'name',
    width: 200,
  },
  {
    title: 'Должники',
    dataIndex: 'debtors_count',
    customRender:({text, record, index})=> record.debtors.length,
    key: 'debtors_count',
    width: 80,
  },
  {
    title: '',
    dataIndex: 'action',
    customRender:({text, record, index})=> record.debtors.length,
    key: 'action',
    width: 80,
  },
]

const districts = ref([])
const loading = ref(false)

onBeforeMount(async () => {
  districts.value = []
  loading.value = true
  try {
    const { data } = await apiRequest.get('/v3/debt-collector/districts')
    districts.value = data
    loading.value = false
  } catch (e) {
    districts.value = []
    loading.value = false
    console.log(e)
  }
})
const currentFormattedDateTime = (()=> {
  let date = new Date();
  let h = date.getHours();
  let m = date.getMinutes();
  if (m < 10) m = "0" + m;
  let s = date.getSeconds();
  if (s < 10) s = "0" + s;
  let dd = date.getDate();
  let mm = date.getMonth();
  let yyyy = date.getFullYear();
  return `${dd}_${mm}_${yyyy}_${h}_${m}_${s}`;
})
const dataExport = (async (record)=> {
  loading.value = true
  try {
    const response = await apiRequest.get(`/v3/debt-collector/debtors/export_by_district?district_id=${record.id}&collector_id=${authStore?.user?.id}`, {responseType: 'blob'})
      let blob = new Blob([response.data], {type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'}),
      url = window.URL.createObjectURL(blob)
      let record_region = record.name.toLowerCase().replace(' ','_')
      let link = document.createElement('a');
      link.href = url;
      link.download = `${record_region}_${currentFormattedDateTime()}.xlsx`;
      document.body.appendChild(link);
      link.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(link);
      
    loading.value = false
  } catch (e) {
    loading.value = false
    console.error(e)
  }
}) 


</script>
