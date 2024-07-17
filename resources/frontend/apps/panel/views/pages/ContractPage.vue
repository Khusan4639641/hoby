<template>
  <a-layout class="page contract-page">
    <a-page-header
      :title="`Контракт №${contract.id || ''}`"
      @back="() => router.push({ name: 'contracts', query: { local_region: contract.local_region } })"
      >
        <template #extra>
        <router-link :to="{ name: 'logout' }">
          <a-button danger>Выйти</a-button>
        </router-link>
      </template>
    </a-page-header>
    <a-card :loading="loading">
      <template #actions>
        <picture-outlined @click="modals.photo = true" />
        <edit-outlined key="edit" @click="modals.comment = true" />
        <calendar-outlined @click="modals.date = true" />
        <pushpin-outlined @click="modals.location = true" />
      </template>
      <a-card-grid style="width: 100%">
        <a-card-meta title="Личные данные">
          <template #description>
            <p>
              <b>Ф.И.О.:</b> {{ contract.fio }}
              <br>
              <b>Телефон:</b> {{ contract.phone }}
              <br>
              <b>Паспорт:</b> {{ contract.passport_number }}
              <br>
              <b>Прописка:</b> {{ contract.registration_address }}
            </p>
          </template>
        </a-card-meta>
      </a-card-grid>

      <a-card-grid v-if="contract.guarants" style="width: 100%">
        <a-card-meta title="Доверители">
          <template #description>
            <template v-if="contract.guarants.length > 0">
              <p v-for="guarant in contract.guarants" :key="guarant.phone">
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
      <a-card-grid style="width: 100%">
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
      <a-card-grid style="width: 100%">
        <a-card-meta title="Задолжность">
          <template #description>
            <p>
              <b>Сумма:</b> {{ contract.recovery_sum }}
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
  <AddCommentModal :active="modals.comment" @submit="handleComment" @cancel="modals.comment = false" />
  <AddDateModal :active="modals.date" @submit="handleDate" @cancel="modals.date = false" />
  <AddPhotoModal v-if="modals.photo" :active="modals.photo" @submit="handlePhoto" @cancel="modals.photo = false" />
  <AddLocationModal v-if="modals.location" :active="modals.location" @submit="handleLocation" @cancel="modals.location = false" />
</template>

<script setup>
import { notification } from 'ant-design-vue'
import 'ant-design-vue/lib/notification/style'

import { ref, reactive, onBeforeMount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import apiRequest from '../../utils/apiRequest'

import AddCommentModal from '../../components/Contract/AddCommentModal'
import AddDateModal from '../../components/Contract/AddActionModal'
import AddPhotoModal from '../../components/Contract/AddPhotoModal'
import AddLocationModal from '../../components/Contract/AddLocationModal'

const route = useRoute()
const router = useRouter()

const loading = ref(false)
const contract = ref({})
const files = ref([
  { name: 'Акт', type: 'act' },
  { name: 'Паспорт и прописка', type: 'passport_with_address' },
  { name: 'Селфи с паспортом', type: 'passport_selfie' },
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
    const { data } = await apiRequest.get(`/v1/collector/contracts/${route.params.contract}`)
    contract.value = data
    loading.value = false
  } catch (e) {
    console.log(e)
  }
}

const handleTransaction = async (type, data) => {
  try {
    await apiRequest.post(`/v1/collector/transactions`, {
      collector_contract_id: contract.value.collector_contract_id,
      type,
      content: data
    }, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    notification.success({
      message: 'Успешно отправлено!'
    })  
  } catch (e) {
    const { error: errors } = e.response.data
    errors.forEach(({ text }) => {
      notification.error({
        message: text
      })  
    })
    console.log(e)
  }
}

const handleComment = (data) => {
  handleTransaction('text', data)
    .then(() => modals.comment = false)
}

const handleDate = (data) => {
  handleTransaction('date', data)
    .then(() => modals.date = false)
}

const handlePhoto = async (imageUrl) => {
  const imageFile = await fetch(imageUrl)
    .then(res => res.blob())
    .then(blob => {
      return new File([blob], 'collector-contract-photo.jpeg')
    })

  handleTransaction('photo', imageFile)
    .then(() => modals.photo = false)
}

const handleLocation = (data) => {
  handleTransaction('location', data)
    .then(() => modals.location = false)
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