<template>
  <a-page-header
    title="Категории"
  >
    <template #extra>
      <a-button @click="isModalOpen = true" type="primary">Добавить</a-button>
    </template>
  </a-page-header>
  <a-table
    :columns="columns"
    :data-source="catalogCategories"
    rowKey="id"
    :pagination="false"
    bordered
  >
    <template #bodyCell="{ column, text, record }">
      <template v-if="column.key === 'name'">
        {{ record.languages[0].title }}
      </template>
      <template v-if="column.key === 'actions'">
        <router-link :to="{ name: 'catalog-category', params: { catalogCategoryId: record.id } }">
          <a-button type="primary">Перейти</a-button>
        </router-link>
      </template>
    </template>
  </a-table>
  <a-modal
    v-model:visible="isModalOpen"
    title="Добавление подкатегории"
    @ok="onSubmit"
    ok-text="Добавить"
    cancel-text="Отменить"
    @close="closeModal"
  >
    <a-form layout="vertical">
      <a-form-item
        label="ИКПУ"
        v-bind="validateInfos.psic_code"
      >
        <a-input v-model:value="formRef.psic_code" />
      </a-form-item>
      <a-form-item
        label="ИКПУ (Описание)"
        v-bind="validateInfos.psic_text"
      >
        <a-textarea v-model:value="formRef.psic_text" />
      </a-form-item>
      <a-form-item
        label="Перевод (ru)"
        v-bind="validateInfos.translation_ru"
      >
        <a-input v-model:value="formRef.translation_ru" />
      </a-form-item>
      <a-form-item
        label="Перевод (uz)"
        v-bind="validateInfos.translation_uz"
      >
        <a-input v-model:value="formRef.translation_uz" />
      </a-form-item>
    </a-form>
  </a-modal>
</template>

<script setup>
import {ref, reactive, onBeforeMount, watch} from 'vue'
import {Form, notification} from 'ant-design-vue'

import apiRequest from '../../../utils/apiRequest'

const props = defineProps(['parentCategoryId'])

const columns = [
  {
    title: 'ID',
    dataIndex: 'id',
    key: 'id',
    width: '100px'
  },
  {
    title: 'Название',
    key: 'name'
  },
  {
    title: 'ИКПУ',
    dataIndex: 'psic_code',
    key: 'psic_code'
  },
  {
    title: 'ИКПУ (Описание)',
    dataIndex: 'psic_text',
    key: 'psic_text'
  },
  {
    title: 'Действия',
    key: 'actions'
  },
]

const catalogCategories = ref([])
const isModalOpen = ref(false)

const loadCategories = async () => {
  try {
    const {data} = await apiRequest.get('/v3/catalog-categories', {
      params: {
        parent_id: props.parentCategoryId
      }
    })

    catalogCategories.value = data.data
  } catch (e) {
    console.log(e)
  }
}

onBeforeMount(loadCategories)
watch(() => props.parentCategoryId, loadCategories)

const useForm = Form.useForm

const formRef = reactive({
  psic_code: '',
  psic_text: '',
  translation_ru: '',
  translation_uz: ''
})

const rulesRef = reactive({
  psic_code: [],
  psic_text: [],
  translation_ru: [{
    required: true,
    message: 'Обязательное поле!'
  }],
  translation_uz: [{
    required: true,
    message: 'Обязательное поле!'
  }],
})

const { validate, validateInfos } = useForm(formRef, rulesRef, {
  onValidate: (...args) => console.log(...args)
})

const closeModal = () => {
  isModalOpen.value = false
}

const createCategory = async (form) => {
  try {
    const formData = JSON.parse(JSON.stringify(form))
    formData.parent_id = props.parentCategoryId
    formData.status = 1
    formData.languages = [
      {
        language_code: 'ru',
        title: form.translation_ru
      },
      {
        language_code: 'uz',
        title: form.translation_uz
      },
    ]

    delete formData.translation_ru
    delete formData.translation_uz

    await apiRequest.post(`/v3/catalog-categories`, formData)

    notification.success({
      message: 'Успешно добавлено!'
    })
    loadCategories()
    closeModal()
  } catch (e) {
    console.log(e)
  }
}

const onSubmit = () => {
  validate().then(() => {
    createCategory(formRef)
  }).catch(err => {
    console.log('error', err)
  })
}
</script>
