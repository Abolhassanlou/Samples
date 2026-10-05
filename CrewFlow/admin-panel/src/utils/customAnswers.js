/**
 * How a worker's custom-question answers are read and shown. Answers are
 * stored as strings whatever the question's type (see the Employee
 * module's custom_field_answers migration): a boolean is "true"/"false",
 * a number its string form, a multi_select a JSON-encoded array.
 *
 * `answers` is the list GET /users/{id}/custom-field-answers returns —
 * ALL of the worker's answers, every category — so the same functions
 * serve the Skills tab and the personal-info questions on Profile.
 */

export function answerFor(answers, fieldId) {
  return answers.find((a) => a.custom_field_definition_id === fieldId)?.value
}

/** Did the worker give a real answer? Nothing ticked in a multi_select, an empty string and null all count as no. */
export function isAnswered(field, answers) {
  const raw = answerFor(answers, field.id)
  if (raw === undefined || raw === null || raw === '') return false
  if (field.field_type === 'multi_select') {
    try {
      const list = JSON.parse(raw)
      return Array.isArray(list) && list.length > 0
    } catch {
      return true // malformed — show it as stored rather than hide it
    }
  }
  return true
}

export function formatAnswer(field, answers) {
  if (!isAnswered(field, answers)) return '—'
  const raw = answerFor(answers, field.id)
  if (field.field_type === 'boolean') return raw === 'true' ? 'Yes' : 'No'
  if (field.field_type === 'multi_select') {
    try {
      return JSON.parse(raw).join(', ')
    } catch {
      return raw
    }
  }
  return raw
}

/**
 * The questions worth listing for an admin. The directory returns every
 * question to someone with users.manage, including ones since switched
 * off — an active one is always listed, a disabled one only if this
 * worker had already answered it (that answer is still on record).
 */
export function visibleQuestions(fields, answers) {
  return fields.filter((f) => f.is_active || isAnswered(f, answers))
}
