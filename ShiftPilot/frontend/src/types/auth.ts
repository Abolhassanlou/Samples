export type AuthUser = {
  id: number
  first_name: string
  last_name: string
  preferred_name: string | null
  email: string
  email_verified_at: string | null
  created_at: string
  updated_at: string
}

export type LoginCredentials = {
  email: string
  password: string
  device_name: string
}

export type LoginResponse = {
  message: string
  data: {
    user: AuthUser
    token: string
  }
}

export type CurrentUserResponse = {
  data: {
    user: AuthUser
  }
}

export type LogoutResponse = {
  message: string
}
