export default [
  {
    path: "catalog-categories",
    component: () => import("../views/pages/catalog-categories/CatalogCategories"),
    name: 'catalog-categories',
    meta: {
      title: 'Работа с категориями',
      requireAuth: true,
    }
  },
  {
    path: "catalog-categories/:catalogCategoryId",
    component: () => import("../views/pages/catalog-categories/CatalogCategory"),
    name: 'catalog-category',
    meta: {
      title: 'Работа с категорией',
      requireAuth: true,
    }
  },
]
