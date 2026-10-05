<script setup>
import { ref, computed, watch, nextTick } from 'vue'

const props = defineProps({
  fields: { type: Array, required: true }, // CustomFieldDefinition[]
  answers: { type: Array, required: true }, // CustomFieldAnswer[]
  // Owned by the parent, which is the one actually waiting on the server —
  // this form only emits the payload, so it can't know when saving ends.
  saving: { type: Boolean, default: false },
})

const emit = defineEmits(['save'])

const values = ref({})
const formEl = ref(null)
// Set by the first Save click. Until then nothing is flagged — a required
// field isn't "wrong" just because the person hasn't reached it yet.
const attempted = ref(false)

function resetValues() {
  const map = {}
  for (const field of props.fields) {
    const existing = props.answers.find((a) => a.custom_field_definition_id === field.id)

    if (field.field_type === 'multi_select') {
      // Stored server-side as a JSON-encoded array string — parse it
      // back into a real array for the checkboxes to bind to. Falls
      // back to an empty array for both "never answered" and any
      // unexpected/malformed stored value.
      try {
        map[field.id] = existing?.value ? JSON.parse(existing.value) : []
      } catch {
        map[field.id] = []
      }
    } else {
      map[field.id] = existing?.value ?? (field.field_type === 'boolean' ? 'false' : '')
    }
  }
  values.value = map
  attempted.value = false
}

watch([() => props.fields, () => props.answers], resetValues, { immediate: true })

// The API stores every answer as a STRING (`answers.*.value` is
// nullable|string server-side). A number input hands back a JS number —
// Vue casts v-model on <input type="number"> automatically — which would
// go out as a JSON number and be rejected, so everything is converted
// here. An empty answer is null; 0 is a real answer ("0 children") and
// must not be mistaken for empty, which `raw || null` used to do.
function toStoredValue(field, raw) {
  if (field.field_type === 'multi_select') return JSON.stringify(raw || [])
  if (raw === '' || raw === null || raw === undefined) return null
  return String(raw)
}

// What "not answered" means for a required question. Matches the server
// (CustomFieldAnswerController::isBlank): whitespace-only text is blank, a
// multi_select with nothing ticked is blank, `0` is a real answer, and a
// boolean is never blank (an unticked box is still an answer).
function isEmptyAnswer(field, raw) {
  if (field.field_type === 'multi_select') return !Array.isArray(raw) || raw.length === 0
  if (field.field_type === 'boolean') return false
  return raw === null || raw === undefined || String(raw).trim() === ''
}

// Live once the person has tried to save: fixing a field clears its flag
// straight away, without having to press Save again to find out.
const missingFields = computed(() =>
  attempted.value ? props.fields.filter((f) => f.is_required && isEmptyAnswer(f, values.value[f.id])) : []
)
const isMissing = (field) => missingFields.value.some((f) => f.id === field.id)

async function handleSave() {
  attempted.value = true

  if (missingFields.value.length > 0) {
    // The Save button is at the bottom of a possibly long form — take the
    // person to the first thing that needs filling in.
    await nextTick()
    formEl.value?.querySelector('.field--missing input, .field--missing select')?.focus()
    return
  }

  const payload = props.fields.map((field) => ({
    custom_field_definition_id: field.id,
    value: toStoredValue(field, values.value[field.id]),
  }))
  emit('save', payload)
}
</script>

<template>
  <div ref="formEl" class="custom-field-form">
    <p v-if="fields.length === 0" class="empty-note">Nothing to fill in here yet.</p>

    <label v-for="field in fields" :key="field.id" class="field" :class="{ 'field--missing': isMissing(field) }">
      <span class="field-label">{{ field.label }}<span v-if="field.is_required" class="required-mark">*</span></span>

      <input
        v-if="field.field_type === 'text'"
        v-model="values[field.id]"
        type="text"
        class="field-input"
        :aria-invalid="isMissing(field)"
      />
      <input
        v-else-if="field.field_type === 'number'"
        v-model="values[field.id]"
        type="number"
        class="field-input"
        :aria-invalid="isMissing(field)"
      />
      <input
        v-else-if="field.field_type === 'date'"
        v-model="values[field.id]"
        type="date"
        class="field-input"
        :aria-invalid="isMissing(field)"
      />
      <select v-else-if="field.field_type === 'select'" v-model="values[field.id]" class="field-input"
        :aria-invalid="isMissing(field)">
        <option value="">—</option>
        <option v-for="opt in field.options || []" :key="opt" :value="opt">{{ opt }}</option>
      </select>
      <div v-else-if="field.field_type === 'multi_select'" class="multi-select-options">
        <label v-for="opt in field.options || []" :key="opt" class="multi-select-option">
          <input type="checkbox" :value="opt" v-model="values[field.id]" />
          {{ opt }}
        </label>
      </div>
      <label v-else-if="field.field_type === 'boolean'" class="boolean-toggle">
        <input
          type="checkbox"
          :checked="values[field.id] === 'true'"
          @change="values[field.id] = $event.target.checked ? 'true' : 'false'"
        />
        Yes
      </label>
      <span v-if="isMissing(field)" class="field-error" role="alert">
        {{ field.field_type === 'multi_select' ? 'Choose at least one.' : 'This question is required.' }}
      </span>
    </label>

    <p v-if="missingFields.length > 0" class="error-note" role="alert">
      Not saved yet — please answer: {{ missingFields.map((f) => f.label).join(', ') }}.
    </p>

    <button v-if="fields.length > 0" class="save-button" :disabled="saving" @click="handleSave">
      {{ saving ? 'Saving…' : 'Save' }}
    </button>
  </div>
</template>

<style scoped>
.custom-field-form {
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
}

.empty-note {
  font-size: 0.82rem;
  color: var(--color-slate);
  font-style: italic;
  margin: 0;
}

.field {
  display: block;
}

.field-label {
  display: block;
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--color-ink);
  margin-bottom: 0.35rem;
}

.required-mark {
  color: var(--color-danger);
  margin-left: 0.15rem;
}

.field--missing .field-input {
  border-color: var(--color-danger);
}

.field-error {
  display: block;
  margin-top: 0.3rem;
  font-size: 0.76rem;
  font-weight: 600;
  color: var(--color-danger);
}

.error-note {
  color: var(--color-danger);
  background: rgba(181, 83, 63, 0.08);
  border: 1px solid rgba(181, 83, 63, 0.25);
  border-radius: 8px;
  padding: 0.6rem 0.75rem;
  font-size: 0.82rem;
  margin: 0;
}

.field-input {
  width: 100%;
  padding: 0.55rem 0.7rem;
  font-size: 0.88rem;
  border: 1px solid var(--color-line);
  border-radius: 8px;
  background: #fff;
  font-family: inherit;
}

.field-input:focus-visible {
  border-color: var(--color-amber);
  box-shadow: 0 0 0 3px rgba(224, 151, 58, 0.25);
  outline: none;
}

.multi-select-options {
  display: flex;
  flex-wrap: wrap;
  gap: 0.7rem 1.1rem;
}

.multi-select-option {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.85rem;
  font-weight: 400;
}

.boolean-toggle {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  font-weight: 400;
}

.save-button {
  align-self: flex-start;
  font-size: 0.85rem;
  font-weight: 600;
  padding: 0.5rem 1rem;
  color: var(--color-ink);
  background: var(--color-amber);
  border: none;
  border-radius: 8px;
  cursor: pointer;
}

.save-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
