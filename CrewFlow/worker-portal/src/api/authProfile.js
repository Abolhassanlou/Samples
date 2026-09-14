import client from '@/api/client'

/**
 * Name and phone live on the User (Authentication module), not Worker
 * (Employee module) — this is the one self-service endpoint for editing
 * them, separate from everything else on the Personal details form.
 */
export function updateMe(payload) {
  return client.put('auth/me', payload).then((r) => r.data.data)
}
