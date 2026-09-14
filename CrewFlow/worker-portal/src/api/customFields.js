import client from '@/api/client'

export function fetchCustomFields(category) {
  return client.get('custom-fields', { params: { category } }).then((r) => r.data.data)
}

export function fetchAnswers(userId) {
  return client.get(`users/${userId}/custom-field-answers`).then((r) => r.data.data)
}

/**
 * Full replace — send every field's current answer together (see the
 * Employee module's CustomFieldAnswerController::sync()).
 */
export function saveAnswers(userId, answers) {
  return client
    .post(`users/${userId}/custom-field-answers`, { answers })
    .then((r) => r.data.data)
}
