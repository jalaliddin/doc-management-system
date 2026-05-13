import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: () => import('@/views/HomeView.vue'),
  },
  {
    path: '/department/:id/new',
    name: 'DocumentForm',
    component: () => import('@/views/DocumentForm.vue'),
    props: true,
  },
  {
    path: '/admin/login',
    name: 'AdminLogin',
    component: () => import('@/views/AdminLogin.vue'),
  },
  {
    path: '/admin',
    redirect: '/admin/departments',
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/departments',
    name: 'AdminDepartments',
    component: () => import('@/views/admin/DepartmentManager.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/organizations',
    name: 'AdminOrganizations',
    component: () => import('@/views/admin/OrganizationManager.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/signatories',
    name: 'AdminSignatories',
    component: () => import('@/views/admin/SignatoryManager.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/templates',
    name: 'AdminTemplates',
    component: () => import('@/views/admin/TemplateManager.vue'),
    meta: { requiresAuth: true },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to) => {
  const auth = useAuthStore()
  if (to.meta.requiresAuth && !auth.isLoggedIn) {
    return { name: 'AdminLogin' }
  }
  if (to.name === 'AdminLogin' && auth.isLoggedIn) {
    return { name: 'AdminDepartments' }
  }
})

export default router
