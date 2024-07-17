import axios from 'axios'
// TODO: Настроить alias (Greydius)
import { API_URL } from '../configs/apiConfig'
import * as dataStorage from '../utils/dataStorage'

const token = dataStorage.get('token')

export default axios.create({
  baseURL: API_URL,
  headers: {
    Authorization: `Bearer ${token}`,
    'Content-Language': 'ru'
  },
})
