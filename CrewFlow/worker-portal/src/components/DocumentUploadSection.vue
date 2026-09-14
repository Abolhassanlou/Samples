<script setup>
import { ref, onMounted, computed, reactive } from 'vue'
import { fetchDocumentTypes } from '@/api/documentTypes'
import { fetchMyDocuments, uploadDocument } from '@/api/documents'

const props = defineProps({
  category: { type: String, required: true }, // "personal" | "work"
})

const types = ref([])
const allDocuments = ref([])
const loading = ref(true)

// Per-row state, keyed by document type key — each row uploads
// independently of the others.
const rowFile = reactive({})
const rowUploading = reactive({})
const rowError = reactive({})

const typeKeys = computed(() => types.value.map((t) => t.key))
const myDocuments = computed(() => allDocuments.value.filter((d) => typeKeys.value.includes(d.document_type)))

function documentFor(typeKey) {
  return myDocuments.value.find((d) => d.document_type === typeKey)
}

async function loadAll() {
  loading.value = true
  const [t, docs] = await Promise.all([fetchDocumentTypes(props.category), fetchMyDocuments()])
  types.value = t
  allDocuments.value = docs
  loading.value = false
}

onMounted(loadAll)

function handleFileChange(typeKey, event) {
  rowFile[typeKey] = event.target.files[0] || null
}

async function handleUpload(typeKey) {
  const file = rowFile[typeKey]
  if (!file) return

  rowError[typeKey] = ''
  rowUploading[typeKey] = true
  try {
    await uploadDocument({ documentType: typeKey, file })
    rowFile[typeKey] = null
    await loadAll()
  } catch (error) {
    rowError[typeKey] = error.response?.data?.message || 'Could not upload this document.'
  } finally {
    rowUploading[typeKey] = false
  }
}
</script>

<template>
  <div>
    <p v-if="loading" class="coming-soon">Loading…</p>

    <div v-else class="doc-checklist">
      <div v-for="type in types" :key="type.key" class="doc-row">
        <div class="doc-row-top">
          <span class="doc-label">{{ type.label }}</span>
          <span
            v-if="documentFor(type.key)"
            class="doc-status"
            :class="`doc-status--${documentFor(type.key).review_status}`"
          >
            {{ documentFor(type.key).review_status }}
          </span>
          <span v-else class="doc-status doc-status--missing">Not uploaded</span>
        </div>

        <div class="doc-row-upload">
          <input
            type="file"
            accept=".pdf,.jpg,.jpeg,.png"
            class="upload-input"
            @change="handleFileChange(type.key, $event)"
          />
          <button
            class="upload-button"
            :disabled="!rowFile[type.key] || rowUploading[type.key]"
            @click="handleUpload(type.key)"
          >
            {{ rowUploading[type.key] ? 'Uploading…' : documentFor(type.key) ? 'Replace' : 'Upload' }}
          </button>
        </div>
        <p v-if="rowError[type.key]" class="upload-error">{{ rowError[type.key] }}</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.coming-soon {
  font-size: 0.82rem;
  color: var(--color-slate);
  line-height: 1.5;
  margin: 0;
}

.doc-checklist {
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
}

.doc-row {
  padding-bottom: 0.9rem;
  border-bottom: 1px solid var(--color-line);
}

.doc-row:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.doc-row-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}

.doc-label {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--color-ink);
}

.doc-status {
  font-size: 0.7rem;
  font-weight: 600;
  padding: 0.12rem 0.5rem;
  border-radius: 999px;
  background: rgba(74, 90, 106, 0.12);
  color: var(--color-slate);
  text-transform: capitalize;
  white-space: nowrap;
}

.doc-status--approved {
  background: rgba(76, 139, 108, 0.15);
  color: var(--color-green);
}

.doc-status--rejected {
  background: rgba(181, 83, 63, 0.12);
  color: var(--color-danger);
}

.doc-status--missing {
  background: rgba(224, 151, 58, 0.14);
  color: var(--color-amber-dark);
}

.doc-row-upload {
  display: flex;
  gap: 0.5rem;
}

.upload-input {
  flex: 1;
  min-width: 0;
  font-size: 0.78rem;
  padding: 0.4rem 0.5rem;
  border: 1px solid var(--color-line);
  border-radius: 8px;
  background: #fff;
}

.upload-error {
  color: var(--color-danger);
  font-size: 0.76rem;
  margin: 0.4rem 0 0;
}

.upload-button {
  flex-shrink: 0;
  font-size: 0.78rem;
  font-weight: 600;
  padding: 0.4rem 0.75rem;
  color: var(--color-ink);
  background: var(--color-amber);
  border: none;
  border-radius: 8px;
  cursor: pointer;
  white-space: nowrap;
}

.upload-button:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}
</style>
