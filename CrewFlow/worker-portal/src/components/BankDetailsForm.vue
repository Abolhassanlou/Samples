<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { fetchWorker, updateWorker } from '@/api/worker'

const auth = useAuthStore()

const loading = ref(true)
const saving = ref(false)
const savedNote = ref(false)
const errorMessage = ref('')

const form = ref({
  bank_name: '',
  bank_account_holder_name: '',
  iban: '',
  bic: '',
})

onMounted(async () => {
  const worker = await fetchWorker(auth.user.id)
  form.value = {
    bank_name: worker.bank_name || '',
    bank_account_holder_name: worker.bank_account_holder_name || '',
    iban: worker.iban || '',
    bic: worker.bic || '',
  }
  loading.value = false
})

async function handleSave() {
  errorMessage.value = ''
  saving.value = true
  try {
    await updateWorker(auth.user.id, form.value)
    savedNote.value = true
    setTimeout(() => (savedNote.value = false), 2000)
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Could not save your bank details.'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div>
    <p v-if="loading" class="loading-note">Loading…</p>

    <form v-else class="form" @submit.prevent="handleSave">
      <p class="holder-note">
        The account holder name must match your own name — you can't add someone else's account.
      </p>

      <div class="field-grid">
        <label class="field">
          <span class="field-label">Bank name</span>
          <input v-model="form.bank_name" type="text" class="field-input" />
        </label>
        <label class="field">
          <span class="field-label">Account holder name</span>
          <input v-model="form.bank_account_holder_name" type="text" class="field-input" />
        </label>
        <label class="field">
          <span class="field-label">IBAN</span>
          <input v-model="form.iban" type="text" class="field-input" />
        </label>
        <label class="field">
          <span class="field-label">BIC</span>
          <input v-model="form.bic" type="text" class="field-input" />
        </label>
      </div>

      <p v-if="errorMessage" class="error-note" role="alert">{{ errorMessage }}</p>

      <button type="submit" class="save-button" :disabled="saving">
        {{ saving ? 'Saving…' : 'Save' }}
      </button>
      <p v-if="savedNote" class="saved-note">Saved.</p>
    </form>
  </div>
</template>

<style scoped>
.loading-note {
  font-size: 0.82rem;
  color: var(--color-slate);
  margin: 0;
}

.form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.holder-note {
  font-size: 0.78rem;
  color: var(--color-slate);
  background: var(--color-paper);
  border-radius: 6px;
  padding: 0.6rem 0.75rem;
  margin: 0;
}

.field-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.9rem;
}

@media (min-width: 640px) {
  .field-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (min-width: 900px) {
  .field-grid {
    grid-template-columns: repeat(3, 1fr);
  }
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

.error-note {
  color: var(--color-danger);
  background: rgba(181, 83, 63, 0.08);
  border: 1px solid rgba(181, 83, 63, 0.25);
  border-radius: 8px;
  padding: 0.6rem 0.75rem;
  font-size: 0.82rem;
  margin: 0;
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

.saved-note {
  font-size: 0.78rem;
  color: var(--color-green);
  margin: 0;
}
</style>
