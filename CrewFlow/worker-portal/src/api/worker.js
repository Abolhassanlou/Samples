import client from '@/api/client'

export function fetchWorker(userId) {
  return client.get(`users/${userId}/worker`).then((r) => r.data.data)
}

/**
 * Partial update — the backend treats every field as optional
 * ("sometimes"), so sending just the handful of keys one form section
 * owns (personal / address / bank) never touches the others.
 */
export function updateWorker(userId, partialPayload) {
  return client.put(`users/${userId}/worker`, partialPayload).then((r) => r.data.data)
}
