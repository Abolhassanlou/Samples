<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { fetchCustomFields, fetchAnswers, saveAnswers } from '@/api/customFields'
import CustomFieldForm from '@/components/CustomFieldForm.vue'

const props = defineProps({
  category: { type: String, required: true }, // "personal_info" | "skill"
})

const auth = useAuthStore()

const fields = ref([])
const answers = ref([])
const loading = ref(true)
const loadError = ref('')
const saving = ref(false)
const saveError = ref('')
const savedNote = ref(false)

// What the server said, in plain text — a validation failure arrives as
// { message, errors: { 'answers.0.value': ['…'] } }, anything else as just
// { message }. Without this, a failed save was an unhandled rejection and
// the person saw nothing happen at all.
function describeError(error, fallback) {
  const fieldErrors = error.response?.data?.errors
  if (fieldErrors) return Object.values(fieldErrors).flat().join(' ')
  return error.response?.data?.message || fallback
}

async function loadAll() {
  loading.value = true
  loadError.value = ''
  try {
    const [f, a] = await Promise.all([fetchCustomFields(props.category), fetchAnswers(auth.user.id)])
    fields.value = f
    answers.value = a
  } catch (error) {
    loadError.value = describeError(error, 'Could not load these questions. Check your connection and try again.')
  } finally {
    loading.value = false
  }
}

onMounted(loadAll)

async function handleSave(payload) {
  saving.value = true
  saveError.value = ''
  savedNote.value = false
  try {
    answers.value = await saveAnswers(auth.user.id, payload)
    savedNote.value = true
    setTimeout(() => (savedNote.value = false), 2000)
  } catch (error) {
    saveError.value = `Not saved — ${describeError(error, 'something went wrong. Please try again.')}`
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div>
    <p v-if="loading" class="loading-note">Loading…</p>
    <p v-else-if="loadError" class="error-note" role="alert">
      {{ loadError }}
      <button class="retry-button" @click="loadAll">Try again</button>
    </p>
    <template v-else>
      <CustomFieldForm :fields="fields" :answers="answers" :saving="saving" @save="handleSave" />
      <p v-if="saveError" class="error-note error-note--after" role="alert">{{ saveError }}</p>
      <p v-if="savedNote" class="saved-note">Saved.</p>
    </template>
  </div>
</template>

<style scoped>
.loading-note {
  font-size: 0.82rem;
  color: var(--color-slate);
  margin: 0;
}

.saved-note {
  font-size: 0.78rem;
  color: var(--color-green);
  margin: 0.5rem 0 0;
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

.error-note--after {
  margin-top: 0.6rem;
}

.retry-button {
  background: none;
  border: none;
  padding: 0;
  margin-left: 0.4rem;
  font: inherit;
  font-weight: 700;
  color: inherit;
  text-decoration: underline;
  cursor: pointer;
}
</style>
