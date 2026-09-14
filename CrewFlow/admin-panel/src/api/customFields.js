import client from '@/api/client'

export function fetchCustomFields(category) {
  return client.get('custom-fields', { params: { category } }).then((r) => r.data.data)
}

export function createCustomField(payload) {
  return client.post('custom-fields', payload).then((r) => r.data.data)
}

export function updateCustomField(id, payload) {
  return client.put(`custom-fields/${id}`, payload).then((r) => r.data.data)
}

/**
 * Permanent — cascades to every worker's answer to this question.
 * Prefer updateCustomField(id, { is_active: false }) unless that's
 * genuinely wanted (see the confirmation dialog in CustomFieldsView.vue).
 */
export function deleteCustomField(id) {
  return client.delete(`custom-fields/${id}`)
}

/**
 * The FIXED baseline document types (photo, passport, bank_card, etc.)
 * — these live as a PHP constant in WorkerDocumentController, not the
 * database, so they're read-only here (no id to toggle/edit). A
 * completely separate source from fetchCustomDocumentTypes() below,
 * which only returns what THIS company has added on top.
 */
export function fetchFixedDocumentTypes(category) {
  return client.get('documents/types', { params: { category } }).then((r) => r.data.data)
}

export function fetchCustomDocumentTypes(category) {
  return client.get('custom-document-types', { params: { category } }).then((r) => r.data.data)
}

export function createCustomDocumentType(payload) {
  return client.post('custom-document-types', payload).then((r) => r.data.data)
}

export function updateCustomDocumentType(id, payload) {
  return client.put(`custom-document-types/${id}`, payload).then((r) => r.data.data)
}

/**
 * Permanent, but safe — doesn't touch already-uploaded files/records
 * (WorkerDocument.document_type is a plain string, not a foreign key).
 */
export function deleteCustomDocumentType(id) {
  return client.delete(`custom-document-types/${id}`)
}

/**
 * A specific worker's answers to the skill/personal_info questions —
 * self-or-users.manage on the backend, so an admin (who has
 * users.manage) can call this for anyone, same as fetchWorker().
 */
export function fetchWorkerAnswers(userId) {
  return client.get(`users/${userId}/custom-field-answers`).then((r) => r.data.data)
}
