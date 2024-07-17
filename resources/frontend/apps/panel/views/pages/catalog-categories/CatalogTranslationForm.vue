<template>
  <a-form layout="vertical" @submit="onSubmit">
    <a-form-item
      label="Название"
      v-bind="validateInfos.title"
    >
      <a-input v-model:value="formRef.title" />
    </a-form-item>
    <a-form-item>
      <a-button @click.prevent="onSubmit" type="primary">Сохранить</a-button>
    </a-form-item>
  </a-form>
</template>

<script setup>
import { reactive } from "vue"

import {Form, notification} from 'ant-design-vue'
import 'ant-design-vue/lib/notification/style'
import apiRequest from "../../../utils/apiRequest";

const props = defineProps(['translation'])

const useForm = Form.useForm

const formRef = reactive({
  title: props.translation.title
})

const rulesRef = reactive({
  title: [{
    required: true,
    message: 'Обязательное поле!'
  }],
})

const { validate, validateInfos } = useForm(formRef, rulesRef, {
  onValidate: (...args) => console.log(...args)
})

const updateTranslation = async (form) => {
  try {
    const translationId = props.translation.id
    await apiRequest.patch(`/v3/catalog-category-translations/${translationId}`, form)

    notification.success({
      message: 'Успешно обновлено!'
    })
  } catch (e) {
    console.log(e)
  }
}

const onSubmit = () => {
  validate().then(() => {
    updateTranslation(formRef)
  }).catch(err => {
    console.log('error', err);
  });
};
</script>
