<template>
  <a-modal
    :visible="props.active"
    title="Добавить комментарий"
    @ok="onSubmit"
    @cancel="onCancel"
  >
    <a-form @submit="onSubmit" layout="vertical">
      <a-form-item
        label="Комментарий"
        v-bind="validateInfos.comment"
      >
        <a-input v-model:value="formState.comment" placeholder="Сообщение..." />
      </a-form-item>
    </a-form>
  </a-modal>
</template>

<script setup>
import { reactive } from 'vue'
import { Form } from 'ant-design-vue'

const props = defineProps(['active'])
const emit = defineEmits(['cancel', 'submit'])

const useForm = Form.useForm
const formState = reactive({
  comment: ''
})
const formRules = reactive({
  comment: [{ required: true, message: 'Введите сообщение!' }]
})

const { validate, validateInfos } = useForm(formState, formRules)

const onSubmit = () => {
  validate()
    .then(({ comment }) => {
      emit('submit', comment)
    })
}

const onCancel = () => {
  emit('cancel')
}
</script>

<style>

</style>