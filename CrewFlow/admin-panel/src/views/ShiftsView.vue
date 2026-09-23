<script setup>
import { ref, onMounted } from 'vue'
import AppShell from '@/components/layout/AppShell.vue'
import { fetchShifts, createShift, updateShift, deleteShift, fetchShiftInterests, fetchShiftAssignments, createAssignment, deleteAssignment } from '@/api/shifts'
import { fetchBranches } from '@/api/workers'

const shifts = ref([])
const branches = ref([])
const loading = ref(true)
const loadError = ref('')

const showForm = ref(false)
const creating = ref(false)
const createError = ref('')

// "Modify" — inline edit, one row expands below the shift being edited.
const editingId = ref(null)
const editForm = ref({})
const saving = ref(false)
const editError = ref('')

// "Applicants" — a separate inline panel, who's expressed interest and
// who's already assigned, with an "Assign" action per pending interest.
const viewingApplicantsId = ref(null)
const shiftInterests = ref([])
const shiftAssignments = ref([])
const loadingApplicants = ref(false)
const assigningKey = ref(null)
const assignError = ref('')

const form = ref({
  title: '',
  description: '',
  branch_id: '',
  location_address: '',
  quantity_needed: 1,
  rate_type: 'hourly',
  hourly_rate: '',
  fixed_amount: '',
  starts_at: '',
  ends_at: '',
  qualification_policy: 'strict',
})

async function loadAll() {
  loading.value = true
  loadError.value = ''
  try {
    const [s, b] = await Promise.all([fetchShifts(), fetchBranches()])
    shifts.value = s
    branches.value = b
    if (b.length > 0 && !form.value.branch_id) form.value.branch_id = b[0].id
  } catch (error) {
    loadError.value = error.response?.data?.message || 'Could not load shifts. Check your connection and try again.'
  } finally {
    loading.value = false
  }
}

onMounted(loadAll)

function resetForm() {
  form.value = {
    title: '',
    description: '',
    branch_id: branches.value[0]?.id || '',
    location_address: '',
    quantity_needed: 1,
    rate_type: 'hourly',
    hourly_rate: '',
    fixed_amount: '',
    starts_at: '',
    ends_at: '',
    qualification_policy: 'strict',
  }
}

async function handleCreate() {
  creating.value = true
  createError.value = ''
  try {
    const payload = { ...form.value }
    if (payload.rate_type === 'hourly') delete payload.fixed_amount
    else delete payload.hourly_rate

    const created = await createShift(payload)
    shifts.value = [created, ...shifts.value]
    showForm.value = false
    resetForm()
  } catch (error) {
    const fieldErrors = error.response?.data?.errors
    createError.value = fieldErrors
      ? Object.values(fieldErrors).flat().join(' ')
      : error.response?.data?.message || 'Could not create this shift. Please check the details and try again.'
  } finally {
    creating.value = false
  }
}

function formatDateRange(shift) {
  if (!shift.starts_at) return ''
  const start = new Date(shift.starts_at)
  const end = shift.ends_at ? new Date(shift.ends_at) : null
  const dateFmt = { month: 'short', day: 'numeric', year: 'numeric' }
  const timeFmt = { hour: '2-digit', minute: '2-digit' }
  const datePart = start.toLocaleDateString(undefined, dateFmt)
  const startTime = start.toLocaleTimeString(undefined, timeFmt)
  const endTime = end ? end.toLocaleTimeString(undefined, timeFmt) : ''
  return endTime ? `${datePart}, ${startTime} – ${endTime}` : `${datePart}, ${startTime}`
}

function rateLabel(shift) {
  if (shift.rate_type === 'hourly' && shift.hourly_rate) return `€${shift.hourly_rate}/hr`
  if (shift.rate_type === 'fixed' && shift.fixed_amount) return `€${shift.fixed_amount} fixed`
  return '—'
}

// datetime-local inputs need "YYYY-MM-DDTHH:mm" — the API returns a
// full ISO timestamp, so this trims it down to what the input expects.
function toLocalInput(isoString) {
  if (!isoString) return ''
  const d = new Date(isoString)
  const pad = (n) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
}

function startEdit(shift) {
  editingId.value = shift.id
  viewingApplicantsId.value = null
  editError.value = ''
  editForm.value = {
    title: shift.title,
    description: shift.description || '',
    branch_id: shift.branch_id,
    location_address: shift.location_address || '',
    quantity_needed: shift.quantity_needed,
    rate_type: shift.rate_type,
    hourly_rate: shift.hourly_rate || '',
    fixed_amount: shift.fixed_amount || '',
    starts_at: toLocalInput(shift.starts_at),
    ends_at: toLocalInput(shift.ends_at),
    qualification_policy: shift.qualification_policy,
  }
}

function cancelEdit() {
  editingId.value = null
}

async function saveEdit(shift) {
  saving.value = true
  editError.value = ''
  try {
    const payload = { ...editForm.value }
    if (payload.rate_type === 'hourly') delete payload.fixed_amount
    else delete payload.hourly_rate

    const updated = await updateShift(shift.id, payload)
    shifts.value = shifts.value.map((s) => (s.id === shift.id ? updated : s))
    editingId.value = null
  } catch (error) {
    const fieldErrors = error.response?.data?.errors
    editError.value = fieldErrors
      ? Object.values(fieldErrors).flat().join(' ')
      : error.response?.data?.message || 'Could not save these changes. Please try again.'
  } finally {
    saving.value = false
  }
}

/**
 * "Disable" just sets status to cancelled (or back to open) through the
 * same update endpoint — keeps the shift and its full interest/
 * assignment history intact. A cancelled shift stops showing up in a
 * worker's "Available shifts" (see ShiftController::index()), but stays
 * visible here for the admin either way.
 */
async function toggleStatus(shift) {
  const newStatus = shift.status === 'cancelled' ? 'open' : 'cancelled'
  const updated = await updateShift(shift.id, { status: newStatus })
  shifts.value = shifts.value.map((s) => (s.id === shift.id ? updated : s))
}

/**
 * A real, permanent delete — unlike Disable, which just cancels while
 * keeping history. This also deletes every interest/assignment tied to
 * the shift (the backend cascades). Confirmed since it can't be undone.
 */
async function handleDelete(shift) {
  const warning = `Delete "${shift.title}" permanently? This also deletes every worker's interest/assignment tied to it. This can't be undone — consider Disable instead if you just want to stop it from being taken.`
  if (!window.confirm(warning)) return

  await deleteShift(shift.id)
  shifts.value = shifts.value.filter((s) => s.id !== shift.id)
  if (editingId.value === shift.id) editingId.value = null
}

async function toggleApplicants(shift) {
  if (viewingApplicantsId.value === shift.id) {
    viewingApplicantsId.value = null
    return
  }

  viewingApplicantsId.value = shift.id
  editingId.value = null
  assignError.value = ''
  loadingApplicants.value = true
  try {
    const [interests, assignments] = await Promise.all([
      fetchShiftInterests(shift.id),
      fetchShiftAssignments(shift.id),
    ])
    shiftInterests.value = interests
    shiftAssignments.value = assignments
  } finally {
    loadingApplicants.value = false
  }
}

/**
 * Turns a pending interest into a real assignment — the backend marks
 * the interest itself "converted" automatically, so after this call it
 * moves out of the interests list into the assignments list on its own.
 */
async function handleAssign(shift, interest) {
  const key = interest.id
  assigningKey.value = key
  assignError.value = ''
  try {
    await createAssignment(shift.id, {
      worker_id: interest.worker_id,
      shift_position_id: interest.shift_position_id,
    })
    const [interests, assignments] = await Promise.all([
      fetchShiftInterests(shift.id),
      fetchShiftAssignments(shift.id),
    ])
    shiftInterests.value = interests
    shiftAssignments.value = assignments
    // The list a shift's confirmed_count/status comes from just changed.
    shifts.value = await fetchShifts()
  } catch (error) {
    assignError.value = error.response?.data?.message || 'Could not assign this worker.'
  } finally {
    assigningKey.value = null
  }
}

/**
 * Removing an assignment directly — no approval step needed, this is
 * the dispatcher's own action. Confirmed since the worker loses their
 * spot immediately.
 */
async function handleRemoveAssignment(shift, assignment) {
  if (!window.confirm(`Remove ${assignment.worker_name} from this shift?`)) return

  assigningKey.value = `remove-${assignment.id}`
  assignError.value = ''
  try {
    await deleteAssignment(assignment.id)
    const [interests, assignments] = await Promise.all([
      fetchShiftInterests(shift.id),
      fetchShiftAssignments(shift.id),
    ])
    shiftInterests.value = interests
    shiftAssignments.value = assignments
    shifts.value = await fetchShifts()
  } catch (error) {
    assignError.value = error.response?.data?.message || 'Could not remove this assignment.'
  } finally {
    assigningKey.value = null
  }
}
</script>

<template>
  <AppShell>
    <div class="page-header">
      <div>
        <h1 class="page-title">Shifts</h1>
        <p class="page-lead">
          Simple, standalone shifts for now — no Event grouping, one plain headcount instead of
          named roles. Those come next.
        </p>
      </div>
      <button class="new-button" @click="showForm = !showForm">
        {{ showForm ? 'Cancel' : '+ New shift' }}
      </button>
    </div>

    <form v-if="showForm" class="new-shift-form" @submit.prevent="handleCreate">
      <div class="field-grid">
        <label class="field field--wide">
          <span class="field-label">Title</span>
          <input v-model="form.title" type="text" class="field-input" required />
        </label>
        <label class="field">
          <span class="field-label">Branch</span>
          <select v-model="form.branch_id" class="field-input" required>
            <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
          </select>
        </label>
        <label class="field">
          <span class="field-label">People needed</span>
          <input v-model.number="form.quantity_needed" type="number" min="1" class="field-input" required />
        </label>
        <label class="field">
          <span class="field-label">Starts</span>
          <input v-model="form.starts_at" type="datetime-local" class="field-input" required />
        </label>
        <label class="field">
          <span class="field-label">Ends</span>
          <input v-model="form.ends_at" type="datetime-local" class="field-input" required />
        </label>
        <label class="field">
          <span class="field-label">Rate type</span>
          <select v-model="form.rate_type" class="field-input">
            <option value="hourly">Hourly</option>
            <option value="fixed">Fixed</option>
          </select>
        </label>
        <label v-if="form.rate_type === 'hourly'" class="field">
          <span class="field-label">Hourly rate (€)</span>
          <input v-model="form.hourly_rate" type="number" min="0" step="0.01" class="field-input" />
        </label>
        <label v-else class="field">
          <span class="field-label">Fixed amount (€)</span>
          <input v-model="form.fixed_amount" type="number" min="0" step="0.01" class="field-input" />
        </label>
        <label class="field">
          <span class="field-label">Qualification policy</span>
          <select v-model="form.qualification_policy" class="field-input">
            <option value="strict">Strict — hide if not qualified</option>
            <option value="override">Override — show to everyone, no warning</option>
            <option value="warn">Warn — show to everyone, flag mismatches</option>
          </select>
        </label>
        <label class="field field--wide">
          <span class="field-label">Location address</span>
          <input v-model="form.location_address" type="text" class="field-input" />
        </label>
        <label class="field field--wide">
          <span class="field-label">Description</span>
          <textarea v-model="form.description" class="field-input" rows="2"></textarea>
        </label>
      </div>

      <p v-if="createError" class="form-error" role="alert">{{ createError }}</p>

      <button type="submit" class="create-button" :disabled="creating">
        {{ creating ? 'Creating…' : 'Create shift' }}
      </button>
    </form>

    <p v-if="loading" class="loading-note">Loading…</p>
    <p v-else-if="loadError" class="form-error" role="alert">{{ loadError }}</p>

    <table v-else-if="shifts.length > 0" class="shifts-table">
      <thead>
        <tr>
          <th>Title</th>
          <th>When</th>
          <th>Filled</th>
          <th>Rate</th>
          <th>Status</th>
          <th>Policy</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <template v-for="shift in shifts" :key="shift.id">
          <tr>
            <td>{{ shift.title }}</td>
            <td>{{ formatDateRange(shift) }}</td>
            <td>
              {{ shift.confirmed_count }}/{{ shift.quantity_needed }}
              <span v-if="shift.confirmed_workers?.length > 0" class="confirmed-names">
                {{ shift.confirmed_workers.map((w) => w.name).join(', ') }}
              </span>
            </td>
            <td>{{ rateLabel(shift) }}</td>
            <td><span class="status-chip" :class="`status-chip--${shift.status}`">{{ shift.status }}</span></td>
            <td>{{ shift.qualification_policy }}</td>
            <td class="actions-cell">
              <button class="text-action" @click="toggleApplicants(shift)">
                {{ viewingApplicantsId === shift.id ? 'Hide applicants' : 'Applicants' }}
              </button>
              <button class="text-action" @click="editingId === shift.id ? cancelEdit() : startEdit(shift)">
                {{ editingId === shift.id ? 'Cancel' : 'Modify' }}
              </button>
              <button class="text-action" @click="toggleStatus(shift)">
                {{ shift.status === 'cancelled' ? 'Enable' : 'Disable' }}
              </button>
              <button class="text-action text-action--danger" @click="handleDelete(shift)">Delete</button>
            </td>
          </tr>
          <tr v-if="viewingApplicantsId === shift.id">
            <td colspan="7">
              <div class="applicants-panel">
                <p v-if="loadingApplicants" class="loading-note">Loading…</p>
                <template v-else>
                  <h4 class="applicants-heading">Interested (not yet assigned)</h4>
                  <div v-if="shiftInterests.length > 0" class="applicant-list">
                    <div v-for="interest in shiftInterests" :key="interest.id" class="applicant-row">
                      <div>
                        <span class="applicant-name">{{ interest.worker_name }}</span>
                        <span v-if="interest.role_name" class="applicant-role">{{ interest.role_name }}</span>
                        <span class="applicant-status" :class="`applicant-status--${interest.status}`">{{ interest.status }}</span>
                        <span v-if="interest.qualification_warning" class="applicant-warning">doesn't fully meet requirements</span>
                      </div>
                      <button
                        class="assign-button"
                        :disabled="assigningKey === interest.id"
                        @click="handleAssign(shift, interest)"
                      >
                        {{ assigningKey === interest.id ? 'Assigning…' : 'Assign' }}
                      </button>
                    </div>
                  </div>
                  <p v-else class="empty-note">No one has expressed interest yet.</p>

                  <p v-if="assignError" class="form-error" role="alert">{{ assignError }}</p>

                  <h4 class="applicants-heading applicants-heading--spaced">Assigned</h4>
                  <div v-if="shiftAssignments.length > 0" class="applicant-list">
                    <div v-for="assignment in shiftAssignments" :key="assignment.id" class="applicant-row">
                      <div>
                        <span class="applicant-name">{{ assignment.worker_name }}</span>
                        <span v-if="assignment.role_name" class="applicant-role">{{ assignment.role_name }}</span>
                        <span
                          class="applicant-status"
                          :class="assignment.status === 'confirmed' ? 'applicant-status--confirmed' : 'applicant-status--pending'"
                        >
                          {{ assignment.status === 'confirmed' ? 'Confirmed' : 'Awaiting worker confirmation' }}
                        </span>
                      </div>
                      <button
                        class="remove-button"
                        :disabled="assigningKey === `remove-${assignment.id}`"
                        @click="handleRemoveAssignment(shift, assignment)"
                      >
                        {{ assigningKey === `remove-${assignment.id}` ? 'Removing…' : 'Remove' }}
                      </button>
                    </div>
                  </div>
                  <p v-else class="empty-note">No one assigned yet.</p>
                </template>
              </div>
            </td>
          </tr>
          <tr v-if="editingId === shift.id">
            <td colspan="7">
              <div class="edit-form">
                <div class="field-grid">
                  <label class="field field--wide">
                    <span class="field-label">Title</span>
                    <input v-model="editForm.title" type="text" class="field-input" required />
                  </label>
                  <label class="field">
                    <span class="field-label">Branch</span>
                    <select v-model="editForm.branch_id" class="field-input" required>
                      <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                  </label>
                  <label class="field">
                    <span class="field-label">People needed</span>
                    <input v-model.number="editForm.quantity_needed" type="number" min="1" class="field-input" />
                  </label>
                  <label class="field">
                    <span class="field-label">Starts</span>
                    <input v-model="editForm.starts_at" type="datetime-local" class="field-input" />
                  </label>
                  <label class="field">
                    <span class="field-label">Ends</span>
                    <input v-model="editForm.ends_at" type="datetime-local" class="field-input" />
                  </label>
                  <label class="field">
                    <span class="field-label">Rate type</span>
                    <select v-model="editForm.rate_type" class="field-input">
                      <option value="hourly">Hourly</option>
                      <option value="fixed">Fixed</option>
                    </select>
                  </label>
                  <label v-if="editForm.rate_type === 'hourly'" class="field">
                    <span class="field-label">Hourly rate (€)</span>
                    <input v-model="editForm.hourly_rate" type="number" min="0" step="0.01" class="field-input" />
                  </label>
                  <label v-else class="field">
                    <span class="field-label">Fixed amount (€)</span>
                    <input v-model="editForm.fixed_amount" type="number" min="0" step="0.01" class="field-input" />
                  </label>
                  <label class="field">
                    <span class="field-label">Qualification policy</span>
                    <select v-model="editForm.qualification_policy" class="field-input">
                      <option value="strict">Strict</option>
                      <option value="override">Override</option>
                      <option value="warn">Warn</option>
                    </select>
                  </label>
                  <label class="field field--wide">
                    <span class="field-label">Location address</span>
                    <input v-model="editForm.location_address" type="text" class="field-input" />
                  </label>
                  <label class="field field--wide">
                    <span class="field-label">Description</span>
                    <textarea v-model="editForm.description" class="field-input" rows="2"></textarea>
                  </label>
                </div>
                <p v-if="editError" class="form-error" role="alert">{{ editError }}</p>
                <button class="create-button" :disabled="saving" @click="saveEdit(shift)">
                  {{ saving ? 'Saving…' : 'Save changes' }}
                </button>
              </div>
            </td>
          </tr>
        </template>
      </tbody>
    </table>
    <p v-else class="empty-note">No shifts yet — create one above.</p>
  </AppShell>
</template>

<style scoped>
.page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.page-title {
  font-family: var(--font-display);
  font-weight: 700;
  margin: 0 0 0.35rem;
}

.page-lead {
  color: var(--color-slate);
  margin: 0;
  max-width: 60ch;
  font-size: 0.88rem;
}

.new-button {
  flex-shrink: 0;
  font-size: 0.85rem;
  font-weight: 600;
  padding: 0.5rem 1rem;
  color: var(--color-ink);
  background: var(--color-amber);
  border: none;
  border-radius: 8px;
  cursor: pointer;
  white-space: nowrap;
}

.new-shift-form {
  background: #fff;
  border: 1px solid var(--color-line);
  border-radius: 10px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  max-width: 900px;
}

.field-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 1rem;
  margin-bottom: 1rem;
}

.field {
  display: block;
}

.field--wide {
  grid-column: 1 / -1;
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
  font-family: inherit;
}

.field-input:focus-visible {
  border-color: var(--color-amber);
  box-shadow: 0 0 0 3px rgba(224, 151, 58, 0.25);
  outline: none;
}

.form-error {
  color: var(--color-danger);
  background: rgba(181, 83, 63, 0.08);
  border: 1px solid rgba(181, 83, 63, 0.25);
  border-radius: 8px;
  padding: 0.6rem 0.75rem;
  font-size: 0.82rem;
  margin: 0 0 1rem;
}

.create-button {
  font-size: 0.85rem;
  font-weight: 600;
  padding: 0.55rem 1.1rem;
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

.loading-note {
  color: var(--color-slate);
}

.shifts-table {
  width: 100%;
  border-collapse: collapse;
  background: #fff;
  border: 1px solid var(--color-line);
  border-radius: 10px;
  overflow: hidden;
}

.shifts-table th {
  text-align: left;
  font-size: 0.74rem;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  color: var(--color-slate);
  padding: 0.75rem 1rem;
  border-bottom: 1px solid var(--color-line);
  background: var(--color-paper);
}

.shifts-table td {
  padding: 0.7rem 1rem;
  border-bottom: 1px solid var(--color-line);
  font-size: 0.85rem;
}

.shifts-table tr:last-child td {
  border-bottom: none;
}

.confirmed-names {
  display: block;
  font-size: 0.74rem;
  color: var(--color-slate);
  margin-top: 0.15rem;
}

.status-chip {
  font-size: 0.74rem;
  font-weight: 600;
  padding: 0.15rem 0.55rem;
  border-radius: 999px;
  background: rgba(74, 90, 106, 0.12);
  color: var(--color-slate);
  text-transform: capitalize;
}

.status-chip--open {
  background: rgba(76, 139, 108, 0.15);
  color: var(--color-green);
}

.status-chip--cancelled {
  background: rgba(181, 83, 63, 0.12);
  color: var(--color-danger);
}

.actions-cell {
  white-space: nowrap;
}

.text-action {
  background: none;
  border: none;
  padding: 0;
  margin-right: 0.9rem;
  color: var(--color-amber-dark);
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
}

.text-action:last-child {
  margin-right: 0;
}

.text-action--danger {
  color: var(--color-danger);
}

.edit-form {
  background: var(--color-paper);
  border-radius: 8px;
  padding: 1.25rem;
  margin: 0.25rem 0;
}

.empty-note {
  color: var(--color-slate);
  font-style: italic;
  font-size: 0.85rem;
}

.applicants-panel {
  background: var(--color-paper);
  border-radius: 8px;
  padding: 1.25rem;
  margin: 0.25rem 0;
}

.applicants-heading {
  font-family: var(--font-display);
  font-size: 0.82rem;
  font-weight: 700;
  color: var(--color-slate);
  margin: 0 0 0.6rem;
}

.applicants-heading--spaced {
  margin-top: 1.25rem;
}

.applicant-list {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}

.applicant-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  background: #fff;
  border: 1px solid var(--color-line);
  border-radius: 8px;
  padding: 0.55rem 0.8rem;
}

.applicant-name {
  font-size: 0.85rem;
  font-weight: 600;
  margin-right: 0.6rem;
}

.applicant-role {
  font-size: 0.76rem;
  color: var(--color-slate);
  margin-right: 0.6rem;
}

.applicant-status {
  font-size: 0.7rem;
  font-weight: 600;
  padding: 0.1rem 0.5rem;
  border-radius: 999px;
  background: rgba(74, 90, 106, 0.12);
  color: var(--color-slate);
  text-transform: capitalize;
}

.applicant-status--confirmed {
  background: rgba(76, 139, 108, 0.15);
  color: var(--color-green);
}

.applicant-status--waitlisted,
.applicant-status--pending {
  background: rgba(224, 151, 58, 0.18);
  color: var(--color-amber-dark);
}

.applicant-warning {
  display: block;
  font-size: 0.74rem;
  color: var(--color-danger);
  margin-top: 0.25rem;
}

.assign-button {
  flex-shrink: 0;
  font-size: 0.78rem;
  font-weight: 600;
  padding: 0.4rem 0.85rem;
  color: var(--color-ink);
  background: var(--color-amber);
  border: none;
  border-radius: 8px;
  cursor: pointer;
  white-space: nowrap;
}

.assign-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.remove-button {
  flex-shrink: 0;
  font-size: 0.78rem;
  font-weight: 600;
  padding: 0.4rem 0.85rem;
  color: var(--color-danger);
  background: #fff;
  border: 1px solid rgba(181, 83, 63, 0.35);
  border-radius: 8px;
  cursor: pointer;
  white-space: nowrap;
}

.remove-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
