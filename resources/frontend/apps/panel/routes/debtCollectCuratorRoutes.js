export default [
  {
    path: 'debt-collect-curator',
    redirect: { name: 'debt-collect-curator-collectors' },
  },
  {
    path: "debt-collect-curator/collectors",
    component: () => import("../views/pages/debt-collect/curator/CollectorsPage"),
    name: 'debt-collect-curator-collectors',
    meta: {
      title: 'Коллекторы - Куратор Коллекторов',
      requireAuth: true,
    }
  },
  {
    path: "debt-collect-curator/collectors/:collectorId",
    component: () => import("../views/pages/debt-collect/curator/CollectorPage"),
    name: 'debt-collect-curator-collector',
    meta: {
      title: 'Коллектор - Куратор Коллекторов',
      requireAuth: true,
    }
  },
  {
    path: "debt-collect-curator/analytic/collectors",
    component: () => import("../views/pages/debt-collect/curator/AnalyticCollectorsPage"),
    name: 'debt-collect-leader-analytic-collectors',
    meta: {
      title: 'Аналитика Коллекторов - Руководитель Взыскания',
      requireAuth: true,
    }
  },
  {
    path: "debt-collect-curator/analytic/debtors",
    component: () => import("../views/pages/debt-collect/curator/AnalyticDebtorsPage"),
    name: 'debt-collect-curator-analytic-debtors',
    meta: {
      title: 'Аналитика Должников - Куратор Коллекторов',
      requireAuth: true,
    }
  },
  {
    path: "debt-collect-curator/debtors/:debtorId",
    component: () => import("../views/pages/debt-collect/curator/DebtorPage"),
    name: 'debt-collect-curator-debtor',
    meta: {
      title: 'Должник - Куратор Коллекторов',
      requireAuth: true,
    }
  },
  {
    path: "debt-collect-curator/contracts/:contractId",
    component: () => import("../views/pages/debt-collect/curator/ContractPage"),
    name: 'debt-collect-curator-contract',
    meta: {
      title: 'Контракт - Куратор Коллекторов',
      requireAuth: true,
    }
  },
]
