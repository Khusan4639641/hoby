// TODO: Настроить alias (Greydius)
import { LOCAL_STORAGE_PREFIX } from '../configs/storageConfig'

export function get(key) {
  return JSON.parse(localStorage.getItem(LOCAL_STORAGE_PREFIX + ':' + key))
}

export function set(key, value) {
  return localStorage.setItem(LOCAL_STORAGE_PREFIX + ':' + key, JSON.stringify(value))
}

export function remove(key) {
  return localStorage.removeItem(LOCAL_STORAGE_PREFIX + ':' + key)
}