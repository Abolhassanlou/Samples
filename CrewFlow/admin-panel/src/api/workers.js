import client from '@/api/client'

export function fetchWorkers(filters = {}) {
  const params = {}
  if (filters.search) params.search = filters.search
  if (filters.qualificationId) params.qualification_id = filters.qualificationId
  if (filters.branchId) params.branch_id = filters.branchId
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
