<template>
  <a-modal :cancel-text="'Отмена'" :ok-text="'Сохранить'" :ok-button-props="{ loading: submitBtnLoading }" :visible="props.active" :mask-closable="false" :title="`Районы куратора - ${props.curator.full_name} ` " @ok="onSubmit" @cancel="onCancel">
    <a-form @submit="onSubmit" layout="vertical">
      <a-form-item label="Регионы">
        <a-select ref="select" v-model:value="regionModel" placeholder="Выберите регион" @change="handleRegionChange">
          <a-select-option v-for="region in regions" :value="region.id">{{region.name}} <span class="text-muted float-right"> {{CheckExistingRegion(region.id)}}</span></a-select-option>
          <template v-if="regionsLoading" #notFoundContent>
            <a-spin size="small" /> Загрузка
          </template>
        </a-select>
      </a-form-item>
      <a-form-item label="Районы">
        <a-select :loading="regionModel && districts.length == 0" :disabled="!regionModel" :value="curatorRegionsObject[regionModel]" mode="multiple" style="width: 100%" placeholder="Выберите район"
          :options="districtsModified" :field-names="{ label:'name', value: 'id' }" @change="handleDistrictChange">
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

const props = defineProps(['active', 'curator'])
const emit = defineEmits(['cancel', 'submit'])

const curatorRegionsObject = ref({})
const curatorRegions = ref([])
const regions = ref([])
const districts = ref([])

const attachedDistricts = ref([])
const regionModel = ref(null)
const regionsLoading = ref(false)
const districtsLoading = ref(false)
const submitBtnLoading = ref(false)
const districtsModified = computed(()=>{
    let modifiedArr = districts.value.map((el)=> {
        if (attachedDistricts.value.includes(el.id)) {
          console.log(el.name);
          return {...el, disabled:true}
        }
        return el
    })
    return modifiedArr
})

const onSubmit = async () => {
  submitBtnLoading.value = true
  let prepared_districts = []
  let data = curatorRegionsObject.value
  for (const key in data) {
      prepared_districts = [...prepared_districts, ...data[key]]
  }

  try {
      const { data } = await apiRequest.patch(`/v3/debt-collect-leader/curators/${props.curator.id}/districts`,{district_id: prepared_districts})
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
const CheckExistingRegion = (region_id) => {
  let foundRegion = (curatorRegionsObject.value.hasOwnProperty(String(region_id)))
  if (!foundRegion) return
  if (curatorRegionsObject.value[region_id].length) return `(${curatorRegionsObject.value[region_id].length} выбрано)`
  else return
}
const loadCuratorRegions = async () => {
  try {
    const { data } = await apiRequest.get(`/v3/debt-collect-leader/curators/${props.curator.id}/regions`)
    curatorRegions.value = data

    curatorRegions.value.forEach((el)=>{
      let regionDistricts = []
      el.districts.forEach(dist => {
        regionDistricts.push(dist.id)
      });
      curatorRegionsObject.value[el.id] = regionDistricts
    })

  } catch (e) {
    console.log(e)
  }
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
const loadAttachedDistricts = async () => {
  attachedDistricts.value = []
  try {
    const { data } = await apiRequest.get(`/v3/debt-collect-leader/curator-districts`,{params: {except_curator_id:[props.curator.id]}})
    attachedDistricts.value = data
  } catch (e) {
    attachedDistricts.value = []
    console.log(e)
  }
}
onBeforeMount(()=>{
  loadCuratorRegions()
  loadAllRegions()
  loadAllDistricts()
  loadAttachedDistricts()
})

const handleRegionChange = (value) => {
  let isRegionExist = (curatorRegionsObject.value.hasOwnProperty(String(value)))
  if (!isRegionExist) curatorRegionsObject.value[value] = []
  loadAllDistricts()
}
const handleDistrictChange = (value) => {
  if (!curatorRegionsObject.value[regionModel.value]) return
  curatorRegionsObject.value[regionModel.value] = value
}

</script>

<style>

</style>
