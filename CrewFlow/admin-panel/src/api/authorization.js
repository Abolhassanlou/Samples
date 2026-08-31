import client from '@/api/client'

export function fetchUsers() {
  return client.get('users').then((r) => r.data.data)
}

export function fetchRoles() {
  return client.get('roles').then((r) => r.data.data)
}

export function fetchPermissions() {
  return client.get('permissions').then((r) => r.data.data)
}

export function createRole(payload) {
  return client.post('roles', payload).then((r) => r.data.data)
}

export function updateRole(roleId, payload) {
  return client.put(`roles/${roleId}`, payload).then((r) => r.data.data)
}

export function deleteRole(roleId) {
  return client.delete(`roles/${roleId}`)
}

export function assignRole(userId, roleName) {
  return client.post(`users/${userId}/roles`, { role: roleName }).then((r) => r.data.data)
}

export function removeRole(userId, roleName) {
  // The role name (not id) is a literal URL path segment on the backend
  // (`DELETE users/{user}/roles/{role}` takes a string) — must be
  // encoded since role names can contain spaces, e.g. "Company Admin".
  return client
    .delete(`users/${userId}/roles/${encodeURIComponent(roleName)}`)
    .then((r) => r.data.data)
}
