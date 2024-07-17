<template>
    <a-table :scroll="{ x: 1000 }" :pagination="schedulePagination"  :loading="loading" size="small" :dataSource="scheduleList" :columns="scheduleColumns" >
        <template #bodyCell="{ column, record }">
          <template v-if="column.key === 'paid_at'">
            <span>
              <a-tag :color="statusFormat(record).color"> {{ statusFormat(record).text }}</a-tag>
            </span>
          </template>
        </template>
      </a-table>
</template>
<script setup>
import apiRequest from '../../utils/apiRequest'
import { ref, onBeforeMount, reactive } from 'vue'
import customParseFormat from 'dayjs/plugin/customParseFormat'
import dayjs from 'dayjs'
const props = defineProps(['contractId'])
const loading = ref(false)
dayjs.extend(customParseFormat)
const schedulePagination = reactive({ pageSize: 50 })
const scheduleList = ref([])
const scheduleColumns = [
    {
        title: '№',
        dataIndex: 'id',
        key: 'id',
        width: 25,
        fixed: true,
        customRender: ({ text, record, index }) => index + 1,
    },
    {
        title: 'Дата',
        dataIndex: 'payment_date',
        key: 'payment_date',
    },
    {
        title: 'Платеж',
        dataIndex: 'total',
        key: 'total',
        customRender: ({ text, record, index }) => text.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ") + ' сум',
    },
    {
        title: 'Остаток',
        dataIndex: 'balance',
        key: 'balance',
        customRender: ({ text, record, index }) => text.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ") + ' сум',
    },
    {
        title: 'Статус',
        dataIndex: 'paid_at',
        key: 'paid_at',
        customRender: ({ text, record, index }) => text ? `Оплачено ${dayjs(text).format('DD.MM.YYYY в HH:mm:ss')}` : 'Не оплачено',
    }
]
const statusFormat = (data, payedDate)=>{
    data.payment_date, data.paid_at
  let now = dayjs(new Date())
  let paymentDate = dayjs(data.payment_date, 'DD.MM.YYYY HH:mm:ss')
  
  // Если долг не погашен и дата оплаты платежа прошла  (diff = now - paymentDate)
  if (data.status == 0 && now.diff(paymentDate, 'day', true) > 0) return {color: 'red', text: 'Просрочено'}
  if (data.paid_at && data.status == 1) return {color: 'green', text: 'Оплачено в '+ dayjs(data.paid_at, 'YYYY-MM-DD HH:mm:ss').format('DD.MM.YYYY HH:mm:ss')} 
  if (data.paid_at && data.status == 0) return {color: 'blue', text: 'Частичная оплата в '+ dayjs(data.paid_at, 'YYYY-MM-DD HH:mm:ss').format('DD.MM.YYYY HH:mm:ss')} 

  return { color: 'default', text: 'Ожидание оплаты' } 
}
const loadSchedule = async () => {
    loading.value = true
    try {
        const { data } = await apiRequest.get(`/v3/debt-collector/contracts/${props.contractId}/schedules`)
        scheduleList.value = data
        loading.value = false
    } catch (e) {
        console.error(e)
    }
}
onBeforeMount(loadSchedule)

</script>
