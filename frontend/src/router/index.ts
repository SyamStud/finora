import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/login',
    name: 'login',
    component: () => import('../views/LoginView.vue'),
    meta: { guest: true, title: 'Masuk' },
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('../views/RegisterView.vue'),
    meta: { guest: true, title: 'Daftar' },
  },
  {
    path: '/',
    component: () => import('../layouts/AppLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      { path: '', name: 'dashboard', component: () => import('../views/DashboardView.vue'), meta: { title: 'Ringkasan' } },
      { path: 'transactions', name: 'transactions', component: () => import('../views/TransactionsView.vue'), meta: { title: 'Transaksi' } },
      { path: 'pending', name: 'pending', component: () => import('../views/PendingTransactionsView.vue'), meta: { title: 'Transaksi Pending' } },
      { path: 'accounts', name: 'accounts', component: () => import('../views/AccountsView.vue'), meta: { title: 'Rekening' } },
      { path: 'accounts/:id', name: 'account-detail', component: () => import('../views/AccountDetailView.vue'), meta: { title: 'Detail Rekening' } },
      { path: 'categories', name: 'categories', component: () => import('../views/CategoriesView.vue'), meta: { title: 'Kategori' } },
      { path: 'assets', name: 'assets', component: () => import('../views/AssetsView.vue'), meta: { title: 'Aset' } },
      { path: 'budgets', name: 'budgets', component: () => import('../views/BudgetsView.vue'), meta: { title: 'Budget' } },
      { path: 'settings', name: 'settings', component: () => import('../views/SettingsView.vue'), meta: { title: 'Pengaturan' } },
    ],
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('../views/NotFoundView.vue'),
  },
]

export const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: () => ({ top: 0 }),
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (!auth.ready) {
    await auth.initialize()
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta.guest && auth.isAuthenticated) {
    return { name: 'dashboard' }
  }

  return true
})

router.afterEach((to) => {
  const title = (to.meta.title as string | undefined) ?? 'Finora'
  document.title = `${title} · Finora`
})
