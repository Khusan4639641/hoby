<template>
    <a-config-provider :locale="locale">
      <div class="layout base-layout">
        <router-view />
      </div>
    </a-config-provider>
</template>
<script setup>
  import { reactive } from 'vue'
  import ruRU from 'ant-design-vue/es/locale/ru_RU';
  const locale = reactive(ruRU)
</script>
<style lang="scss" scoped>
.base-layout {
  display: flex;
  padding-top: 80px;
}
</style>