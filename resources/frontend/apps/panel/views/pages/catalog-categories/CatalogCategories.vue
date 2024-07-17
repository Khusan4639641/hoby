<template>
  <a-layout class="page dashboard-page">
    <a-page-header title="Категории" sub-title="Список всех родительских категорий"/>
    <a-layout-content>
      <CatalogSubCategoriesTable :parent-category-id="0" />
    </a-layout-content>
  </a-layout>
</template>

<script setup>
import CatalogSubCategoriesTable from './CatalogSubCategoriesTable'
</script>
