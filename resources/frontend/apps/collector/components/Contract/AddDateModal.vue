<template>
  <a-modal
    :visible="props.active"
    title="Добавить дату"
    @ok="onSubmit"
    @cancel="onCancel"
  >
    <a-form @submit="onSubmit" layout="vertical">
      <a-form-item
        label="Дата ожидаемой оплаты"
        v-bind="validateInfos.date"
      >
        <a-date-picker style="width: 100%;" :disabled-date="disabledDate" v-model:value="formState.date" />
      </a-form-item>
      <a-form-item
          label="Обещанная сумма"
          v-bind="validateInfos.amount"
      >
        <a-input-number style="width: 100%;" :min="0" v-model:value="formState.amount" />
      </a-form-item>
      <a-form-item
          label="Комментарий"
          v-bind="validateInfos.comment"
      >
        <a-input style="width: 100%;" v-model:value="formState.comment" />
      </a-form-item>
    </a-form>
  </a-modal>
</template>

<script setup>
import { reactive } from 'vue'
import { Form } from 'ant-design-vue'
import dayjs from "dayjs";

const props = defineProps(['active'])
const emit = defineEmits(['cancel', 'submit'])

const useForm = Form.useForm
const formState = reactive({
  date: '',
  amount: null,
  comment: ''
})
const formRules = reactive({
  date: [{ required: true, message: 'Введите дату!' }],
  amount: [{ required: true, message: 'Введите сумму!' }],
  comment: []
})

const { validate, validateInfos } = useForm(formState, formRules)

const disabledDate = current => {
  return current && current < dayjs().subtract(1, 'day').endOf('day')
};

const onSubmit = () => {
  validate()
    .then(({ date, amount, comment }) => {
      const content = JSON.stringify({
        date: date.format('DD.MM.YYYY'),
        amount, comment
      })
      emit('submit', content)
    })
}

const onCancel = () => {
  emit('cancel')
}
</script>

<style>

</style>