import axios from 'axios'
import { useAuthStore } from '@/stores/auth'

// The root domain shifts start after the company code, e.g.
// company code "acme2024" + root domain "crewflow.localhost:8000"
// -> https://acme2024.crewflow.localhost:8000/api
const API_ROOT_DOMAIN = import.meta.env.VITE_API_ROOT_DOMAIN || 'crewflow.localhost:8000'
const API_SCHEME = import.meta.env.VITE_API_SCHEME || 'http'

export function buildBaseUrl(companyCode) {
  return `${API_SCHEME}://${companyCode}.${API_ROOT_DOMAIN}/api`
}

const client = axios.create()

// The base URL depends on which company is logged in, so it's set
// per-request rather than once at creation time.
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

// A 401 anywhere means the token is no longer valid — log out and let
// the router guard redirect to /login.
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
