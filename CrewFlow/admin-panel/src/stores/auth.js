import { defineStore } from 'pinia'
import axios from 'axios'
import { buildBaseUrl } from '@/api/client'

const STORAGE_KEY = 'crewflow_auth'

function loadPersisted() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    return raw ? JSON.parse(raw) : null
  } catch {
    return null
  }
}

function persist(state) {
  localStorage.setItem(
    STORAGE_KEY,
    JSON.stringify({
      companyCode: state.companyCode,
      token: state.token,
      user: state.user,
    }),
  )
}

export const useAuthStore = defineStore('auth', {
  state: () => {
    const saved = loadPersisted()
    return {
      companyCode: saved?.companyCode || null,
      token: saved?.token || null,
      user: saved?.user || null,
      loginError: null,
      isLoggingIn: false,
    }
  },

  getters: {
    isAuthenticated: (state) => !!state.token,
    permissions: (state) => state.user?.permissions || [],
  },

  actions: {
    can(permission) {
      return this.permissions.includes(permission)
    },

    async login({ companyCode, email, password }) {
      this.isLoggingIn = true
      this.loginError = null

      try {
        const baseURL = buildBaseUrl(companyCode)
        const response = await axios.post(
          `${baseURL}/auth/login`,
          { email, password },
          { headers: { Accept: 'application/json' } },
        )

        this.companyCode = companyCode
        this.token = response.data.data.token
        this.user = response.data.data.user

        persist(this)

        return true
      } catch (error) {
        this.loginError =
          error.response?.data?.message || 'Login failed. Please check your details and try again.'
        return false
      } finally {
        this.isLoggingIn = false
      }
    },

    logout() {
      this.companyCode = null
      this.token = null
      this.user = null
      localStorage.removeItem(STORAGE_KEY)
    },
  },
})
