<script setup>
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { fetchWorker, updateWorker } from '@/api/worker'
import { updateMe } from '@/api/authProfile'
import { COUNTRIES } from '@/constants/countries'
import { LANGUAGES } from '@/constants/languages'

const auth = useAuthStore()

const LANGUAGE_OPTIONS = ['german', 'english', 'italian', 'french']

// A canonical marker string for "doesn't need a visa" — distinguishes
// it from an actual visa/permit type the worker might type in. Both
// live in the same work_authorization_type field the backend already
// has; this is just how the two paths of the yes/no question below map
// onto it.
const EU_CITIZEN_MARKER = 'EU/EEA/Swiss/Austrian citizen — no visa required'

const STATUS_LABELS = {
  pending: 'Pending admin review',
  valid: 'Valid',
  expired: 'Expired',
  not_required: 'Not required',
  rejected: 'Rejected — contact your admin',
}

const loading = ref(true)
const saving = ref(false)
const savedNote = ref(false)
const errorMessage = ref('')

// The admin-confirmed status — shown read-only. This form never
// submits it; only an admin can change it (see the Employee module's
// README, "Why accept only asks for a password" section's neighbor on
// work authorization).
const currentStatus = ref('')

// Local UI state for the yes/no question below — not sent to the
// backend directly, just decides which fields show and what
// work_authorization_type ends up being on save.
const citizenshipType = ref('')

const form = ref({
  first_name: '',
  last_name: '',
  phone: '',
  date_of_birth: '',
  gender: '',
  marital_status: '',
  nationality: '',
  native_language: '',
  social_security_number: '',
  german_language_level: '',
  languages_spoken: [],
  work_authorization_type: '',
  work_authorization_expiry_date: '',
})

onMounted(async () => {
  const worker = await fetchWorker(auth.user.id)
  form.value = {
    first_name: worker.first_name || '',
    last_name: worker.last_name || '',
    phone: auth.user.phone || '',
    date_of_birth: worker.date_of_birth?.slice(0, 10) || '',
    gender: worker.gender || '',
    marital_status: worker.marital_status || '',
    nationality: worker.nationality || '',
    native_language: worker.native_language || '',
    social_security_number: worker.social_security_number || '',
    german_language_level: worker.german_language_level || '',
    languages_spoken: worker.languages_spoken || [],
    work_authorization_type: worker.work_authorization_type || '',
    work_authorization_expiry_date: worker.work_authorization_expiry_date?.slice(0, 10) || '',
  }
  currentStatus.value = worker.work_authorization_status || ''
  if (worker.work_authorization_type === EU_CITIZEN_MARKER) {
    citizenshipType.value = 'eu'
  } else if (worker.work_authorization_type) {
    citizenshipType.value = 'visa'
  }
  loading.value = false
})

const statusLabel = computed(() => STATUS_LABELS[currentStatus.value] || 'Not yet reviewed')

async function handleSave() {
  errorMessage.value = ''
  saving.value = true
  try {
    const { phone, ...workerFields } = form.value

    // Translate the yes/no question into what the backend actually
    // stores. Both paths send an expiry date now — an EU/EEA/Swiss/
    // Austrian citizen doesn't need a VISA, but the passport/ID itself
    // still expires and needs renewing, so that date matters here too,
    // it's just labeled differently in the form above.
    if (citizenshipType.value === 'eu') {
      workerFields.work_authorization_type = EU_CITIZEN_MARKER
    }

    // User.name still carries the invite's email-prefix placeholder
    // (see the Employee module's README, "Why accept only asks for a
    // password") until it's set for real here — derived from the
    // legal-name fields Worker actually owns, not asked for twice.
    const name = `${workerFields.first_name} ${workerFields.last_name}`.trim()

    await Promise.all([
      updateWorker(auth.user.id, workerFields),
      updateMe({ phone, ...(name ? { name } : {}) }),
    ])

    auth.updateUser({ phone, ...(name ? { name } : {}) })

    savedNote.value = true
    setTimeout(() => (savedNote.value = false), 2000)
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Could not save your details.'
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
          <span class="field-label">First name<span class="required-mark">*</span></span>
          <input v-model="form.first_name" type="text" class="field-input" required />
        </label>
        <label class="field">
          <span class="field-label">Last name<span class="required-mark">*</span></span>
          <input v-model="form.last_name" type="text" class="field-input" required />
        </label>
        <label class="field">
          <span class="field-label">Phone<span class="required-mark">*</span></span>
          <input v-model="form.phone" type="tel" class="field-input" required />
        </label>
        <label class="field">
          <span class="field-label">Date of birth<span class="required-mark">*</span></span>
          <input v-model="form.date_of_birth" type="date" class="field-input" required />
        </label>
        <label class="field">
          <span class="field-label">Gender<span class="required-mark">*</span></span>
          <select v-model="form.gender" class="field-input" required>
            <option value="" disabled>—</option>
            <option value="female">Female</option>
            <option value="male">Male</option>
            <option value="diverse">Diverse</option>
          </select>
        </label>
        <label class="field">
          <span class="field-label">Marital status</span>
          <select v-model="form.marital_status" class="field-input">
            <option value="">—</option>
            <option value="single">Single</option>
            <option value="married">Married</option>
            <option value="separated">Separated</option>
            <option value="widowed">Widowed</option>
            <option value="registered_partnership">Registered partnership</option>
          </select>
        </label>
        <label class="field">
          <span class="field-label">Nationality<span class="required-mark">*</span></span>
          <select v-model="form.nationality" class="field-input" required>
            <option value="" disabled>—</option>
            <option v-for="c in COUNTRIES" :key="c" :value="c">{{ c }}</option>
          </select>
        </label>
        <label class="field">
          <span class="field-label">Native language</span>
          <select v-model="form.native_language" class="field-input">
            <option value="">—</option>
            <option v-for="l in LANGUAGES" :key="l" :value="l">{{ l }}</option>
          </select>
        </label>
        <label class="field">
          <span class="field-label">Social security number<span class="required-mark">*</span></span>
          <input
            v-model="form.social_security_number"
            type="text"
            inputmode="numeric"
            pattern="[0-9]{10}"
            maxlength="10"
            title="Exactly 10 digits"
            class="field-input"
            required
          />
        </label>
        <label class="field">
          <span class="field-label">German language level</span>
          <select v-model="form.german_language_level" class="field-input">
            <option value="">—</option>
            <option value="none">None</option>
            <option value="basic">Basic</option>
            <option value="conversational">Conversational</option>
            <option value="fluent">Fluent</option>
            <option value="native">Native</option>
          </select>
        </label>
      </div>

      <div class="languages-field">
        <span class="field-label">Other languages spoken</span>
        <div class="language-options">
          <label v-for="lang in LANGUAGE_OPTIONS" :key="lang" class="language-option">
            <input type="checkbox" :value="lang" v-model="form.languages_spoken" />
            {{ lang.charAt(0).toUpperCase() + lang.slice(1) }}
          </label>
        </div>
      </div>

      <div class="work-auth-section">
        <h3 class="section-heading">Citizenship &amp; work authorization</h3>
        <p class="section-note">
          This is your own information — an admin will confirm it against your uploaded
          passport/permit document before it's official.
        </p>
        <p class="current-status">Current status: <strong>{{ statusLabel }}</strong></p>

        <label class="field">
          <span class="field-label">
            Do you have an Austrian, EU/EEA, or Swiss passport?<span class="required-mark">*</span>
          </span>
          <select v-model="citizenshipType" class="field-input" required>
            <option value="" disabled>—</option>
            <option value="eu">Yes — I don't need a work visa</option>
            <option value="visa">No — I have a different visa/permit</option>
          </select>
        </label>

        <div v-if="citizenshipType === 'visa'" class="field-grid field-grid--visa">
          <label class="field">
            <span class="field-label">Visa/permit type<span class="required-mark">*</span></span>
            <input
              v-model="form.work_authorization_type"
              type="text"
              class="field-input"
              placeholder="e.g. Rot-Weiß-Rot Karte Plus"
              required
            />
          </label>
          <label class="field">
            <span class="field-label">Visa/permit expiry date<span class="required-mark">*</span></span>
            <input v-model="form.work_authorization_expiry_date" type="date" class="field-input" required />
          </label>
        </div>

        <div v-else-if="citizenshipType === 'eu'" class="field-grid field-grid--visa">
          <label class="field">
            <span class="field-label">Passport/ID expiry date<span class="required-mark">*</span></span>
            <input v-model="form.work_authorization_expiry_date" type="date" class="field-input" required />
            <span class="field-hint">No visa needed, but your passport/ID itself still expires.</span>
          </label>
        </div>
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

.required-mark {
  color: var(--color-danger);
  margin-left: 0.15rem;
}

.field-hint {
  display: block;
  font-size: 0.74rem;
  color: var(--color-slate);
  margin-top: 0.35rem;
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

.languages-field {
  padding-top: 0.5rem;
  border-top: 1px dashed var(--color-line);
}

.work-auth-section {
  padding-top: 0.75rem;
  border-top: 1px dashed var(--color-line);
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
}

.section-heading {
  font-family: var(--font-display);
  font-size: 0.9rem;
  font-weight: 700;
  margin: 0;
  color: var(--color-ink);
}

.section-note {
  font-size: 0.78rem;
  color: var(--color-slate);
  margin: -0.4rem 0 0;
  line-height: 1.5;
}

.current-status {
  font-size: 0.82rem;
  color: var(--color-ink);
  margin: 0;
}

.field-grid--visa {
  margin: 0;
}

.language-options {
  display: flex;
  flex-wrap: wrap;
  gap: 0.9rem;
  margin-top: 0.5rem;
}

.language-option {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.85rem;
  font-weight: 400;
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
