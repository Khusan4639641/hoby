import { defineStore } from 'pinia'
import apiRequest from '../utils/apiRequest'
import * as dataStorage from '../utils/dataStorage'

export const useAuthStore = defineStore('auth', {
  state: () => {
    return {
      user: undefined
    }
  },
  actions: {
    auth(formData) {
      return new Promise((resolve, reject) => {
        apiRequest.post('/v3/login/auth', formData)
          .then(({ data }) => {
            // TODO: Заменить при фиксе legacy-response
            if (data.status === 'success') {
              dataStorage.set('token', data.data.api_token)
              apiRequest.defaults.headers.Authorization = `Bearer ${data.data.api_token}`

              this.user = data.data
              resolve()
            } else if (data.status === 'error') {
              reject(data.error)
            }
          })
          .catch((error) => {
            console.log(error, error.response)
            reject(error.response?.data?.error ?? [{ text: 'Неизвестная ошибка' }])
          })

      })
    },
    async init() {
      const token = dataStorage.get('token')
      apiRequest.defaults.headers.Authorization = `Bearer ${token}`
      try {
        const { data } = await apiRequest.get('/v3/me')
        this.user = data.data
      } catch (error) {
        this.user = null
        dataStorage.remove('token')
        console.log(error)
      }

    },
    async logout() {
      this.user = undefined
      dataStorage.remove('token')
    }
  }
})