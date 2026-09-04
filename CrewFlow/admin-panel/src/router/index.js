import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import LoginView from '@/views/LoginView.vue'
import DashboardView from '@/views/DashboardView.vue'
import UsersView from '@/views/UsersView.vue'
import RolesView from '@/views/RolesView.vue'
import WorkersView from '@/views/WorkersView.vue'
import CreateWorkerView from '@/views/CreateWorkerView.vue'
import InviteWorkerView from '@/views/InviteWorkerView.vue'
import WorkerDetailView from '@/views/WorkerDetailView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta: { public: true },
    },
    {
      path: '/',
      name: 'dashboard',
      component: DashboardView,
    },
    {
      path: '/workers',
      name: 'workers',
      component: WorkersView,
    },
    {
      path: '/workers/invite',
      name: 'workers-invite',
      component: InviteWorkerView,
    },
    {
      path: '/workers/new',
      name: 'workers-new',
      component: CreateWorkerView,
    },
    {
      path: '/workers/:userId',
      name: 'workers-detail',
      component: WorkerDetailView,
    },
    {
      path: '/users',
      name: 'users',
      component: UsersView,
    },
    {
      path: '/roles',
      name: 'roles',
      component: RolesView,
    },
  ],
})

router.beforeEach((to) => {
  const auth = useAuthStore()

  if (!to.meta.public && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.name === 'login' && auth.isAuthenticated) {
    return { name: 'dashboard' }
  }

  return true
})

export default router
