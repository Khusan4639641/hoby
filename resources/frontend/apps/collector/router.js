import { createRouter, createWebHistory } from "vue-router"
import { useAuthStore } from './stores/authStore'
import baseRoutes from "./routes/baseRoutes"

const router = createRouter({
	history: createWebHistory(),
	routes: [
		{
			path: "/ru/collector",
			name:"main-page",
			component: () => import('./views/layouts/BaseLayout'),
			children: baseRoutes
		},
		{
			path: "/ru/collector/auth",
			component: () => import('./views/layouts/UnsignedLayout'),
			children: [
				{
					path: "login",
					component: () => import("./views/pages/LoginPage"),
					name: 'login',
					meta: {
					  title: 'Вход'
					}
				},
				{
					path: "logout",
					component: () => import("./views/pages/LogoutPage"),
					name: 'logout',
					meta: {
					  title: 'Выход',
					  requireAuth: true,
					}
				},
			]
		},
	],
})

router.beforeEach(async (to, from, next) => {
	const authStore = useAuthStore()
	if(authStore.user === undefined) await authStore.init()

	if(to.name === 'login' && authStore.user !== null) {
		next({ name: 'main-page' })
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