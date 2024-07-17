<template>
  <a-config-provider :locale="locale">
    <div class="layout base-layout">
      <a-layout v-if="authStore?.user?.phone">
        <a-layout-sider 
          breakpoint="lg"
          :trigger="null"
          collapsed-width="0"
          collapsible
          v-model:collapsed="siderCollapsed"
          theme="light"
        >
        <div class="logo" >
          <router-link :to="{ name: 'dashboard' }">
            <img src="../../assets/images/logo-test.svg" alt="test Logo">
          </router-link>
        </div>
        <div class="user-data">
          <a-space align="start" direction="vertical">
          <a-space align="start">
            <a-typography-title style="color: #fff;" :level="5"> ID:</a-typography-title>
            <a-typography-title v-if="authStore?.user?.id" style="color: #fff;" :level="5"> {{authStore?.user?.id }}</a-typography-title>
            <a-typography-title v-else style="color: #ffffff54;" :level="5"> Нет ID </a-typography-title>
          </a-space>
          <a-space align="start">
            <userOutlined style="color: #fff;"/>
            <a-typography-title v-if="authStore?.user?.full_name" style="color: #fff;" :level="5"> {{authStore?.user?.full_name }}</a-typography-title>
            <a-typography-title v-else style="color: #ffffff54;" :level="5"> Нет Ф.И.О </a-typography-title>
          </a-space>
          <a-space align="start">
            <phoneOutlined style="color: #fff;"/>
            <a-typography-title v-if="authStore?.user?.phone" style="color: #fff;" :level="5"> {{authStore?.user?.phone.replace('+','')}} </a-typography-title>
            <a-typography-title v-else style="color: #ffffff54;" :level="5"> Нет номера</a-typography-title>

          </a-space>
          </a-space>
        </div>
        
        <a-menu @click="goTo('dashboard')" :selectedKeys="selectedKeys" theme="light" mode="inline">
          <a-menu-item key="dashboard">
            <user-outlined />
            <span class="nav-text">Главная страница</span>
          </a-menu-item>
        </a-menu>
        </a-layout-sider>
        <a-layout >
          <a-layout-header theme="light" :style="{ background: '#fff', padding: '0 16px'}">
            <a-button  type="primary" shape="round" @click="siderCollapsed = !siderCollapsed">
              <menu-unfold-outlined v-if="siderCollapsed"/>
              <menu-fold-outlined v-else/>
            </a-button>
            <a-space :style="{float: 'right', lineHeight: 0, height: '64px'}" align="center">
              <a-space align="end" direction="vertical" style="gap: 22px">
                <a-typography-text strong type="secondary" style="color:#1E1E1E;" :level="5">Сумма вознаграждения:</a-typography-text>
                <a-typography-text v-if="authStore?.user?.remunerations" strong style="font-size: 20px; color: var(--antd-wave-shadow-color)" :level="4">{{authStore?.user?.remunerations }}</a-typography-text>
                <a-typography-text v-else strong style="font-size: 20px; color: #ff764373" :level="4">0.00</a-typography-text>
              </a-space>
              <a-divider  type="vertical"/>
              <router-link :to="{ name: 'logout' }">
                <a-button type="link" size="large" primary>
                  <template #icon>
                    <LogoutOutlined /> 
                  </template>Выйти
                </a-button>
              </router-link>
            </a-space>
          </a-layout-header>
          <a-layout-content :style="{ margin: '0 16px 0' }">
            <router-view />
          </a-layout-content>
        </a-layout>
      </a-layout>
      <div v-else class="loading-layout">
        <loading-outlined />
      </div>
    </div>
</a-config-provider>

</template>

<script setup>
import { useRouter, useRoute } from 'vue-router'
import { ref, computed, reactive } from "vue";
import ruRU from 'ant-design-vue/es/locale/ru_RU';
import { useAuthStore } from '../../stores/authStore'

const locale = reactive(ruRU)
const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const siderCollapsed = ref(false)
const selectedKeys = computed(()=> [route.name])
const goTo = (routeName)=>{ router.push({ name: routeName})}

</script>
<style lang="scss" scoped>
.base-layout {
  display: flex;
  
}
.login-layout-content {
  display: flex;
  align-items: center;
}
.logo {
  width: 100%;
  padding: 16px 16px 16px 24px;
  height: 64px;
  align-items: center;
  display: inline-flex;
}
.user-data {
  background: var(--antd-wave-shadow-color);
  padding: 24px;
}
.ant-layout-sider-light .ant-layout-sider-zero-width-trigger{
  top: 11px !important;
}
.loading-layout {
  position: absolute;
  top: 0;
  left: 0;
  height: 100vh;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  span {
    font-size: 36px;
    color: var(--antd-wave-shadow-color);
  }
}
</style>
