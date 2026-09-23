import client from '@/api/client'

export function fetchShifts() {
  return client.get('shifts').then((r) => r.data.data)
}

export function createShift(payload) {
  return client.post('shifts', payload).then((r) => r.data.data)
}

export function updateShift(id, payload) {
  return client.put(`shifts/${id}`, payload).then((r) => r.data.data)
}

export function fetchShiftInterests(shiftId) {
  return client.get(`shifts/${shiftId}/interests`).then((r) => r.data.data)
}

export function fetchShiftAssignments(shiftId) {
  return client.get(`shifts/${shiftId}/assignments`).then((r) => r.data.data)
}

/**
 * Directly assigns a worker to a shift (no need for them to have
 * expressed interest first) — if a matching pending interest DOES
 * exist, the backend marks it "converted" automatically.
 */
export function createAssignment(shiftId, payload) {
  return client.post(`shifts/${shiftId}/assignments`, payload).then((r) => r.data.data)
}

/**
 * Permanent — cascades to positions, qualifications, interests, and
 * assignments tied to this shift. Prefer updateShift(id, { status:
 * 'cancelled' }) unless that's genuinely wanted (see the confirmation
 * dialog in ShiftsView.vue).
 */
export function deleteShift(id) {
  return client.delete(`shifts/${id}`)
}

/**
 * Dispatcher/admin cancels directly — immediate, no approval step.
 * Soft (sets status: cancelled, keeps the row for history) — distinct
 * from the worker-initiated cancellation-request flow, which needs
 * separate approval.
 */
export function deleteAssignment(assignmentId) {
  return client.delete(`assignments/${assignmentId}`)
}
