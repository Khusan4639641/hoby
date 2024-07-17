<template>
  <header class="header">
    <a-row justify="between">
      <a-col :span="12">
        <div class="header__element">
          test
        </div>
      </a-col>
      <a-col>
        <div class="header__element">
          <div class="lang-selector header__lang-selector">
            <button class="lang-selector__lang">
              <img src="lang-selector__lang-icon" alt="">
              <div class="lang-selector__lang-name">RU</div>
            </button>
          </div>
        </div>  
      </a-col>
    </a-row>
    
  </header>
</template>

<script>
export default {

}
</script>

<style lang="scss" scoped>
.header {

  &__element {

  }
  &__logo {

  }
  &__lang-selector {

  }
}

.lang-selector {
  &__lang {
    &__lang-icon {

    }
    &__lang-name {
      
    }
  }
}
</style>