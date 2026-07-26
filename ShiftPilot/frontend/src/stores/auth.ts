import { computed, ref } from 'vue'
import { defineStore } from 'pinia'

import { ApiError, apiRequest } from '@/services/api'

import type {
  AuthUser,
  CurrentUserResponse,
  LoginCredentials,
  LoginResponse,
  LogoutResponse,
} from '@/types/auth'

const TOKEN_STORAGE_KEY = 'shiftpilot_auth_token'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<AuthUser | null>(null)

  const token = ref<string | null>(localStorage.getItem(TOKEN_STORAGE_KEY))

  const isLoading = ref(false)

  const isAuthenticated = computed(() => token.value !== null)

  const displayName = computed(() => {
    if (!user.value) {
      return ''
    }

    return user.value.preferred_name || `${user.value.first_name} ${user.value.last_name}`
  })

  function clearSession(): void {
    user.value = null
    token.value = null

    localStorage.removeItem(TOKEN_STORAGE_KEY)
  }

  async function login(credentials: LoginCredentials): Promise<AuthUser> {
    isLoading.value = true

    try {
      const response = await apiRequest<LoginResponse>('/auth/login', {
        method: 'POST',
        body: JSON.stringify(credentials),
      })

      user.value = response.data.user
      token.value = response.data.token

      localStorage.setItem(TOKEN_STORAGE_KEY, response.data.token)

      return response.data.user
    } finally {
      isLoading.value = false
    }
  }

  async function fetchCurrentUser(): Promise<AuthUser | null> {
    if (!token.value) {
      user.value = null

      return null
    }

    isLoading.value = true

    try {
      const response = await apiRequest<CurrentUserResponse>('/auth/me', {
        token: token.value,
      })

      user.value = response.data.user

      return response.data.user
    } catch (error: unknown) {
      if (error instanceof ApiError && error.status === 401) {
        clearSession()
      }

      throw error
    } finally {
      isLoading.value = false
    }
  }

  async function logout(): Promise<void> {
    isLoading.value = true

    try {
      if (token.value) {
        await apiRequest<LogoutResponse>('/auth/logout', {
          method: 'POST',
          token: token.value,
        })
      }
    } finally {
      clearSession()
      isLoading.value = false
    }
  }

  return {
    user,
    token,
    isLoading,
    isAuthenticated,
    displayName,
    login,
    logout,
    fetchCurrentUser,
    clearSession,
  }
})
