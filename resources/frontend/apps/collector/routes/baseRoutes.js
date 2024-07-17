// TODO: Добавить meta (Greydius)
export default [
  {
    path: "",
    component: () => import("../views/pages/DashboardPage"),
    name: 'dashboard',
    meta: {
      title: 'Выбор района',
      requireAuth: true,
    }
  },
  {
    path: "debtors",
    component: () => import("../views/pages/DebtorsPage"),
    name: 'debtors',
    meta: {
      title: 'Должники',
      requireAuth: true,
    }
  },
  {
    path: "debtor/:debtor",
    component: () => import("../views/pages/DebtorPage"),
    name: 'debtor',
    meta: {
      title: 'Должник',
      requireAuth: true,
    }
  },
  {
    path: "contracts/:contract",
    component: () => import("../views/pages/ContractPage"),
    name: 'contract',
    meta: {
      title: 'Контракт',
      requireAuth: true,
    }
  },
 
]