<template>
  <a-layout class="page contract-page">
    <a-page-header
      :title="`Контракт №${contract.id || ''}`"
      @back="() => router.push({ name: 'debt-collect-curator-debtor', params: { debtorId: contract.debtor.id } })"
      >
    </a-page-header>
    <a-card :loading="loading">
      <a-card-grid :hoverable="false" style="width: 100%">
        <a-card-meta title="Личные данные должника">
          <template #description>
            <p>
              <b>Ф.И.О.:</b> {{ contract.debtor.full_name }}
              <br>
              <b>Телефон:</b> {{ contract.debtor.phone }}
            </p>
          </template>
        </a-card-meta>
      </a-card-grid>
      <a-card-grid :hoverable="false" style="width: 100%">
        <a-card-meta title="Заказ">
          <template #description>
            <p v-for="product in contract.products" :key="product.name">
              <b>Товар:</b> {{ product.name }}
              <br>
              <b>Цена:</b> {{ product.price }} сум
            </p>
          </template>
        </a-card-meta>
      </a-card-grid>
      <a-card-grid :hoverable="false" style="width: 100%">
        <a-card-meta title="История платежей">
          <template #description>
            <PaymentHistoryTable :payload="{ contract_id: route.params.contractId}" />
          </template>
        </a-card-meta>
      </a-card-grid>
      <a-card-grid style="width: 100%">
        <a-card-meta title="Компания">
          <template #description>
            <p>
              <b>Название:</b> {{ contract.company.name }}
              <br>
              <b>Номер:</b> {{ contract.company.phone }}
            </p>
          </template>
        </a-card-meta>
      </a-card-grid>
      <a-card-grid :hoverable="false" style="width: 100%; padding: 0">
        <a-collapse v-model="scheduleCollapse" ghost>
              <a-collapse-panel key="1" header="График платежей" class="ant-card-meta-title" style="padding: 12px 8px;">
                <ContractSchedulesTable :contractId="route.params.contractId" />
              </a-collapse-panel>
        </a-collapse>
      </a-card-grid>
      <a-card-grid style="width: 100%">
        <a-card-meta title="Задолжность по контракту">
          <template #description>
            <p>
              <b>Сумма:</b> {{ contract.debt_sum }} сум
            </p>
          </template>
        </a-card-meta>
      </a-card-grid>
      <a-card-grid class="contract__document" v-for="file in files" :key="file.type">
        <a class="contract__document-link" :class="{ disabled: contract.files[file.type] === null }"
          :href="contract.files[file.type]" target="_blank">
          {{ file.name }}
        </a>
      </a-card-grid>
    </a-card>
  </a-layout>
</template>

<script setup>
import 'ant-design-vue/lib/notification/style'

import { ref, reactive, onBeforeMount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import apiRequest from '../../../../utils/apiRequest'
import PaymentHistoryTable from '../../../../components/Contract/PaymentHistoryTable'
import ContractSchedulesTable from '../../../../components/Contract/ContractSchedulesTable'

const route = useRoute()
const router = useRouter()

const scheduleCollapse = ref(false)

const loading = ref(false)
const contract = ref({})


const files = ref([
  { name: 'Акт', type: 'act' },
  { name: 'Фото с товаром', type: 'selfie_with_product' },
])

const modals = reactive({
  comment: false,
  date: false,
  location: false,
  photo: false
})

const loadContact = async () => {
  loading.value = true
  try {
    const { data } = await apiRequest.get(`/v3/debt-collect-curator/contracts/${route.params.contractId}`)
    contract.value = data
    loading.value = false
  } catch (e) {
    console.error(e)
  }
}

onBeforeMount(loadContact)
</script>

<style lang="scss" scoped>
.contract {
  &__document {
    width: 50%;
    text-align: center;
    padding: 0;

    &-link {
      display: flex;
      width: 100%;
      height: 100%;
      align-items: center;
      justify-content: center;
      padding: 24px;

      &.disabled {
        cursor: not-allowed;
        color: gray;
        opacity: 0.5;
      }
    }
  }
}
</style>
