import { createRouter, createWebHistory } from 'vue-router'
import useAuth from '../composables/useAuth'

const HomeView = () => import('../views/HomeView.vue')
const LoginView = () => import('../views/LoginView.vue')

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomeView,
      meta: { requiresAuth: true },
    },
    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta: { guestOnly: true },
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: '/',
    },
  ],
  scrollBehavior() {
    return { top: 0 }
  },
})

router.beforeEach((to, _from, next) => {
  const auth = useAuth()

  if (to.meta?.requiresAuth && !auth.isAuthenticated.value) {
    const redirect = to.fullPath && to.fullPath !== '/' ? to.fullPath : undefined
    next({ name: 'login', ...(redirect ? { query: { redirect } } : {}) })
    return
  }

  if (to.meta?.guestOnly && auth.isAuthenticated.value) {
    next({ name: 'home' })
    return
  }

  next()
})

export default router
