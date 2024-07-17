// TODO: Настроить alias (Greydius)
import { LOCAL_STORAGE_PREFIX } from '../configs/storageConfig'

export function get(key) {
  // TODO: Убрать, когда фронт отделится от старого бэка
  if(key === 'token') {
    return window.globalApiToken
  }

  return JSON.parse(localStorage.getItem(LOCAL_STORAGE_PREFIX + ':' + key))
}

export function set(key, value) {
  return localStorage.setItem(LOCAL_STORAGE_PREFIX + ':' + key, JSON.stringify(value))
}

export function remove(key) {
  return localStorage.removeItem(LOCAL_STORAGE_PREFIX + ':' + key)
}
