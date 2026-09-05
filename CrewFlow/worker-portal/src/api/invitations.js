import axios from 'axios'
import { buildBaseUrl } from '@/api/client'

/**
 * These two calls happen BEFORE the worker has any credentials, so they
 * can't go through the normal `client` instance (which reads companyCode
 * from the auth store — not set yet). The company code comes straight
 * from the URL's own `company=` query param instead (see this app's
 * README and the Employee module's README for why the invite link
 * carries both `token` and `company`).
 */

export function fetchInvitation(companyCode, token) {
  const baseURL = buildBaseUrl(companyCode)
  return axios
    .get(`${baseURL}/invitations/${token}`, { headers: { Accept: 'application/json' } })
    .then((r) => r.data.data)
}

export function acceptInvitation(companyCode, token, { name, phone, password }) {
  const baseURL = buildBaseUrl(companyCode)
  return axios
    .post(
      `${baseURL}/invitations/${token}/accept`,
      { name, phone, password, password_confirmation: password },
      { headers: { Accept: 'application/json' } },
    )
    .then((r) => r.data.data)
}
