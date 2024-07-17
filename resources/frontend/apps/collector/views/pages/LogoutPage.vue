<template>
  <a-layout class="page logout-page">
    <div class="page__content">
      <h1> Выход...</h1>
    </div>
  </a-layout>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/authStore'

const authStore = useAuthStore()
const router = useRouter()

authStore.logout()
  .then(() => {
    router.push({ name: 'login' })
  })
</script>

<style lang="scss" scoped>
.page__content {
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  
}
.logout-page {
  min-height: 100vh;
  justify-content: center;
  align-items: center;
}
</style>