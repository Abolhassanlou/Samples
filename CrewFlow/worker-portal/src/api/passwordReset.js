import axios from 'axios'
import { buildBaseUrl } from '@/api/client'

/**
 * Both calls happen before the worker has any session — same reasoning
 * as api/invitations.js, so they go straight through axios with an
 * explicit baseURL rather than the normal `client` instance.
 */

export function requestPasswordReset(companyCode, email) {
  const baseURL = buildBaseUrl(companyCode)
  return axios
    .post(
      `${baseURL}/auth/forgot-password`,
      { email, redirect_url: `${window.location.origin}/reset-password` },
      { headers: { Accept: 'application/json' } },
    )
    .then((r) => r.data)
}

export function resetPassword(companyCode, { email, token, password }) {
  const baseURL = buildBaseUrl(companyCode)
  return axios
    .post(
      `${baseURL}/auth/reset-password`,
      { email, token, password, password_confirmation: password },
      { headers: { Accept: 'application/json' } },
    )
    .then((r) => r.data)
}
