import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/plugins/axios'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('admin_token') || null)
  const admin = ref(JSON.parse(localStorage.getItem('admin_user') || 'null'))

  const isLoggedIn = computed(() => !!token.value)

  async function login(username, password) {
    const response = await api.post('/admin/login', { username, password })
    token.value = response.data.token
    admin.value = response.data.admin
    localStorage.setItem('admin_token', token.value)
    localStorage.setItem('admin_user', JSON.stringify(admin.value))
    return response.data
  }

  async function logout() {
    try {
      await api.post('/admin/logout')
    } finally {
      token.value = null
      admin.value = null
      localStorage.removeItem('admin_token')
      localStorage.removeItem('admin_user')
    }
  }

  return { token, admin, isLoggedIn, login, logout }
})
