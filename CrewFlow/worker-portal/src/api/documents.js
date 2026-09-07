import client from '@/api/client'

export function fetchMyDocuments() {
  return client.get('documents').then((r) => r.data.data)
}

export function uploadDocument({ documentType, file, documentNumber, issuedAt, expiresAt }) {
  const formData = new FormData()
  formData.append('document_type', documentType)
  formData.append('file', file)
  if (documentNumber) formData.append('document_number', documentNumber)
  if (issuedAt) formData.append('issued_at', issuedAt)
  if (expiresAt) formData.append('expires_at', expiresAt)

  return client
    .post('documents', formData, { headers: { 'Content-Type': 'multipart/form-data' } })
    .then((r) => r.data.data)
}
