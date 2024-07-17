export default [
  {
    path: 'debt-collect-leader',
    redirect: { name: 'debt-collect-leader-curators' },
  },
  {
    path: "debt-collect-leader/curators",
    component: () => import("../views/pages/debt-collect/leader/CuratorsPage"),
    name: 'debt-collect-leader-curators',
    meta: {
      title: 'Кураторы - Руководитель Взыскания',
      requireAuth: true,
    }
  },
  {
    path: "debt-collect-leader/collectors",
    component: () => import("../views/pages/debt-collect/leader/CollectorsPage"),
    name: 'debt-collect-leader-collectors',
    meta: {
      title: 'Коллекторы - Руководитель Взыскания',
      requireAuth: true,
    }
  },
  {
    path: "debt-collect-leader/collectors/:collectorId",
    component: () => import("../views/pages/debt-collect/leader/CollectorPage"),
    name: 'debt-collect-leader-collector',
    meta: {
      title: 'Коллектор - Руководитель Взыскания',
      requireAuth: true,
    }
  },
  {
    path: "debt-collect-leader/analytic/collectors",
    component: () => import("../views/pages/debt-collect/leader/AnalyticCollectorsPage"),
    name: 'debt-collect-leader-analytic',
    meta: {
      title: 'Аналитика Коллекторов - Руководитель Взыскания',
      requireAuth: true,
    }
  },
  {
    path: "debt-collect-leader/analytic/debtors",
    component: () => import("../views/pages/debt-collect/leader/AnalyticDebtorsPage"),
    name: 'debt-collect-leader-analytic-debtors',
    meta: {
      title: 'Аналитика Должников - Руководитель Взыскания',
      requireAuth: true,
    }
  },
  {
    path: "debt-collect-leader/analytic/letters",
    component: () => import("../views/pages/debt-collect/leader/AnalyticLettersPage"),
    name: 'debt-collect-leader-analytic-letters',
    meta: {
      title: 'Аналитика Писем - Руководитель Взыскания',
      requireAuth: true,
    }
  },
  {
    path: "debt-collect-leader/debtors",
    component: () => import("../views/pages/debt-collect/leader/DebtorsPage"),
    name: 'debt-collect-leader-debtors',
    meta: {
      title: 'Должники - Руководитель Взыскания',
      requireAuth: true,
    }
  },
  {
    path: "debt-collect-leader/debtors/:debtorId",
    component: () => import("../views/pages/debt-collect/leader/DebtorPage"),
    name: 'debt-collect-leader-debtor',
    meta: {
      title: 'Должник - Руководитель Взыскания',
      requireAuth: true,
    }
  },
  {
    path: "debt-collect-leader/contracts/:contractId",
    component: () => import("../views/pages/debt-collect/leader/ContractPage"),
    name: 'debt-collect-leader-contract',
    meta: {
      title: 'Контракт Должник - Руководитель Взыскания',
      requireAuth: true,
    }
  },
]
