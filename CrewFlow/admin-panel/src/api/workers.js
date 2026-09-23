import client from '@/api/client'

export function fetchWorkers(filters = {}) {
  const params = {}
  if (filters.search) params.search = filters.search
  if (filters.qualificationId) params.qualification_id = filters.qualificationId
  if (filters.branchId) params.branch_id = filters.branchId
  if (filters.contractType) params.contract_type = filters.contractType
  if (filters.workTimeModel) params.work_time_model = filters.workTimeModel
  if (filters.nightShift) params.night_shift = 1
  if (filters.eligibleOnly) params.eligible = 1
  if (filters.dayOfWeek !== '' && filters.dayOfWeek != null) params.day_of_week = filters.dayOfWeek
  if (filters.time) params.time = filters.time

  return client.get('workers', { params }).then((r) => r.data.data)
}

export function fetchQualifications() {
  return client.get('qualifications').then((r) => r.data.data)
}

export function fetchBranches() {
  return client.get('branches').then((r) => r.data.data)
}

/**
 * The primary way a worker now gets an account — an admin/dispatcher
 * only ever provides an email. Everything else (name, phone, password)
 * is filled in by the worker themselves via the invite link.
 */
export function inviteWorker(email) {
  return client.post('workers/invite', { email }).then((r) => r.data.data)
}

/**
 * The explicit confirmation after a 409 { reactivatable: true } from
 * inviteWorker() above — resets the existing worker's invitation and
 * resends the email, keeping everything else about them untouched.
 */
export function reactivateWorker(userId) {
  return client.post(`workers/${userId}/reactivate`).then((r) => r.data.data)
}

/**
 * Registers the new worker's account via the same public endpoint a
 * worker would use to sign themselves up. Issues that worker a token
 * too (unused here — an admin filling out this form isn't "logging in
 * as" the worker, just creating their account on their behalf).
 *
 * This is the OLDER flow — still useful for e.g. importing existing
 * employee data, but inviteWorker() above is the primary path now.
 */
export function registerWorker({ name, email, phone, password }) {
  return client
    .post('auth/register', {
      name,
      email,
      phone,
      password,
      password_confirmation: password,
    })
    .then((r) => r.data.data.user)
}

// --- Worker (personal facts + work authorization) ---

export function fetchWorker(userId) {
  return client.get(`users/${userId}/worker`).then((r) => r.data.data)
}

export function updateWorker(userId, payload) {
  return client.put(`users/${userId}/worker`, payload).then((r) => r.data.data)
}

// --- CompanyWorker (the employment relationship) ---

export function fetchEmployment(userId) {
  return client.get(`users/${userId}/employment`).then((r) => r.data.data)
}

export function updateEmployment(userId, payload) {
  return client.put(`users/${userId}/employment`, payload).then((r) => r.data.data)
}

// --- EmploymentContract (full contract history) ---

export function fetchContracts(userId) {
  return client.get(`users/${userId}/contracts`).then((r) => r.data.data)
}

/**
 * Builds multipart/form-data only when a file is actually attached —
 * otherwise a plain JSON payload, since most edits (e.g. just changing
 * status) don't need one.
 */
function toContractFormData(payload) {
  const formData = new FormData()
  for (const [key, value] of Object.entries(payload)) {
    if (key === 'file') continue
    if (value === null || value === undefined || value === '') continue
    formData.append(key, typeof value === 'boolean' ? (value ? '1' : '0') : value)
  }
  if (payload.file) formData.append('file', payload.file)
  return formData
}

export function createContract(userId, payload) {
  if (payload.file) {
    return client
      .post(`users/${userId}/contracts`, toContractFormData(payload), {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      .then((r) => r.data.data)
  }
  return client.post(`users/${userId}/contracts`, payload).then((r) => r.data.data)
}

/**
 * PUT doesn't natively support multipart/form-data in most HTTP
 * clients/browsers — when a file is attached, this uses Laravel's
 * standard method-spoofing (POST with _method=PUT) instead.
 */
export function updateContract(userId, contractId, payload) {
  if (payload.file) {
    const formData = toContractFormData(payload)
    formData.append('_method', 'PUT')
    return client
      .post(`users/${userId}/contracts/${contractId}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      .then((r) => r.data.data)
  }
  return client.put(`users/${userId}/contracts/${contractId}`, payload).then((r) => r.data.data)
}

// --- Qualifications / availability (unchanged endpoints) ---

export function grantQualification(userId, qualificationId) {
  return client
    .post(`users/${userId}/qualifications`, { qualification_id: qualificationId })
    .then((r) => r.data.data)
}

export function syncAvailability(userId, slots) {
  return client.post(`users/${userId}/availability`, { slots }).then((r) => r.data.data)
}

// --- Documents (admin view of a specific worker's uploads) ---

export function fetchWorkerDocuments(userId) {
  return client.get(`users/${userId}/documents`).then((r) => r.data.data)
}

export function reviewDocument(documentId, payload) {
  return client.post(`documents/${documentId}/review`, payload).then((r) => r.data.data)
}

/**
 * Returns the raw file as a blob and triggers the browser's own
 * download — the endpoint needs the Bearer token, so a plain <a href>
 * can't be used directly (same reasoning as the contract download in
 * worker-portal).
 */
export async function downloadDocument(documentId, filename = 'document') {
  const response = await client.get(`documents/${documentId}/download`, { responseType: 'blob' })

  const url = window.URL.createObjectURL(new Blob([response.data]))
  const link = document.createElement('a')
  link.href = url
  link.download = filename
  document.body.appendChild(link)
  link.click()
  link.remove()
  window.URL.revokeObjectURL(url)
}

/**
 * Opens the file in a new tab instead of forcing a save — for a photo
 * or PDF, this lets someone just LOOK at it without cluttering their
 * downloads folder. No `download` attribute set, so the browser renders
 * it natively by its actual MIME type (axios's blob already carries the
 * real Content-Type from the response).
 */
export async function viewDocument(documentId) {
  const response = await client.get(`documents/${documentId}/download`, { responseType: 'blob' })
  const url = window.URL.createObjectURL(new Blob([response.data]))
  window.open(url, '_blank')
}

export async function downloadContractFile(userId, contractId, filename = 'contract') {
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

export async function viewContractFile(userId, contractId) {
  const response = await client.get(`users/${userId}/contracts/${contractId}/download`, {
    responseType: 'blob',
  })
  const url = window.URL.createObjectURL(new Blob([response.data]))
  window.open(url, '_blank')
}
