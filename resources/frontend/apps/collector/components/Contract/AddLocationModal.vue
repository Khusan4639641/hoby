<template>
  <a-modal wrap-class-name="location-modal" width="100%" :visible="props.active" title="Добавить локацию" @ok="onSubmit"
    @cancel="onCancel" okText="Всё верно" cancelText="Отмена">
    <template v-if="state.pin">
      <div style="width: 100%; height: 100%; display: flex; flex-direction: column; gap: 20px;">
        <h3>Вы здесь?</h3>
        <yandex-map
            :settings="settings"
            :coords="state.pin"
            :zoom="10"
            style="width: 100%; flex: 1;"
            @click="changeLocation"
        >
          <ymap-marker :coords="state.pin"></ymap-marker>
        </yandex-map>
      </div>
    </template>
    <template v-else>
      <template v-if="state.map.error">
        <p>Проблемы с доступом к Вашей локации!</p>
      </template>
      <template v-else>
        <p>Загрузка...</p>
      </template>
    </template>
  </a-modal>
</template>

<script setup>
import { reactive, onBeforeMount, ref } from 'vue'
import { yandexMap, ymapMarker } from 'vue-yandex-maps'
import {notification} from "ant-design-vue";

const props = defineProps(['active'])
const emit = defineEmits(['cancel', 'submit'])

const state = reactive({
  pin: null,
  map: {
    error: null
  }
})

const settings = reactive({
  apiKey: '61a1dfaf-26ee-4023-a650-e7b628670418',
  lang: 'ru_RU',
  coordorder: 'latlong',
  enterprise: false,
  version: '2.1'
})

const init = () => {
  navigator.geolocation.getCurrentPosition(
    position => {
      state.pin = [
        position.coords.latitude,
        position.coords.longitude
      ]
    },
    error => {
      state.map.error = error
      console.log(error.message);
    },
  )
}

const changeLocation = (e) => {
  state.pin = e.get('coords');
}

const onSubmit = () => {
  if(!state.pin) {
    notification.error({
      message: 'Ошибка! Передаётся пустая локация!'
    })
    return
  }

  emit('submit', JSON.stringify(state.pin))
}

const onCancel = () => {
  emit('cancel')
}

onBeforeMount(init)
</script>

<style lang="scss">
.location-modal {
  display: flex;
  flex-direction: column;
  justify-content: center;
  .ant-modal {
    max-width: 100%;
    top: 0;
    padding-bottom: 0;
    margin: 0;
  }

  .ant-modal-content {
    display: flex;
    flex-direction: column;
    height: calc(100vh - 80px);
  }

  .ant-modal-body {
    flex: 1;
  }
}
</style>