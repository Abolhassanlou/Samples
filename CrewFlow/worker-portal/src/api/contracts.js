import client from '@/api/client'

export function fetchContracts(userId) {
  return client.get(`users/${userId}/contracts`).then((r) => r.data.data)
}

export function signContract(userId, contractId) {
  return client.post(`users/${userId}/contracts/${contractId}/sign`).then((r) => r.data.data)
}

/**
 * The download endpoint returns the raw file, not JSON, and needs the
 * Bearer token attached — a plain <a href> can't carry an auth header,
 * so this fetches it as a blob and triggers the browser's own download
 * via a temporary link.
 */
export async function downloadContract(userId, contractId, filename = 'contract') {
  const response = await client.get(`users/${userId}/contracts/${contractId}/download`, {
    responseType: 'blob',
  })

  const url = window.URL.createObjectURL(new Blob([response.data]))
  const link = document.createElement('a')
  link.href = url
  link.download = filename
  document.body.appendChild(link)
  link.click()
  link.remove()
  window.URL.revokeObjectURL(url)
}
