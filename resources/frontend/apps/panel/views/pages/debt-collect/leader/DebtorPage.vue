<template>
  <a-layout class="page debtor-page">
    <a-page-header
        :title="`Должник `"
        :sub-title="`${debtor.full_name || ''}`"
        @back="goBack"
    >
    </a-page-header>
    <a-card :loading="loading">
      <a-card-grid :hoverable="false" style="width: 100%">
        <a-card-meta title="Личные данные">
          <template #description>
            <p>
              <b>Ф.И.О:</b> {{ debtor.full_name }}
              <br>
              <b>Телефон:</b> {{ debtor.phone }}
              <br>
              <b>Паспорт:</b> {{ debtor.passport_number }}
              <br>
              <b>Прописка:</b> {{ debtor.registration_address }}
            </p>
          </template>
        </a-card-meta>
      </a-card-grid>

      <a-card-grid :hoverable="false" v-if="debtor.guarants" style="width: 100%">
        <a-card-meta title="Доверители">
          <template #description>
            <template v-if="debtor.guarants.length > 0">
              <p v-for="guarant in debtor.guarants" :key="guarant.phone">
                <b>Ф.И.О.:</b> {{ guarant.name }}
                <br>
                <b>Телефон:</b> {{ guarant.phone }}
              </p>
            </template>
            <template v-else>
              <p>
                <b>Доверители отсутствуют</b>
              </p>
            </template>
          </template>
        </a-card-meta>
      </a-card-grid>
      <a-card-grid :hoverable="false" style="width: 100%">
        <a-card-meta title="Общая задолжность">
          <template #description>
            <p>
              <b>Сумма:</b> {{ debtor.debt_collect_sum }} сум
            </p>
          </template>
        </a-card-meta>
      </a-card-grid>
      <a-card-grid :hoverable="false" style="width: 100%;padding: 24px 0 ;">
        <a-collapse v-model="paymentHistoryCollapse" ghost>
              <a-collapse-panel key="1" header="История платежей" class="ant-card-meta-title" style="padding: 0 8px;">
                <PaymentHistoryTable :payload="{ user_id: route.params.debtorId}" />
              </a-collapse-panel>
        </a-collapse>
      </a-card-grid>
      <a-card-grid :hoverable="false" style="width: 100%" >
        <a-card-meta :title="`Контракты должника`">
          <template #description>
            <a-card style="margin-top: 1rem;" :bordered="false">
              <a-card-grid
                  v-for="contract in debtorContracts"
                  :key="contract.id"
                  class="debtor_contract"
                  @click="router.push({ name: 'debt-collect-leader-contract', params: { contractId: contract.id } })"
              >
                <a-card-meta :title="`Контракт #${contract.id}`">
                  <template #description>
                    <p style="margin: 0;">
                      <b>Сумма долга:</b> {{ contract.debt_sum }}
                      <br>
                      <b>Просрочено:</b> {{ contract.expired_days }} дней
                      <br>
                      <b>Статус:</b> {{ getStatusName(contract.status) }}
                    </p>

                  </template>
                </a-card-meta>
              </a-card-grid>
            </a-card>
          </template>
        </a-card-meta>
      </a-card-grid>
      <a-card-grid class="debtor__document" v-for="file in files" :key="file.type">
        <a class="debtor__document-link" :class="{ disabled: debtor.files[file.type] === null }"
           :href="debtor.files[file.type]" target="_blank">
          {{ file.name }}
        </a>
      </a-card-grid>
    </a-card>
  </a-layout>
</template>

<script setup>
import 'ant-design-vue/lib/notification/style'

import { ref, onBeforeMount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import apiRequest from '../../../../utils/apiRequest'
import PaymentHistoryTable from '../../../../components/Contract/PaymentHistoryTable'

const route = useRoute()
const router = useRouter()

const loading = ref(false)
const paymentHistoryCollapse = ref(false)
const debtor = ref({})
const debtorContracts = ref([])
const files = ref([
  { name: 'Паспорт', type: 'passport_first_page' },
  { name: 'Прописка', type: 'passport_with_address' },
  { name: 'Селфи с паспортом', type: 'passport_selfie' },
])

const getStatusName = (statusId) => {
  const statuses = {
    1: 'Активный',
    2: 'На модерации',
    3: 'Просрочен',
    4: 'Просрочен',
    5: 'Отменен',
    9: 'Закрыт'
  }
  return statuses[statusId]
}

const loadDebtor = async () => {
  loading.value = true
  try {
    const { data } = await apiRequest.get(`/v3/debt-collect-leader/debtors/${route.params.debtorId}`)
    debtor.value = data
    loading.value = false
  } catch (e) {
    console.log(e)
  }
}
const loadDebtorContracts = async () => {
  try {
    const { data } = await apiRequest.get(`/v3/debt-collect-leader/debtors/${route.params.debtorId}/contracts`)
    debtorContracts.value = data
  } catch (e) {
    console.log(e)
  }
}

const goBack = () => {
  window.history.length > 0 ? router.back() : router.replace({ name: 'debt-collect-leader-debtors' })
}

onBeforeMount(()=>{
  loadDebtor()
  loadDebtorContracts()
})
</script>

<style lang="scss" scoped>
.debtor {
  &_contract {
    background-color: #eeeeee;
    border-radius: 8px;
    margin: 4px;
    width: calc(25% - 8px);
  }

  &__document {
    width: calc(100% / 3);
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
