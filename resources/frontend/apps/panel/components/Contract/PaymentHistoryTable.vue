<template>
    <a-table :loading="loading" :dataSource="paymentHistoryList" :columns="paymentHistoryColumns" />
</template>
<script setup>
import apiRequest from '../../utils/apiRequest'
import { ref, onBeforeMount} from 'vue'
const props = defineProps(['payload'])
const loading = ref(false)

const paymentHistoryList = ref([])
const paymentHistoryColumns = [
        {
        title: 'Сумма',
        dataIndex: 'amount',
        key: 'amount',
        width: 100,
        fixed: true,
        customRender:({text, record, index})=> text.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ")+' сум',
        },
        {
        title: 'Платежная система',
        dataIndex: 'payment_system',
        key: 'payment_system',
        width: 150
        },
        {
        title: 'Статус',
        dataIndex: 'status',
        key: 'status',
        width: 100,
        customRender:({text, record, index})=> Number(text) ? 'Подтвержден': ''
        },
        {
        title: 'Дата оплаты',
        dataIndex: 'created_at',
        key: 'created_at',
        width: 100
        }
    ]
    const loadPaymentHistory = async () => {
        loading.value = true
        try {
            const { data } = await apiRequest.post(`/v1/recovery/collectors/history-payment`, props.payload)
            paymentHistoryList.value = data
            loading.value = false
        } catch (e) {
            console.error(e)
            loading.value = false

        }
    }
    onBeforeMount(async()=>{
        await loadPaymentHistory()
    })

</script>