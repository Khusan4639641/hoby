<template>
  <a-layout class="page dashboard-page">
    <a-page-header
      :title="category.languages ? category.languages[0].title : 'Категория'"
      sub-title="Редактирование категории"
      @back="handleBack"
    />
    <a-layout-content>
      <a-row :gutter="24" style="margin-bottom: 24px;">
        <a-col :span="24" :md="12">
          <a-card title="Редактирование">
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
              <a-form-item>
                <a-button @click.prevent="onSubmit" type="primary">Сохранить</a-button>
              </a-form-item>
            </a-form>
          </a-card>
        </a-col>
        <a-col :span="24" :md="12">
          <a-card
            :title="`Перевод (${translation.language_code})`"
            v-for="translation in category.languages"
            :key="translation.id"
            style="margin-bottom: 12px;"
          >
            <CatalogTranslationForm :translation="translation" />
          </a-card>
        </a-col>
      </a-row>

      <CatalogSubCategoriesTable :parent-category-id="route.params.catalogCategoryId" />
    </a-layout-content>
  </a-layout>
</template>

<script setup>
import {onBeforeMount, reactive, ref, watch} from "vue"
import { useRoute, useRouter } from 'vue-router'
import {Form, notification} from 'ant-design-vue'

import CatalogSubCategoriesTable from './CatalogSubCategoriesTable'
import CatalogTranslationForm from './CatalogTranslationForm'

import apiRequest from "../../../utils/apiRequest"

const route = useRoute()
const router = useRouter()

const category = ref({})

const useForm = Form.useForm

const formRef = reactive({
  psic_code: '',
  psic_text: ''
})

const rulesRef = reactive({
  psic_code: [],
  psic_text: []
})

const { validate, validateInfos } = useForm(formRef, rulesRef, {
  onValidate: (...args) => console.log(...args)
})

const loadCategory = async () => {
  const categoryId = route.params.catalogCategoryId
  if (!categoryId) return

  try {
    const {data} = await apiRequest.get(`/v3/catalog-categories/${categoryId}`)
    const responseCategory = data.data
    category.value = responseCategory
    formRef.psic_code = responseCategory.psic_code
    formRef.psic_text = responseCategory.psic_text
  } catch (e) {
    console.log(e)
  }
}

onBeforeMount(loadCategory)
watch(() => route.params.catalogCategoryId, loadCategory)

const updateCategory = async (form) => {
  try {
    await apiRequest.patch(`/v3/catalog-categories/${category.value.id}`, form)

    notification.success({
      message: 'Успешно обновлено!'
    })
  } catch (e) {
    console.log(e)
  }
}

const onSubmit = () => {
  validate().then(() => {
    updateCategory(formRef)
  }).catch(err => {
    console.log('error', err)
  })
}

const handleBack = () => {
  console.log(category.value)
  if(category.value.parent_id === undefined) {
    return null
  }

  if(category.value.parent_id === 0) {
    router.push({ name: 'catalog-categories' })
    return
  }

  router.push({ name: 'catalog-category', params: { catalogCategoryId: category.value.parent_id } })
}
</script>
