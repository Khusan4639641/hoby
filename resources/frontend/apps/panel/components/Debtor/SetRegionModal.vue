<template>
  <a-modal :cancel-text="'Отмена'" :ok-text="'Сохранить'" :ok-button-props="{disabled: (!regionModel || !districtModel), loading: submitBtnLoading }" :visible="props.active" :mask-closable="false" :title="`Район должника - ${props.debtor.full_name} ` " @ok="onSubmit" @cancel="onCancel">
    <a-form @submit="onSubmit" layout="vertical">
      <a-form-item label="Регионы">
        <a-select ref="select" v-model:value="regionModel" placeholder="Выберите регион" @change="handleRegionChange">
          <a-select-option v-for="region in regions" :value="region.id">{{region.name}}</a-select-option>
          <template v-if="regionsLoading" #notFoundContent>
            <a-spin size="small" /> Загрузка
          </template>
        </a-select>
      </a-form-item>
      <a-form-item label="Районы">
        <a-select :loading="regionModel && districts.length == 0" :disabled="!regionModel" v-model:value="districtModel" style="width: 100%" placeholder="Выберите район"
          :options="districts" :field-names="{ label:'name', value: 'id' }">
          <template v-if="districtsLoading" #notFoundContent>
            <a-spin size="small" /> Загрузка
          </template>
        </a-select>
      </a-form-item>
    </a-form>
  </a-modal>
</template>

<script setup>
import { ref, onBeforeMount, computed } from 'vue'
import apiRequest from '../../utils/apiRequest'

const props = defineProps(['active', 'debtor'])
const emit = defineEmits(['cancel', 'submit'])

const debtorRegions = ref([])
const regions = ref([])
const districts = ref([])

const attachedDistricts = ref([])
const regionModel = ref(null)
const districtModel = ref(null)
const regionsLoading = ref(false)
const districtsLoading = ref(false)
const submitBtnLoading = ref(false)

const onSubmit = async () => {
  submitBtnLoading.value = true
  try {
      const { data } = await apiRequest.post(`/v3/debt-collect-leader/debtors/${props.debtor.id}/district`,{district_id: districtModel.value})
      submitBtnLoading.value = false
      emit('submit')
  } catch (e) {
    submitBtnLoading.value = false
      console.log(e)
  }
}
const onCancel = () => {
  emit('cancel')
}
const loadAllRegions = async () => {
  regionsLoading.value = true
  try {
    const { data } = await apiRequest.get(`/v3/regions`)
    regions.value = data
    regionsLoading.value = false
  } catch (e) {
    regionsLoading.value = false
    console.log(e)
  }
}
const loadAllDistricts = async () => {
  
  districts.value = []
  districtModel.value = null
  if (!regionModel.value) return
  districtsLoading.value = true
  try {
    const { data } = await apiRequest.get(`/v3/districts`,{params: {region_id:regionModel.value}})
    districts.value = data
    districtsLoading.value = false
  } catch (e) {
    districts.value = []
    districtsLoading.value = false
    console.log(e)
  }
}
onBeforeMount(()=>{
  loadAllRegions()
  loadAllDistricts()
})

const handleRegionChange = (value) => loadAllDistricts()

</script>

<style>

</style>