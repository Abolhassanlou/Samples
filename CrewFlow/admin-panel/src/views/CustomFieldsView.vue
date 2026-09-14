<script setup>
import { ref, onMounted, watch } from 'vue'
import AppShell from '@/components/layout/AppShell.vue'
import {
  fetchCustomFields,
  createCustomField,
  updateCustomField,
  deleteCustomField,
  fetchFixedDocumentTypes,
  fetchCustomDocumentTypes,
  createCustomDocumentType,
  updateCustomDocumentType,
  deleteCustomDocumentType,
} from '@/api/customFields'

const TABS = [
  { key: 'personal_info', label: 'Personal info questions', kind: 'field' },
  { key: 'skill', label: 'Skill questions', kind: 'field' },
  { key: 'personal', label: 'Personal document types', kind: 'document' },
  { key: 'work', label: 'Work document types', kind: 'document' },
]

const activeTab = ref(TABS[0])
const items = ref([]) // field questions, OR custom (editable) document types
const fixedItems = ref([]) // read-only built-in document types — empty unless kind === 'document'
const loading = ref(true)

// New field form state.
const newLabel = ref('')
const newKey = ref('')
const newFieldType = ref('text')
const newOptionsText = ref('')
const newIsRequired = ref(false)
const creating = ref(false)
const createError = ref('')

// Edit ("Modify") form state — opens inline under the row being edited.
const editingId = ref(null)
const editLabel = ref('')
const editFieldType = ref('text')
const editOptionsText = ref('')
const editIsRequired = ref(false)
const saving = ref(false)
const editError = ref('')

const loadError = ref('')

async function loadItems() {
  loading.value = true
  loadError.value = ''
  try {
    if (activeTab.value.kind === 'field') {
      fixedItems.value = []
      items.value = await fetchCustomFields(activeTab.value.key)
    } else {
      const [fixed, custom] = await Promise.all([
        fetchFixedDocumentTypes(activeTab.value.key),
        fetchCustomDocumentTypes(activeTab.value.key),
      ])
      fixedItems.value = fixed
      items.value = custom
    }
  } catch (error) {
    // Previously missing entirely — a failed request here left the
    // page stuck on "Loading…" forever with no indication anything
    // had gone wrong.
    loadError.value = error.response?.data?.message || 'Could not load this list. Check your connection and try again.'
  } finally {
    loading.value = false
  }
}

onMounted(loadItems)
watch(activeTab, () => {
  createError.value = ''
  editingId.value = null
  loadItems()
})

function slugify(label) {
  return label
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '_')
    .replace(/^_+|_+$/g, '')
}

async function handleCreate() {
  if (!newLabel.value.trim()) return

  creating.value = true
  createError.value = ''
  try {
    const key = newKey.value.trim() || slugify(newLabel.value)

    if (activeTab.value.kind === 'field') {
      const needsOptions = newFieldType.value === 'select' || newFieldType.value === 'multi_select'
      const options = needsOptions
        ? newOptionsText.value.split(',').map((o) => o.trim()).filter(Boolean)
        : null

      await createCustomField({
        category: activeTab.value.key,
        key,
        label: newLabel.value.trim(),
        field_type: newFieldType.value,
        options,
        is_required: newIsRequired.value,
      })
    } else {
      await createCustomDocumentType({
        category: activeTab.value.key,
        key,
        label: newLabel.value.trim(),
      })
    }

    newLabel.value = ''
    newKey.value = ''
    newOptionsText.value = ''
    newIsRequired.value = false
    await loadItems()
  } catch (error) {
    // Surfaced so a rejected create (e.g. a validation error) is
    // actually visible — previously this failed completely silently,
    // leaving the form as-is with no indication anything went wrong.
    const backendMessage = error.response?.data?.message
    const fieldErrors = error.response?.data?.errors
    createError.value = fieldErrors
      ? Object.values(fieldErrors).flat().join(' ')
      : backendMessage || 'Could not create this. Please try again.'
  } finally {
    creating.value = false
  }
}

async function toggleActive(item) {
  if (activeTab.value.kind === 'field') {
    await updateCustomField(item.id, { is_active: !item.is_active })
  } else {
    await updateCustomDocumentType(item.id, { is_active: !item.is_active })
  }
  await loadItems()
}

function startEdit(item) {
  editingId.value = item.id
  editError.value = ''
  editLabel.value = item.label
  editFieldType.value = item.field_type || 'text'
  editOptionsText.value = (item.options || []).join(', ')
  editIsRequired.value = !!item.is_required
}

function cancelEdit() {
  editingId.value = null
}

async function saveEdit(item) {
  saving.value = true
  editError.value = ''
  try {
    if (activeTab.value.kind === 'field') {
      const needsOptions = editFieldType.value === 'select' || editFieldType.value === 'multi_select'
      const options = needsOptions
        ? editOptionsText.value.split(',').map((o) => o.trim()).filter(Boolean)
        : null

      await updateCustomField(item.id, {
        label: editLabel.value.trim(),
        field_type: editFieldType.value,
        options,
        is_required: editIsRequired.value,
      })
    } else {
      await updateCustomDocumentType(item.id, { label: editLabel.value.trim() })
    }

    editingId.value = null
    await loadItems()
  } catch (error) {
    const backendMessage = error.response?.data?.message
    const fieldErrors = error.response?.data?.errors
    editError.value = fieldErrors
      ? Object.values(fieldErrors).flat().join(' ')
      : backendMessage || 'Could not save these changes. Please try again.'
  } finally {
    saving.value = false
  }
}

/**
 * A real, permanent delete — unlike the Disable toggle, which just
 * hides the question/type while keeping it and its history intact.
 * For a question, this also deletes every worker's existing answer to
 * it (the backend cascades). Confirmed with the browser's native
 * dialog since this can't be undone.
 */
async function handleDelete(item) {
  const warning =
    activeTab.value.kind === 'field'
      ? `Delete "${item.label}" permanently? This also deletes every worker's answer to it. This can't be undone — consider Disable instead if you just want to stop asking it.`
      : `Delete "${item.label}" permanently? This can't be undone.`

  if (!window.confirm(warning)) return

  if (activeTab.value.kind === 'field') {
    await deleteCustomField(item.id)
  } else {
    await deleteCustomDocumentType(item.id)
  }
  await loadItems()
}
</script>

<template>
  <AppShell>
    <h1 class="page-title">Custom fields</h1>
    <p class="page-lead">
      Define the questions and document types workers see on their profile. For document
      types, every company also gets a built-in baseline (photo, passport, bank card, and
      more) shown below, read-only — this page is for what your company adds on top.
    </p>

    <div class="tabs">
      <button
        v-for="tab in TABS"
        :key="tab.key + tab.kind"
        class="tab-button"
        :class="{ 'tab-button--active': activeTab.key === tab.key && activeTab.kind === tab.kind }"
        @click="activeTab = tab"
      >
        {{ tab.label }}
      </button>
    </div>

    <section class="panel">
      <p v-if="loading" class="loading-note">Loading…</p>
      <p v-else-if="loadError" class="create-error" role="alert">{{ loadError }}</p>

      <template v-else>
        <template v-if="fixedItems.length > 0">
          <h3 class="section-heading">Built in — every company gets these, can't be edited here</h3>
          <div v-for="item in fixedItems" :key="item.key" class="item-row item-row--fixed">
            <div>
              <span class="item-label">{{ item.label }}</span>
              <span class="item-key">{{ item.key }}</span>
            </div>
            <span class="builtin-badge">Built in</span>
          </div>

          <h3 class="section-heading">Added by your company</h3>
        </template>

        <div v-for="item in items" :key="item.id" class="item-row-wrapper">
          <div class="item-row">
            <div>
              <span class="item-label">{{ item.label }}</span>
              <span class="item-key">{{ item.key }}</span>
              <span v-if="activeTab.kind === 'field'" class="item-type">{{ item.field_type }}</span>
              <span v-if="item.is_required" class="required-badge">Required</span>
            </div>
            <div class="item-actions">
              <button class="toggle-button" @click="toggleActive(item)">
                {{ item.is_active ? 'Disable' : 'Enable' }}
              </button>
              <button class="toggle-button" @click="editingId === item.id ? cancelEdit() : startEdit(item)">
                {{ editingId === item.id ? 'Cancel' : 'Modify' }}
              </button>
              <button class="toggle-button toggle-button--danger" @click="handleDelete(item)">Delete</button>
            </div>
          </div>

          <div v-if="editingId === item.id" class="edit-form">
            <div class="field-grid">
              <label class="field">
                <span class="field-label">Label</span>
                <input v-model="editLabel" type="text" class="field-input" />
              </label>
              <label v-if="activeTab.kind === 'field'" class="field">
                <span class="field-label">Answer type</span>
                <select v-model="editFieldType" class="field-input">
                  <option value="text">Text</option>
                  <option value="number">Number</option>
                  <option value="date">Date</option>
                  <option value="select">Choice list — pick one</option>
                  <option value="multi_select">Choice list — pick several</option>
                  <option value="boolean">Yes / No</option>
                </select>
              </label>
            </div>

            <label
              v-if="activeTab.kind === 'field' && (editFieldType === 'select' || editFieldType === 'multi_select')"
              class="field options-field"
            >
              <span class="field-label">Options (comma-separated)</span>
              <input v-model="editOptionsText" type="text" class="field-input" />
            </label>

            <label v-if="activeTab.kind === 'field'" class="required-checkbox">
              <input type="checkbox" v-model="editIsRequired" />
              Required
            </label>

            <p v-if="editError" class="create-error" role="alert">{{ editError }}</p>

            <button class="create-button" :disabled="!editLabel.trim() || saving" @click="saveEdit(item)">
              {{ saving ? 'Saving…' : 'Save changes' }}
            </button>
          </div>
        </div>
        <p v-if="items.length === 0" class="empty-note">Nothing added yet.</p>

        <div class="new-item-form">
          <h3 class="form-title">Add {{ activeTab.kind === 'field' ? 'a question' : 'a document type' }}</h3>
          <div class="field-grid">
            <label class="field">
              <span class="field-label">Label</span>
              <input v-model="newLabel" type="text" class="field-input" placeholder="e.g. Shoe size" />
            </label>
            <label class="field">
              <span class="field-label">Key (optional — auto-generated from label)</span>
              <input v-model="newKey" type="text" class="field-input" placeholder="shoe_size" />
            </label>
            <label v-if="activeTab.kind === 'field'" class="field">
              <span class="field-label">Answer type</span>
              <select v-model="newFieldType" class="field-input">
                <option value="text">Text</option>
                <option value="number">Number</option>
                <option value="date">Date</option>
                <option value="select">Choice list — pick one</option>
                <option value="multi_select">Choice list — pick several</option>
                <option value="boolean">Yes / No</option>
              </select>
            </label>
          </div>

          <label
            v-if="activeTab.kind === 'field' && (newFieldType === 'select' || newFieldType === 'multi_select')"
            class="field options-field"
          >
            <span class="field-label">Options (comma-separated)</span>
            <input v-model="newOptionsText" type="text" class="field-input" placeholder="Elementary, Middle school, High school" />
          </label>

          <label v-if="activeTab.kind === 'field'" class="required-checkbox">
            <input type="checkbox" v-model="newIsRequired" />
            Required
          </label>

          <p v-if="createError" class="create-error" role="alert">{{ createError }}</p>

          <button class="create-button" :disabled="!newLabel.trim() || creating" @click="handleCreate">
            {{ creating ? 'Creating…' : 'Create' }}
          </button>
        </div>
      </template>
    </section>
  </AppShell>
</template>

<style scoped>
.page-title {
  font-family: var(--font-display);
  font-weight: 700;
  margin: 0 0 0.35rem;
}

.page-lead {
  color: var(--color-slate);
  margin: 0 0 1.5rem;
  max-width: 65ch;
}

.tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 1.25rem;
}

.tab-button {
  font-size: 0.82rem;
  font-weight: 600;
  padding: 0.5rem 0.9rem;
  color: var(--color-slate);
  background: #fff;
  border: 1px solid var(--color-line);
  border-radius: 999px;
  cursor: pointer;
}

.tab-button--active {
  color: var(--color-ink);
  background: rgba(224, 151, 58, 0.18);
  border-color: var(--color-amber);
}

.panel {
  background: #fff;
  border: 1px solid var(--color-line);
  border-radius: 10px;
  padding: 1.5rem;
}

.loading-note {
  color: var(--color-slate);
}

.section-heading {
  font-family: var(--font-display);
  font-size: 0.8rem;
  font-weight: 700;
  color: var(--color-slate);
  text-transform: uppercase;
  letter-spacing: 0.03em;
  margin: 1.25rem 0 0.5rem;
}

.section-heading:first-child {
  margin-top: 0;
}

.item-row--fixed {
  opacity: 0.75;
}

.builtin-badge {
  font-size: 0.72rem;
  font-weight: 600;
  color: var(--color-slate);
  border: 1px solid var(--color-line);
  border-radius: 6px;
  padding: 0.2rem 0.6rem;
  flex-shrink: 0;
}

.item-row-wrapper {
  border-bottom: 1px solid var(--color-line);
}

.item-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.7rem 0;
  gap: 1rem;
}

.item-actions {
  display: flex;
  gap: 0.5rem;
  flex-shrink: 0;
}

.toggle-button--danger {
  color: var(--color-danger);
  border-color: rgba(181, 83, 63, 0.35);
}

.edit-form {
  background: var(--color-paper);
  border-radius: 8px;
  padding: 1.1rem;
  margin: 0 0 1rem;
}

.item-label {
  font-weight: 600;
  font-size: 0.9rem;
  margin-right: 0.6rem;
}

.item-key {
  font-family: var(--font-mono);
  font-size: 0.76rem;
  color: var(--color-slate);
  margin-right: 0.6rem;
}

.item-type {
  font-size: 0.72rem;
  font-weight: 600;
  color: var(--color-amber-dark);
  background: rgba(224, 151, 58, 0.14);
  padding: 0.1rem 0.5rem;
  border-radius: 999px;
  margin-right: 0.6rem;
}

.required-badge {
  font-size: 0.72rem;
  font-weight: 600;
  color: var(--color-danger);
}

.toggle-button {
  font-size: 0.8rem;
  font-weight: 600;
  padding: 0.35rem 0.8rem;
  color: var(--color-ink);
  background: var(--color-paper);
  border: 1px solid var(--color-line);
  border-radius: 6px;
  cursor: pointer;
  flex-shrink: 0;
}

.empty-note {
  color: var(--color-slate);
  font-style: italic;
  font-size: 0.85rem;
}

.new-item-form {
  margin-top: 1.5rem;
  padding-top: 1.25rem;
  border-top: 1px dashed var(--color-line);
}

.form-title {
  font-family: var(--font-display);
  font-size: 0.95rem;
  margin: 0 0 1rem;
}

.field-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 1rem;
  margin-bottom: 0.9rem;
}

.field {
  display: block;
}

.options-field {
  margin-bottom: 0.9rem;
}

.field-label {
  display: block;
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--color-slate);
  margin-bottom: 0.35rem;
}

.field-input {
  width: 100%;
  padding: 0.55rem 0.7rem;
  font-size: 0.88rem;
  border: 1px solid var(--color-line);
  border-radius: 8px;
  background: #fff;
}

.field-input:focus-visible {
  border-color: var(--color-amber);
  box-shadow: 0 0 0 3px rgba(224, 151, 58, 0.25);
  outline: none;
}

.required-checkbox {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  margin-bottom: 1rem;
}

.create-error {
  color: var(--color-danger);
  background: rgba(181, 83, 63, 0.08);
  border: 1px solid rgba(181, 83, 63, 0.25);
  border-radius: 8px;
  padding: 0.6rem 0.75rem;
  font-size: 0.82rem;
  margin: 0 0 0.9rem;
}

.create-button {
  font-size: 0.85rem;
  font-weight: 600;
  padding: 0.5rem 1rem;
  color: var(--color-ink);
  background: var(--color-amber);
  border: none;
  border-radius: 8px;
  cursor: pointer;
}

.create-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
