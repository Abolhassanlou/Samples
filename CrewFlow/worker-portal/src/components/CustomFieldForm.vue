<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  fields: { type: Array, required: true }, // CustomFieldDefinition[]
  answers: { type: Array, required: true }, // CustomFieldAnswer[]
})

const emit = defineEmits(['save'])

const values = ref({})
const saving = ref(false)

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
}

watch([() => props.fields, () => props.answers], resetValues, { immediate: true })

async function handleSave() {
  saving.value = true
  try {
    const payload = props.fields.map((field) => {
      const raw = values.value[field.id]
      const value = field.field_type === 'multi_select' ? JSON.stringify(raw || []) : raw || null
      return { custom_field_definition_id: field.id, value }
    })
    emit('save', payload)
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="custom-field-form">
    <p v-if="fields.length === 0" class="empty-note">Nothing to fill in here yet.</p>

    <label v-for="field in fields" :key="field.id" class="field">
      <span class="field-label">{{ field.label }}<span v-if="field.is_required" class="required-mark">*</span></span>

      <input
        v-if="field.field_type === 'text'"
        v-model="values[field.id]"
        type="text"
        class="field-input"
      />
      <input
        v-else-if="field.field_type === 'number'"
        v-model="values[field.id]"
        type="number"
        class="field-input"
      />
      <input
        v-else-if="field.field_type === 'date'"
        v-model="values[field.id]"
        type="date"
        class="field-input"
      />
      <select v-else-if="field.field_type === 'select'" v-model="values[field.id]" class="field-input">
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
    </label>

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
