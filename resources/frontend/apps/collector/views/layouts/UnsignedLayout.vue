<template>
  <div class="layout base-layout">
    <a-layout >
        <a-layout-content :style="{ margin: '0 16px 0' }">
          <router-view />
        </a-layout-content>
    </a-layout>
  </div>
</template>
<style lang="scss" scoped>
.base-layout {
  display: flex;
  
}
.login-layout-content {
  display: flex;
  align-items: center;
}

</style>
