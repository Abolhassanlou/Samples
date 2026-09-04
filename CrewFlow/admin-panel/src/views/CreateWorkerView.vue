<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AppShell from '@/components/layout/AppShell.vue'
import {
  fetchQualifications,
  fetchBranches,
  registerWorker,
  updateWorker,
  updateEmployment,
  grantQualification,
  syncAvailability,
} from '@/api/workers'

const router = useRouter()

const qualifications = ref([])
const branches = ref([])

const DAY_LABELS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']

// Account fields.
const name = ref('')
const email = ref('')
const phone = ref('')
const password = ref('')

// Worker (personal facts) fields.
const firstName = ref('')
const lastName = ref('')
const dateOfBirth = ref('')
const address = ref('')
const postalCode = ref('')
const city = ref('')
const country = ref('')
const workAuthorizationStatus = ref('pending')
const workAuthorizationType = ref('')
const workAuthorizationExpiryDate = ref('')

// Employment relationship fields — NOT contract terms. A contract is
// added separately, once this worker exists, from their detail page.
const employeeNumber = ref('')
const homeBranchId = ref('')
const worksNightShifts = ref(false)

// Qualification selection.
const selectedQualificationIds = ref([])

// One row per day of the week.
const availabilityRows = ref(
  DAY_LABELS.map((_, dayOfWeek) => ({
    dayOfWeek,
    enabled: false,
    startTime: '09:00',
    endTime: '17:00',
  })),
)

const submitting = ref(false)
const errorMessage = ref('')

onMounted(async () => {
  const [q, b] = await Promise.all([fetchQualifications(), fetchBranches()])
  qualifications.value = q
  branches.value = b
})

async function handleSubmit() {
  errorMessage.value = ''
  submitting.value = true

  try {
    const user = await registerWorker({
      name: name.value.trim(),
      email: email.value.trim(),
      phone: phone.value.trim(),
      password: password.value,
    })

    await updateWorker(user.id, {
      first_name: firstName.value.trim() || undefined,
      last_name: lastName.value.trim() || undefined,
      date_of_birth: dateOfBirth.value || null,
      address: address.value || null,
      postal_code: postalCode.value || null,
      city: city.value || null,
      country: country.value || null,
      work_authorization_status: workAuthorizationStatus.value,
      work_authorization_type: workAuthorizationType.value || null,
      work_authorization_expiry_date: workAuthorizationExpiryDate.value || null,
    })

    await updateEmployment(user.id, {
      employee_number: employeeNumber.value || null,
      home_branch_id: homeBranchId.value || null,
      works_night_shifts: worksNightShifts.value,
    })

    for (const qualificationId of selectedQualificationIds.value) {
      await grantQualification(user.id, qualificationId)
    }

    const slots = availabilityRows.value
      .filter((row) => row.enabled)
      .map((row) => ({
        day_of_week: row.dayOfWeek,
        start_time: row.startTime,
        end_time: row.endTime,
      }))

    if (slots.length > 0) {
      await syncAvailability(user.id, slots)
    }

    router.push(`/workers/${user.id}`)
  } catch (error) {
    errorMessage.value =
      error.response?.data?.message || 'Something went wrong creating this worker. Please check the details and try again.'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <AppShell>
    <h1 class="page-title">Add worker</h1>
    <p class="page-lead">
      Creates the account and personal record. A contract comes next, from the worker's own
      page — a worker isn't defined by a contract, and can have several over time.
    </p>

    <p v-if="errorMessage" class="error-banner" role="alert">{{ errorMessage }}</p>

    <form class="panel" @submit.prevent="handleSubmit">
      <section class="form-section">
        <h2 class="section-title">Account</h2>
        <div class="field-grid">
          <label class="field">
            <span class="field-label">Full name</span>
            <input v-model="name" type="text" class="field-input" required />
          </label>
          <label class="field">
            <span class="field-label">Email</span>
            <input v-model="email" type="email" class="field-input" required />
          </label>
          <label class="field">
            <span class="field-label">Phone</span>
            <input v-model="phone" type="tel" class="field-input" required />
          </label>
          <label class="field">
            <span class="field-label">Temporary password</span>
            <input v-model="password" type="text" class="field-input" minlength="8" required />
          </label>
        </div>
      </section>

      <section class="form-section">
        <h2 class="section-title">Personal details</h2>
        <div class="field-grid">
          <label class="field">
            <span class="field-label">First name</span>
            <input v-model="firstName" type="text" class="field-input" placeholder="legal first name" />
          </label>
          <label class="field">
            <span class="field-label">Last name</span>
            <input v-model="lastName" type="text" class="field-input" placeholder="legal last name" />
          </label>
          <label class="field">
            <span class="field-label">Date of birth</span>
            <input v-model="dateOfBirth" type="date" class="field-input" />
          </label>
          <label class="field">
            <span class="field-label">Address</span>
            <input v-model="address" type="text" class="field-input" />
          </label>
          <label class="field">
            <span class="field-label">Postal code</span>
            <input v-model="postalCode" type="text" class="field-input" />
          </label>
          <label class="field">
            <span class="field-label">City</span>
            <input v-model="city" type="text" class="field-input" />
          </label>
          <label class="field">
            <span class="field-label">Country</span>
            <input v-model="country" type="text" class="field-input" />
          </label>
        </div>
      </section>

      <section class="form-section">
        <h2 class="section-title">Work authorization</h2>
        <div class="field-grid">
          <label class="field">
            <span class="field-label">Status</span>
            <select v-model="workAuthorizationStatus" class="field-input">
              <option value="pending">Pending</option>
              <option value="valid">Valid</option>
              <option value="expired">Expired</option>
              <option value="not_required">Not required</option>
              <option value="rejected">Rejected</option>
            </select>
          </label>
          <label class="field">
            <span class="field-label">Type</span>
            <input v-model="workAuthorizationType" type="text" class="field-input" placeholder="e.g. Rot-Weiß-Rot Karte Plus" />
          </label>
          <label class="field">
            <span class="field-label">Expiry date</span>
            <input v-model="workAuthorizationExpiryDate" type="date" class="field-input" />
          </label>
        </div>
      </section>

      <section class="form-section">
        <h2 class="section-title">Employment relationship</h2>
        <div class="field-grid">
          <label class="field">
            <span class="field-label">Employee number</span>
            <input v-model="employeeNumber" type="text" class="field-input" placeholder="this company's own numbering, optional" />
          </label>
          <label class="field">
            <span class="field-label">Home branch</span>
            <select v-model="homeBranchId" class="field-input">
              <option value="">No branch set</option>
              <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
            </select>
          </label>
        </div>
        <label class="night-checkbox">
          <input type="checkbox" v-model="worksNightShifts" />
          Willing to work night shifts
        </label>
      </section>

      <section class="form-section">
        <h2 class="section-title">Qualifications</h2>
        <div class="checkbox-grid">
          <label v-for="q in qualifications" :key="q.id" class="checkbox-item">
            <input type="checkbox" :value="q.id" v-model="selectedQualificationIds" />
            {{ q.name }}
          </label>
          <p v-if="qualifications.length === 0" class="empty-note">
            No qualifications defined yet.
          </p>
        </div>
      </section>

      <section class="form-section">
        <h2 class="section-title">Weekly availability</h2>
        <p class="section-hint">
          Enable any day this worker is free, and set the time range. This is about
          <em>which</em> days/hours they're free — use the "willing to work night shifts"
          checkbox above to declare whether they're open to overnight shifts at all.
        </p>
        <div class="availability-table">
          <div v-for="row in availabilityRows" :key="row.dayOfWeek" class="availability-row">
            <label class="availability-day">
              <input type="checkbox" v-model="row.enabled" />
              {{ DAY_LABELS[row.dayOfWeek] }}
            </label>
            <input type="time" v-model="row.startTime" class="field-input" :disabled="!row.enabled" />
            <span class="availability-sep">–</span>
            <input type="time" v-model="row.endTime" class="field-input" :disabled="!row.enabled" />
          </div>
        </div>
      </section>

      <button type="submit" class="submit-button" :disabled="submitting">
        {{ submitting ? 'Creating…' : 'Create worker' }}
      </button>
    </form>
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
  max-width: 60ch;
}

.error-banner {
  color: var(--color-danger);
  background: rgba(181, 83, 63, 0.08);
  border: 1px solid rgba(181, 83, 63, 0.25);
  border-radius: 8px;
  padding: 0.75rem 1rem;
  margin-bottom: 1rem;
}

.panel {
  background: #fff;
  border: 1px solid var(--color-line);
  border-radius: 10px;
  padding: 1.75rem;
  max-width: 720px;
}

.form-section {
  margin-bottom: 1.75rem;
  padding-bottom: 1.75rem;
  border-bottom: 1px dashed var(--color-line);
}

.form-section:last-of-type {
  border-bottom: none;
  margin-bottom: 1.25rem;
  padding-bottom: 0;
}

.section-title {
  font-family: var(--font-display);
  font-size: 1rem;
  margin: 0 0 1rem;
}

.section-hint {
  font-size: 0.82rem;
  color: var(--color-slate);
  margin: -0.5rem 0 1rem;
}

.field-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 1rem;
}

.field {
  display: block;
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
  outline: none;
}

.field-input:focus-visible {
  border-color: var(--color-amber);
  box-shadow: 0 0 0 3px rgba(224, 151, 58, 0.25);
}

.field-input:disabled {
  background: var(--color-paper);
  color: var(--color-slate);
}

.night-checkbox {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  margin-top: 1.1rem;
}

.checkbox-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 0.6rem;
}

.checkbox-item {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 0.85rem;
}

.empty-note {
  grid-column: 1 / -1;
  font-size: 0.82rem;
  color: var(--color-slate);
  font-style: italic;
  margin: 0;
}

.availability-table {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.availability-row {
  display: flex;
  align-items: center;
  gap: 0.6rem;
}

.availability-day {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 0.85rem;
  width: 120px;
  flex-shrink: 0;
}

.availability-row .field-input {
  width: 130px;
}

.availability-sep {
  color: var(--color-slate);
}

.submit-button {
  font-size: 0.9rem;
  font-weight: 600;
  padding: 0.6rem 1.3rem;
  color: var(--color-ink);
  background: var(--color-amber);
  border: none;
  border-radius: 8px;
  cursor: pointer;
}

.submit-button:hover:not(:disabled) {
  background: var(--color-amber-dark);
}

.submit-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
