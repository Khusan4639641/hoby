import { createRouter, createWebHistory } from "vue-router"
import { useAuthStore } from './stores/authStore'
import catalogCategoriesRoutes from "./routes/catalogCategoriesRoutes"
import debtCollectLeaderRoutes from "./routes/debtCollectLeaderRoutes"
import debtCollectCuratorExtendedRoutes from "./routes/debtCollectCuratorExtendedRoutes"
import debtCollectCuratorRoutes from "./routes/debtCollectCuratorRoutes"

const router = createRouter({
	history: createWebHistory(),
	routes: [
		{
			path: "/ru/panel",
			component: () => import('./views/layouts/BaseLayout'),
			children: [
                ...catalogCategoriesRoutes,
                ...debtCollectLeaderRoutes,
                ...debtCollectCuratorExtendedRoutes,
                ...debtCollectCuratorRoutes
            ]
		},
	],
})

router.beforeEach(async (to, from, next) => {
	const authStore = useAuthStore()
	if(authStore.user === undefined) await authStore.init()

	if(to.name === 'login' && authStore.user !== null) {
		next({ name: 'dashboard' })
		return
	}

	if(to.meta.requireAuth && authStore.user === null) {
		next({ name: 'login' })
		return
	}

	document.title = `${to.meta.title} - test Collector`
	next()
})

export default router
