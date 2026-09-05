import axios from 'axios'
import { useAuthStore } from '@/stores/auth'

const API_ROOT_DOMAIN = import.meta.env.VITE_API_ROOT_DOMAIN || 'crewflow.localhost:8000'
const API_SCHEME = import.meta.env.VITE_API_SCHEME || 'http'

export function buildBaseUrl(companyCode) {
  return `${API_SCHEME}://${companyCode}.${API_ROOT_DOMAIN}/api`
}

const client = axios.create()

client.interceptors.request.use((config) => {
  const auth = useAuthStore()

  if (auth.companyCode) {
    config.baseURL = buildBaseUrl(auth.companyCode)
  }

  if (auth.token) {
    config.headers.Authorization = `Bearer ${auth.token}`
  }

  config.headers.Accept = 'application/json'

  return config
})

client.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      const auth = useAuthStore()
      auth.logout()
    }
    return Promise.reject(error)
  },
)

export default client
