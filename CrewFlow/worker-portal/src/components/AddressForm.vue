<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { fetchWorker, updateWorker } from '@/api/worker'
import { COUNTRIES } from '@/constants/countries'

const auth = useAuthStore()

const loading = ref(true)
const saving = ref(false)
const savedNote = ref(false)

const form = ref({
  street: '',
  house_number: '',
  postal_code: '',
  city: '',
  country: '',
  residence_type: '',
})

onMounted(async () => {
  const worker = await fetchWorker(auth.user.id)
  form.value = {
    street: worker.street || '',
    house_number: worker.house_number || '',
    postal_code: worker.postal_code || '',
    city: worker.city || '',
    country: worker.country || '',
    residence_type: worker.residence_type || '',
  }
  loading.value = false
})

async function handleSave() {
  saving.value = true
  try {
    await updateWorker(auth.user.id, form.value)
    savedNote.value = true
    setTimeout(() => (savedNote.value = false), 2000)
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div>
    <p v-if="loading" class="loading-note">Loading…</p>

    <form v-else class="form" @submit.prevent="handleSave">
      <div class="field-grid">
        <label class="field">
          <span class="field-label">Street</span>
          <input v-model="form.street" type="text" class="field-input" />
        </label>
        <label class="field">
          <span class="field-label">House number</span>
          <input v-model="form.house_number" type="text" class="field-input" />
        </label>
        <label class="field">
          <span class="field-label">Postal code</span>
          <input v-model="form.postal_code" type="text" class="field-input" />
        </label>
        <label class="field">
          <span class="field-label">City</span>
          <input v-model="form.city" type="text" class="field-input" />
        </label>
        <label class="field">
          <span class="field-label">Country</span>
          <select v-model="form.country" class="field-input">
            <option value="">—</option>
            <option v-for="c in COUNTRIES" :key="c" :value="c">{{ c }}</option>
          </select>
        </label>
        <label class="field">
          <span class="field-label">Residence type</span>
          <select v-model="form.residence_type" class="field-input">
            <option value="">—</option>
            <option value="main">Main (Hauptwohnsitz)</option>
            <option value="secondary">Secondary (Nebenwohnsitz)</option>
          </select>
        </label>
      </div>

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
