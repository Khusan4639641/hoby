<template>
  <a-modal :closable="!props.sending" :mask-closable="!state.photo || !props.sending"  footer="" :visible="props.active" title="Добавить фотографию" @ok="onSubmit" @cancel="onCancel">

      <div>
        <a-image style="width:100%" :src="state.photo" />
        <a-upload accept="image/png, image/jpeg, image/png, image/webp"  v-model:file-list="state.fileList" :show-upload-list="false" ref="uploadRef" :max-count="1" style="width:100%" :multiple="false" :before-upload="beforeUpload" @change="removeAttrCapture" @remove="cleanModel">
            <a-button  :disabled="props.sending"  v-if="state.photo" @click="()=> {return false}" style="width:100%; margin-top: 8px"  type="default" block>
              Выбрать заново
            </a-button>
            <a-button v-else style="width:100%"  type="primary" block>
              <appstore-outlined /> Выберите из галереи
            </a-button>
        </a-upload>
        <a-space v-if="state.photo" style="width:100%; margin-top: 8px" direction="vertical">
            <a-button :loading="props.sending" @click="sendPhoto" style="width:100%"  type="primary" block>
                Отправить изображение
            </a-button>
            <a-button :disabled="props.sending"  @click="cleanModel" danger style="width:100%" type="link" block>
              Отменить
            </a-button>
            
        </a-space>
        
        <p  v-if="!state.photo" style="margin:.5rem 0; text-align:center">или</p>
        <a-button v-if="!state.photo" type="primary" block @click="goToCamera"><camera-outlined />Сделайте снимок с камеры</a-button>
      </div>
  </a-modal>
</template>

<script setup>
import { reactive, onBeforeUnmount, ref, onMounted } from 'vue'

const props = defineProps({
  active: Boolean,
  sending: Boolean,
})
const emit = defineEmits(['cancel', 'submit'])

const state = reactive({
  fileList: [],
  photo: null
})

const uploadRef = ref(null)


const goToCamera = () => emit('camera')
const cleanModel = () => {
  state.photo = null
  state.fileList = []
}
const removeAttrCapture = () => {
  let fileInput = document.querySelector('[capture="false"]')
  fileInput.removeAttribute('capture')
}
const sendPhoto = () => {
  emit('submit', state.photo)
}

const beforeUpload = (file) => {
  const reader = new FileReader();
  reader.onload = e => state.photo = e.target.result
  state.photo = reader.readAsDataURL(file);
  return false;
}
const onSubmit = () => {
  emit('submit', state.photo)
}
const onCancel = () => {
  emit('cancel')
}
onMounted(removeAttrCapture)
onBeforeUnmount(cleanModel)
</script>

<style lang="scss">
.ant-upload {
  width: 100%;
}
</style>