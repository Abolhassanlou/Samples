import client from '@/api/client'

export function fetchShifts() {
  return client.get('shifts').then((r) => r.data.data)
}

export function expressInterest(shiftId, shiftPositionId = null) {
  return client
    .post(`shifts/${shiftId}/interest`, { shift_position_id: shiftPositionId })
    .then((r) => r.data.data)
}

export function withdrawInterest(shiftId) {
  return client.delete(`shifts/${shiftId}/interest`)
}

export function fetchMyInterests() {
  return client.get('my-interests').then((r) => r.data.data)
}

export function fetchMyAssignments() {
  return client.get('my-assignments').then((r) => r.data.data)
}

export function confirmAssignment(assignmentId) {
  return client.post(`assignments/${assignmentId}/confirm`).then((r) => r.data.data)
}

export function requestCancellation(assignmentId) {
  return client.post(`assignments/${assignmentId}/cancellation-request`).then((r) => r.data.data)
}
