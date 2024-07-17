<template>
  <a-modal wrap-class-name="photo-modal" width="100%" footer="" :visible="props.active" title="Добавить фотографию" @ok="onSubmit" @cancel="onCancel">
    <p v-if="state.camera.error">
      Нет доступа к камере!
    </p>
    <template v-else>
      <div v-show="!state.camera.isPhotoTaken">
        <video ref="camera" style="width: 100%" autoplay></video>
        <a-button type="primary" block @click="takePhoto">Сделать снимок</a-button>
      </div>
      <div v-show="state.camera.isPhotoTaken" style="display: flex; flex-direction: column; gap: 10px;">
        <canvas ref="photoCanvas"></canvas>
        <a-button type="default" block @click="resetPhoto">Переснять</a-button>
        <a-button type="primary" block @click="sendPhoto">Отправить снимок</a-button>
      </div>
    </template>
  </a-modal>
</template>

<script setup>
import { reactive, onBeforeMount, onBeforeUnmount, ref } from 'vue'

const props = defineProps(['active'])
const emit = defineEmits(['cancel', 'submit'])

const state = reactive({
  camera: {
    isOpen: false,
    isLoading: false,
    isPhotoTaken: false,
    error: null
  },
  photo: null
})

const camera = ref(null)
const photoCanvas = ref(null)

const init = () => {
  state.camera.isLoading = true

  navigator.mediaDevices
    .getUserMedia({
      audio: false,
      video: {
          facingMode: 'environment'
      }
    })
    .then(stream => {
      camera.value.srcObject = stream
    })
    .catch(error => {
      console.log(error)
      state.camera.error = error
    })
    .finally(() => {
      state.camera.isLoading = false
    })
}

const takePhoto = () => {
  const resolution = {
    width: camera.value.offsetWidth,
    height: camera.value.offsetHeight
  }
  const canvas = photoCanvas.value
  canvas.height = resolution.height
  canvas.width = resolution.width

  const context = canvas.getContext('2d')

  state.camera.isPhotoTaken = true
  context.drawImage(camera.value, 0, 0, resolution.width, resolution.height)
}

const resetPhoto = () => {
  state.camera.isPhotoTaken = false
}

const sendPhoto = () => {
  const imageUrl = photoCanvas.value.toDataURL("image/jpeg")
  emit('submit', imageUrl)
}

const closeCamera = () => {
  const tracks = camera.value.srcObject.getTracks();

  tracks.forEach(track => {
    track.stop();
  });
}

const onSubmit = () => {
  emit('submit', state.photo)
}

const onCancel = () => {
  emit('cancel')
}

onBeforeMount(init)
onBeforeUnmount(closeCamera)
</script>

<style lang="scss">
.photo-modal {
  .ant-modal {
    max-width: 100%;
    padding-bottom: 0;
    margin: 0;
  }
}
</style>