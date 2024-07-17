<template>
    <a-layout class="page dashboard-page">
      <a-page-header title="Коллекторы" sub-title="Выберите коллектора">
      </a-page-header>
      <a-table :columns="columns" :loading="loading" :data-source="collectors" :pagination="pagination" @change="onChange">
        <template #bodyCell="{text, column, record }">
          <template v-if="column.key === 'full_name'">
            <router-link :to="{ name: 'debt-collect-curator-collector', params: { collectorId: record.id } }">
              {{ record.full_name }}  
            </router-link>
          </template>
          <template v-else-if="column.key === 'regions'">
              <div v-if="record.regions.length">
                  <div v-for="region in record.regions" :key="region.id" class="cont">
                      <h6 class="m-0">{{region.name}}:</h6>
                      <div class="district-tags mb-2 mt-1">
                              <a-tag
                              v-for="district in region.districts"
                              :key="district"
                              color="grey"
                          >
                              {{ district.name}}
                          </a-tag>
                      </div>

                  </div>
              </div>
        </template>
          <template v-else>
            {{text}}
          </template>
        </template>
      </a-table>
    </a-layout>
  </template>
  
  <script setup>
    import { ref, reactive, onBeforeMount } from 'vue'
    import apiRequest from '../../../../utils/apiRequest'

    const columns = [
        {
            title: 'ID',
            dataIndex: 'id',
            key: 'id',
            width: 50,
            fixed: true
        },
        {
            title: 'ФИО',
            key: 'full_name',
            width: 200
        },
        {
            title: 'Номер телефона',
            dataIndex: 'phone',
            key: 'phone',
            width: 200
        },
        
        {
            title: 'Районы',
            dataIndex: 'regions',
            key: 'regions',
            width: 400
        },
        {
            title: 'Кол-во должников ',
            dataIndex: 'debtors_count',
            key: 'debtors_count',
            width: 50
        },
    ]
    const pagination = reactive({
        current: 1,
        total: 1,
        pageSize: 15,
        showSizeChanger:false,
        hidOnSinglePage:true,
        showQuickJumper:true
    })
    const searchModel = ref('')
    const collectors = ref([])
    const loading = ref(false)
    const loadCollectors = async () => {
        loading.value = true
        try {
            const { data } = await apiRequest.get('/v3/debt-collect-curator/analytic/collectors', {
                params: {
                    page: pagination.current
                }
            })
            collectors.value = data.data
            pagination.current = data.current_page
            pagination.total = data.total
            pagination.pageSize = data.per_page
            loading.value = false
        } catch (e) {
            loading.value = false
            console.log(e)
        }
    }
    onBeforeMount(loadCollectors)
    const onChange = (newPagination) => {
        pagination.current = newPagination.current
        loadCollectors()
    }
  </script>