import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { authApi } from '../api'
import { clearToken, getToken, setToken } from '../api/client'
import type { User } from '../types'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(getToken())
  const ready = ref(false)

  const isAuthenticated = computed(() => Boolean(token.value))

  async function initialize(): Promise<void> {
    if (!token.value) {
      ready.value = true
      return
    }
    try {
      const response = await authApi.me()
      user.value = response.user
    } catch {
      clearToken()
      token.value = null
      user.value = null
    } finally {
      ready.value = true
    }
  }

  async function login(email: string, password: string): Promise<void> {
    const response = await authApi.login(email, password)
    applyAuth(response)
  }

  async function register(name: string, email: string, password: string): Promise<void> {
    const response = await authApi.register(name, email, password, password)
    applyAuth(response)
  }

  async function logout(): Promise<void> {
    try {
      await authApi.logout()
    } catch {
      // logout tetap menghapus token lokal walau server gagal
    }
    clearToken()
    token.value = null
    user.value = null
  }

  function applyAuth(response: { user: User; token: string }): void {
    setToken(response.token)
    token.value = response.token
    user.value = response.user
    ready.value = true
  }

  function reset(): void {
    clearToken()
    token.value = null
    user.value = null
  }

  return { user, token, ready, isAuthenticated, initialize, login, register, logout, reset }
})
