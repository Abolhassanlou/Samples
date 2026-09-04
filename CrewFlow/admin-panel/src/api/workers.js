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

export function createContract(userId, payload) {
  return client.post(`users/${userId}/contracts`, payload).then((r) => r.data.data)
}

export function updateContract(userId, contractId, payload) {
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
