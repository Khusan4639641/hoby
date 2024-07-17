<template>
  <a-layout class="page login-page" >
    <a-row justify="center">
      <a-col :span="20" :md="10" :xl="6" :xxl="4">
        <a-card title="Вход">
          <a-form @submit="onSubmit" layout="vertical">
            <a-form-item label="Номер телефона" v-bind="validateInfos.phone">
              <a-input :readonly="loading" v-model:value="modelRef.phone" v-mask="'(##) ### ##-##'" type="tel" autocomplete="off">
                <template #prefix>
                  +998
                </template>
              </a-input>
            </a-form-item>
            <a-form-item label="Пароль" v-bind="validateInfos.password">
              <a-input-password :readonly="loading" v-model:value="modelRef.password">
                <template #prefix>
                  <lock-outlined />
                </template>
              </a-input-password>
            </a-form-item>
            <a-form-item name="submit">
              <a-button :loading="loading" type="primary" html-type="submit" block>Войти</a-button>
            </a-form-item>
          </a-form>
        </a-card>
      </a-col>
    </a-row>
  </a-layout>
</template>

<script setup>
import { notification } from 'ant-design-vue'
import 'ant-design-vue/lib/notification/style'
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { Form } from 'ant-design-vue'

// TODO: Настроить alias (Greydius)
import { useAuthStore } from '../../stores/authStore'

// TODO: Настроить alias (Greydius)
import { parse as phoneParse } from '../../helpers/phoneHelper'

const router = useRouter()
const authStore = useAuthStore()

const useForm = Form.useForm
const loading = ref(false)
const modelRef = reactive({
  phone: null,
  password: null
})
const rulesRef = reactive({
  phone: [
    {
      required: true,
      // TODO: i18n (Greydius)
      message: 'Пожалуйста, введите номер телефона!',
      trigger: 'change'
    },
    {
      trigger: 'blur',
      validator: async (_rule, value) => {
        if (phoneParse(value)) {
          return Promise.resolve()
        } else {
          // TODO: i18n (Greydius)
          return Promise.reject('Введите корректный номер!')
        }
      }
    }
  ],
  password: [
    {
      required: true,
      // TODO: i18n (Greydius)
      message: 'Пожалуйста, введите пароль!',
    }
  ]
})

const { validate, validateInfos } = useForm(modelRef, rulesRef)
const onSubmit =  () => {
  loading.value = true
  validate().then(({ phone: rawPhone, password }) => {
      authStore.auth({
        phone: phoneParse(rawPhone),
        password
      }).then(() => {
          loading.value = false
          router.push({ name: 'dashboard' })
          authStore.init()
        }).catch((errors) => {
          loading.value = false
          errors.forEach(({ text }) => {
            notification.error({
              message: 'Ошибка',
              description: text
            })
          })

        })
    })
    .catch(err => {
      // TODO: Отлавливать ошибки (Greydius)
      loading.value = false
      console.log('error', err)
    })
}
</script>

<style lang="scss" scoped>
.login-page {
  justify-content: center;
  min-height: 100vh;

}
</style>
