<template>
  <a-modal :footer="null" :visible="props.active" :mask-closable="false" @cancel="onCancel" :title="`Район должника - ${props?.debtor?.full_name } `" >
    <a-form :model="formData" @finish="onSubmit" layout="vertical">
      <a-form-item label="Регионы" name="region"  :rules="[{ required: true, message: 'Поле обязательно для заполнения' }]">
        <a-select ref="select" show-search :loading="loading.districtsSelect" :filter-option="filterOption" v-model:value="formData.region" placeholder="Выберите регион" @change="handleRegionChange" :options="regionsList" :field-names="{ label:'name', value: 'id' }">
          <!-- <a-select-option v-for="region in regionsList" :value="region.id">{{region.name}}</a-select-option> -->
          <!-- <template v-if="loading.regionsSelect" #notFoundContent>
            <a-spin size="small" /> Загрузка
          </template> -->
        </a-select>
      </a-form-item>
      <a-form-item label="Районы"  name="district" :rules="[{ required: true, message: 'Поле обязательно для заполнения' }]">
        <a-select show-search :loading="formData.region && districtsList.length == 0" :filter-option="filterOption" :disabled="!formData.region" v-model:value="formData.district" style="width: 100%" placeholder="Выберите район"
          :options="districtsList" :field-names="{ label:'name', value: 'cbu_id' }">
          <template v-if="loading.districtsSelect" #notFoundContent>
            <a-spin size="small" /> Загрузка
          </template>
        </a-select>
      </a-form-item>
      <a-form-item label="Комментарий"  name="comment" :rules="[{ required: true, message: 'Поле обязательно для заполнения' }]">
        <a-textarea v-model:value="formData.comment"   />
      </a-form-item>
      <a-form-item label="Основательный документ">
        <a-upload-dragger
          v-if="formData.fileList.length == 0"
          v-model:fileList="formData.fileList"
          :max-сount="1"
          accept="image/*"
          name="file"
          :multiple="false"
        >
          <p class="ant-upload-drag-icon">
            <inbox-outlined></inbox-outlined>
          </p>
          <p class="ant-upload-text">Нажмите или перетащите файл для загрузки</p>
        </a-upload-dragger>

        <div v-else class="preview">
          <a-space>
            <a-image
              class="preview__image"
              :width="200"
              :src="imageObjUrl(formData.fileList[0]?.originFileObj)"
            />
            <a-button @click="formData.fileList.length = []" class="preview__remove" shape="circle" type="danger" >
              <template #icon>
                <DeleteOutlined/>
              </template>
            </a-button>
          </a-space>
        </div>
      </a-form-item>
      <a-alert
        v-if="reqError"
        message="Ошибка"
        type="error"
        show-icon
      >
      <template #description>
        <div v-for="(err, i) in reqError" :key="`err${i}`" class="ant-alert-description">{{err.text}}</div>
      </template>
      </a-alert>
      <a-divider />

      <div class="d-flex justify-content-end form-footer">
        <a-space>
          <a-button @click="onCancel">Отмена</a-button>
          <a-button :loading="loading.submitBtn" :disabled="(!formData.region || !formData.district || reqError )" type="primary" html-type="submit">Сохранить</a-button>
        </a-space>
      </div>
    </a-form>
  </a-modal>
</template>

<script setup>
import { ref, onBeforeMount, reactive, watch } from 'vue'
import apiRequest from '../../utils/apiRequest'

const props = defineProps(['active', 'debtor', 'urlPrefix', 'type'])
const emit = defineEmits(['cancel', 'submit'])

const reqError = ref(null)

const regionsList = ref([])
const districtsList = ref([])


const formData = reactive({
  fileList: [],
  region: null,
  district: null,
  comment: null,
})
const loading = reactive({
  regionsSelect: false,
  districtsSelect: false,
  comment: false,
  submitBtn: false,
})


watch( () => formData.district, (newValue, oldValue) => {
    if (newValue != oldValue) {
        reqError.value = null
    }
  },
)


const onSubmit = async () => {
  reqError.value = null
  let file = formData.fileList[0]?.originFileObj || null
  loading.submitBtn = true
  let payload  = {
    comment: formData.comment,
    cbu_id: formData.district,
    file
  }
  try {
      const { data } = await apiRequest.post(`/v3/${props.urlPrefix}/debtors/${props.debtor.id}/update-district`, payload, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })
      loading.submitBtn = false
      emit('submit', props.type)
      polipop.add({content: 'Район должника успешно изменен!', title: `Успешно`, type: 'success'});
  } catch (e) {
    if (e.response.data.status == 'error') {
        reqError.value = e.response.data.error
    }
    loading.submitBtn = false
      console.error(e)
  }
}

const onCancel = () => {
  emit('cancel')
}
const loadAllRegions = async () => {
  loading.regionsSelect = true
  try {
    const { data } = await apiRequest.get(`/v3/regions`)
    regionsList.value = data
    loading.regionsSelect = false
  } catch (e) {

    loading.regionsSelect = false
    console.log(e)
  }
}
const imageObjUrl = (object)=>{
  if (!object) return ''
  let preview = URL.createObjectURL(object);
  return preview
}
const loadAllDistricts = async () => {

  districtsList.value = []
  formData.district = null
  if (!formData.region) return
  loading.districtsSelect = true
  try {
    const { data } = await apiRequest.get(`/v3/districts`,{params: {region_id:formData.region}})
    districtsList.value = data
    loading.districtsSelect = false
  } catch (e) {
    districtsList.value = []
    loading.districtsSelect = false
    console.log(e)
  }
}
const filterOption = (input, option) => {
  return option.name?.toLowerCase().indexOf(input?.toLowerCase()) >= 0;
};
onBeforeMount(async ()=>{

  await loadAllRegions()
  formData.region = props?.debtor?.region?.id || null
  await loadAllDistricts()
  formData.district = props?.debtor?.district?.cbu_id || null
})

const handleRegionChange = (value) => {
  loadAllDistricts()
}

</script>

<style>
.preview .preview__image, .preview .ant-image-mask{
  border-radius: 6px;
}

</style>
