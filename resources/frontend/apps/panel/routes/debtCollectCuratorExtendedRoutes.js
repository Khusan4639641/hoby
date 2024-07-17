export default [
  {
    path: 'debt-collect-curator-extended',
    redirect: { name: 'debt-collect-curator-extended-collectors' },
  },
  {
    path: "debt-collect-curator-extended/collectors",
    component: () => import("../views/pages/debt-collect/curator-extended/CollectorsPage"),
    name: 'debt-collect-curator-extended-collectors',
    meta: {
      title: 'Коллекторы - Старший Куратор Коллекторов',
      requireAuth: true,
    }
  },
  {
    path: "debt-collect-curator-extended/collectors/:collectorId",
    component: () => import("../views/pages/debt-collect/curator-extended/CollectorPage"),
    name: 'debt-collect-curator-extended-collector',
    meta: {
      title: 'Коллектор - Старший Куратор Коллекторов',
      requireAuth: true,
    }
  },
  {
    path: "debt-collect-curator-extended/analytic/collectors",
    component: () => import("../views/pages/debt-collect/curator-extended/AnalyticCollectorsPage"),
    name: 'debt-collect-leader-analytic-collectors',
    meta: {
      title: 'Аналитика Коллекторов - Руководитель Взыскания',
      requireAuth: true,
    }
  },
  {
    path: "debt-collect-curator-extended/analytic/debtors",
    component: () => import("../views/pages/debt-collect/curator-extended/AnalyticDebtorsPage"),
    name: 'debt-collect-curator-extended-analytic-debtors',
    meta: {
      title: 'Аналитика Должников - Старший Куратор Коллекторов',
      requireAuth: true,
    }
  },
  {
    path: "debt-collect-curator-extended/debtors",
    component: () => import("../views/pages/debt-collect/curator-extended/DebtorsPage"),
    name: 'debt-collect-curator-extended-debtors',
    meta: {
      title: 'Должники - Старший Куратор Коллекторов',
      requireAuth: true,
    }
  },
  {
    path: "debt-collect-curator-extended/debtors/:debtorId",
    component: () => import("../views/pages/debt-collect/curator-extended/DebtorPage"),
    name: 'debt-collect-curator-extended-debtor',
    meta: {
      title: 'Должник - Старший Куратор Коллекторов',
      requireAuth: true,
    }
  },
  {
    path: "debt-collect-curator-extended/contracts/:contractId",
    component: () => import("../views/pages/debt-collect/curator-extended/ContractPage"),
    name: 'debt-collect-curator-extended-contract',
    meta: {
      title: 'Контракт - Старший Куратор Коллекторов',
      requireAuth: true,
    }
  },
]
