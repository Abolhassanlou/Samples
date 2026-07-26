const API_BASE_URL = (import.meta.env.VITE_API_BASE_URL ?? 'http://localhost/api/v1').replace(
  /\/$/,
  '',
)

export type ValidationErrors = Record<string, string[]>

type ApiErrorPayload = {
  message?: string
  errors?: ValidationErrors
}

type ApiRequestOptions = Omit<RequestInit, 'headers'> & {
  token?: string | null
  headers?: HeadersInit
}

export class ApiError extends Error {
  readonly status: number
  readonly errors: ValidationErrors

  constructor(message: string, status: number, errors: ValidationErrors = {}) {
    super(message)

    this.name = 'ApiError'
    this.status = status
    this.errors = errors
  }
}

export async function apiRequest<T>(path: string, options: ApiRequestOptions = {}): Promise<T> {
  const headers = new Headers(options.headers)

  headers.set('Accept', 'application/json')

  if (options.body && !(options.body instanceof FormData)) {
    headers.set('Content-Type', 'application/json')
  }

  if (options.token) {
    headers.set('Authorization', `Bearer ${options.token}`)
  }

  const response = await fetch(`${API_BASE_URL}${path}`, {
    ...options,
    headers,
  })

  let data: unknown = null

  const contentType = response.headers.get('content-type')

  if (response.status !== 204 && contentType?.includes('application/json')) {
    data = await response.json()
  }

  if (!response.ok) {
    const payload = data as ApiErrorPayload | null

    throw new ApiError(
      payload?.message ?? 'An unexpected error occurred.',
      response.status,
      payload?.errors,
    )
  }

  return data as T
}
