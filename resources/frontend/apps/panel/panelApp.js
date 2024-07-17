import { createApp } from "vue"
import { createPinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import VueTheMask from 'vue-the-mask'
require('dayjs/locale/ru')

import router from "./router"

import App from "./App"

import './assets/styles/app.scss'

const pinia = createPinia()
// TODO: Добавить i18n (Greydius)
const i18n = createI18n({

})

const app = createApp(App)

app.use(pinia)
app.use(i18n)
app.use(router)

app.use(VueTheMask)

app.mount("#vue-app")
