<template>
  <a-layout class="page debtor-page">
    <a-page-header
      :title="`Должник `"
      :sub-title="`${debtor.full_name || ''}`"
      @back="() => router.push({ name: 'debtors', query: { district_id: debtor.district_id } })"
      >
    </a-page-header>
    <a-card :loading="loading">
      <template #actions>
        <picture-outlined @click="modals.gallery = true" />
        <edit-outlined key="edit" @click="modals.comment = true" />
        <calendar-outlined @click="modals.date = true" />
        <pushpin-outlined @click="modals.location = true" />
      </template>
      <a-card-grid :hoverable="false" style="width: 100%">
        <a-card-meta title="Личные данные">
          <template #description>
            <p>
              <b>Ф.И.О:</b> {{ debtor.full_name }}
              <br>
              <b>Телефон:</b> <a :href="`tel:${debtor.phone}`">{{ debtor.phone }}</a>
              (<a :href="`https://t.me/${debtor.phone}`" target="_blank">Телеграм <send-outlined /></a>)
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
                <b>Телефон:</b> <a :href="`tel:${guarant.phone}`">{{ guarant.phone }}</a>
                (<a :href="`https://t.me/+${guarant.phone}`" target="_blank">Телеграм <send-outlined /></a>)
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
                <PaymentHistoryTable :payload="{ user_id: route.params.debtor}" />
              </a-collapse-panel>
        </a-collapse>
      </a-card-grid>
      <a-card-grid :hoverable="false" style="width: 100%" >
        <a-card-meta :title="`Контракты должника`">
          <template #description>
            <a-card style="margin-top: 1rem;" :bordered="false">
              <a-card-grid class="debtor_contract" v-for="contract in debtor.contracts" :key="contract.id">
                <router-link  :to="{ name: 'contract', params: { contract: contract.id, debtor: route.params.debtor } }">
                  <a-card-meta :title="`Контракт #${contract.id}`">
                    <template #description>
                      <p style="margin: 0;">
                        <b>Сумма долга:</b> {{ contract.debt_sum }} сум
                        <br>
                        <b>Просрочено:</b> {{ contract.expired_days }} дней
                        <br>
                        <b>Статус:</b> {{ getStatusName(contract.status) }}
                      </p>
                    </template>
                  </a-card-meta>
                </router-link>
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
  <AddCommentModal :active="modals.comment" @submit="handleComment" @cancel="modals.comment = false" />
  <AddDateModal :active="modals.date" @submit="handleDate" @cancel="modals.date = false" />
  <AddPhotoModal v-if="modals.photo" :active="modals.photo" @submit="handlePhoto" @cancel="modals.photo = false" />
  <AddLocationModal v-if="modals.location" :active="modals.location" @submit="handleLocation" @cancel="modals.location = false" />
  <AddPhotoFromGalleryModal v-if="modals.gallery" :sending="modals.gallery_sending" :active="modals.gallery" @submit="handleGallery" @camera="modals.gallery = false; modals.photo = true" @cancel="modals.gallery = false; modals.gallery_sending = false" />
</template>

<script setup>
import { notification } from 'ant-design-vue'
import 'ant-design-vue/lib/notification/style'

import { ref, reactive, onBeforeMount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import apiRequest from '../../utils/apiRequest'
import PaymentHistoryTable from '../../components/Contract/PaymentHistoryTable'

import AddCommentModal from '../../components/Contract/AddCommentModal'
import AddDateModal from '../../components/Contract/AddDateModal'
import AddPhotoModal from '../../components/Contract/AddPhotoModal'
import AddPhotoFromGalleryModal from '../../components/Contract/AddPhotoFromGalleryModal'
import AddLocationModal from '../../components/Contract/AddLocationModal'

const route = useRoute()
const router = useRouter()

const loading = ref(false)
const paymentHistoryCollapse = ref(false)
const debtor = ref({})

const files = ref([
  { name: 'Паспорт', type: 'passport_first_page' },
  { name: 'Прописка', type: 'passport_with_address' },
  { name: 'Селфи с паспортом', type: 'passport_selfie' },
])

const modals = reactive({
  comment: false,
  date: false,
  location: false,
  photo: false,
  gallery: false,
  gallery_sending: false,
})

const loadDebtor = async () => {
  loading.value = true
  try {
    const { data } = await apiRequest.get(`/v3/debt-collector/debtors/${route.params.debtor}`)
    debtor.value = data
    loading.value = false
  } catch (e) {
    console.log(e)
  }
}
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

const handleTransaction = async (type, data) => {
  try {
    await apiRequest.post(`/v3/debt-collector/debtors/${route.params.debtor}/actions`, {
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
        message: 'Ошибка',
        description: text
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
      return new File([blob], 'collector-debtor-photo.jpeg')
    })

  handleTransaction('photo', {type: 'camera', file: imageFile})
    .then(() => modals.photo = false)
}

const handleGallery = async (imageUrl) => {
  modals.gallery_sending = true
  const imageFile = await fetch(imageUrl)
    .then(res => res.blob())
    .then(blob => {
      return new File([blob], 'collector-debtor-photo.jpeg')
    })
  handleTransaction('photo', {type: 'gallery', file: imageFile})
    .then(() =>{ modals.gallery_sending = false; modals.gallery = false})
}

const handleLocation = (data) => {
  handleTransaction('location', data)
    .then(() => modals.location = false)
}
onBeforeMount(()=>{
  loadDebtor()
})
</script>

<style lang="scss" scoped>
.debtor {
  &_contract {
    background-color: #eeeeee;
    border-radius: 8px;
    margin: 4px;
    width: calc(100%);
    @media (min-width: 768px) {
      width: calc(50% - 8px);
    }
    @media (min-width: 1200px) {
      width: calc(25% - 8px);
    }
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