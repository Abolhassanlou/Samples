<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import AppShell from '@/components/layout/AppShell.vue'
import {
  fetchWorker,
  updateWorker,
  fetchEmployment,
  updateEmployment,
  fetchContracts,
  createContract,
  updateContract,
  fetchBranches,
} from '@/api/workers'

const route = useRoute()
const userId = route.params.userId

const worker = ref(null)
const employment = ref(null)
const contracts = ref([])
const branches = ref([])

const loading = ref(true)
const errorMessage = ref('')
const savingWorker = ref(false)
const savingEmployment = ref(false)

async function loadAll() {
  loading.value = true
  errorMessage.value = ''
  try {
    const [w, e, c, b] = await Promise.all([
      fetchWorker(userId),
      fetchEmployment(userId),
      fetchContracts(userId),
      fetchBranches(),
    ])
    worker.value = w
    employment.value = e
    contracts.value = c
    branches.value = b
  } catch {
    errorMessage.value = 'Could not load this worker. Check your connection and try again.'
  } finally {
    loading.value = false
  }
}

onMounted(loadAll)

async function saveWorker() {
  savingWorker.value = true
  try {
    worker.value = await updateWorker(userId, worker.value)
  } finally {
    savingWorker.value = false
  }
}

async function saveEmployment() {
  savingEmployment.value = true
  try {
    employment.value = await updateEmployment(userId, employment.value)
  } finally {
    savingEmployment.value = false
  }
}

// --- New contract form ---
const showNewContractForm = ref(false)
const newContract = ref({
  contract_number: '',
  contract_type: 'employment_contract',
  work_time_model: 'full_time',
  is_marginal: false,
  weekly_hours: '',
  start_date: '',
  end_date: '',
  status: 'draft',
  notes: '',
})
const creatingContract = ref(false)

async function submitNewContract() {
  creatingContract.value = true
  try {
    const payload = { ...newContract.value }
    if (!payload.end_date) delete payload.end_date
    if (!payload.weekly_hours) delete payload.weekly_hours
    const created = await createContract(userId, payload)
    contracts.value = [created, ...contracts.value]
    showNewContractForm.value = false
    newContract.value = {
      contract_number: '',
      contract_type: 'employment_contract',
      work_time_model: 'full_time',
      is_marginal: false,
      weekly_hours: '',
      start_date: '',
      end_date: '',
      status: 'draft',
      notes: '',
    }
  } finally {
    creatingContract.value = false
  }
}

async function setContractStatus(contract, status) {
  const updated = await updateContract(userId, contract.id, { status })
  contracts.value = contracts.value.map((c) => (c.id === contract.id ? updated : c))
}

const activeContract = computed(() => contracts.value.find((c) => c.status === 'active'))
</script>

<template>
  <AppShell>
    <p v-if="errorMessage" class="error-banner" role="alert">{{ errorMessage }}</p>
    <p v-else-if="loading" class="loading-note">Loading…</p>

    <template v-else-if="worker">
      <h1 class="page-title">{{ worker.first_name || '' }} {{ worker.last_name || '' }}</h1>
      <p class="page-lead">Personal record, employment relationship, and contract history.</p>

      <div class="two-col">
        <section class="panel">
          <h2 class="panel-title">Personal details</h2>
          <div class="field-grid">
            <label class="field">
              <span class="field-label">First name</span>
              <input v-model="worker.first_name" type="text" class="field-input" />
            </label>
            <label class="field">
              <span class="field-label">Last name</span>
              <input v-model="worker.last_name" type="text" class="field-input" />
            </label>
            <label class="field">
              <span class="field-label">Date of birth</span>
              <input v-model="worker.date_of_birth" type="date" class="field-input" />
            </label>
            <label class="field">
              <span class="field-label">Status</span>
              <select v-model="worker.status" class="field-input">
                <option value="pending">Pending</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="blocked">Blocked</option>
              </select>
            </label>
            <label class="field">
              <span class="field-label">Address</span>
              <input v-model="worker.address" type="text" class="field-input" />
            </label>
            <label class="field">
              <span class="field-label">Postal code</span>
              <input v-model="worker.postal_code" type="text" class="field-input" />
            </label>
            <label class="field">
              <span class="field-label">City</span>
              <input v-model="worker.city" type="text" class="field-input" />
            </label>
            <label class="field">
              <span class="field-label">Country</span>
              <input v-model="worker.country" type="text" class="field-input" />
            </label>
          </div>

          <h3 class="subsection-title">Work authorization</h3>
          <div class="field-grid">
            <label class="field">
              <span class="field-label">Status</span>
              <select v-model="worker.work_authorization_status" class="field-input">
                <option value="pending">Pending</option>
                <option value="valid">Valid</option>
                <option value="expired">Expired</option>
                <option value="not_required">Not required</option>
                <option value="rejected">Rejected</option>
              </select>
            </label>
            <label class="field">
              <span class="field-label">Type</span>
              <input v-model="worker.work_authorization_type" type="text" class="field-input" />
            </label>
            <label class="field">
              <span class="field-label">Expiry date</span>
              <input v-model="worker.work_authorization_expiry_date" type="date" class="field-input" />
            </label>
          </div>

          <button class="save-button" :disabled="savingWorker" @click="saveWorker">
            {{ savingWorker ? 'Saving…' : 'Save personal details' }}
          </button>
        </section>

        <section class="panel">
          <h2 class="panel-title">Employment relationship</h2>
          <div class="field-grid">
            <label class="field">
              <span class="field-label">Employee number</span>
              <input v-model="employment.employee_number" type="text" class="field-input" placeholder="optional" />
            </label>
            <label class="field">
              <span class="field-label">Home branch</span>
              <select v-model="employment.home_branch_id" class="field-input">
                <option :value="null">No branch set</option>
                <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
              </select>
            </label>
            <label class="field">
              <span class="field-label">Status</span>
              <select v-model="employment.status" class="field-input">
                <option value="invited">Invited</option>
                <option value="pending">Pending</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="blocked">Blocked</option>
              </select>
            </label>
            <label class="field">
              <span class="field-label">Joined</span>
              <input v-model="employment.joined_at" type="date" class="field-input" />
            </label>
            <label class="field">
              <span class="field-label">Left</span>
              <input v-model="employment.left_at" type="date" class="field-input" />
            </label>
          </div>
          <label class="night-checkbox">
            <input type="checkbox" v-model="employment.works_night_shifts" />
            Willing to work night shifts
          </label>

          <button class="save-button" :disabled="savingEmployment" @click="saveEmployment">
            {{ savingEmployment ? 'Saving…' : 'Save employment relationship' }}
          </button>
        </section>
      </div>

      <section class="panel">
        <div class="contracts-header">
          <div>
            <h2 class="panel-title">Contracts</h2>
            <p v-if="activeContract" class="active-contract-note">
              Currently active: {{ activeContract.contract_type }} · {{ activeContract.work_time_model }}
              <span v-if="activeContract.is_marginal">· marginal (Geringfügig)</span>
              · {{ activeContract.is_permanent ? 'permanent' : `until ${activeContract.end_date}` }}
            </p>
            <p v-else class="no-active-contract-note">No active contract — this worker cannot be assigned to shifts.</p>
          </div>
          <button class="add-contract-button" @click="showNewContractForm = !showNewContractForm">
            {{ showNewContractForm ? 'Cancel' : '+ New contract' }}
          </button>
        </div>

        <form v-if="showNewContractForm" class="new-contract-form" @submit.prevent="submitNewContract">
          <div class="field-grid">
            <label class="field">
              <span class="field-label">Contract number</span>
              <input v-model="newContract.contract_number" type="text" class="field-input" placeholder="optional" />
            </label>
            <label class="field">
              <span class="field-label">Contract type</span>
              <select v-model="newContract.contract_type" class="field-input">
                <option value="employment_contract">Echter Dienstvertrag</option>
                <option value="free_service_contract">Freier Dienstvertrag</option>
                <option value="work_contract">Werkvertrag</option>
              </select>
            </label>
            <label class="field">
              <span class="field-label">Work time model</span>
              <select v-model="newContract.work_time_model" class="field-input">
                <option value="full_time">Vollzeit</option>
                <option value="part_time">Teilzeit</option>
                <option value="casual">Fallweise Beschäftigung</option>
              </select>
            </label>
            <label class="field">
              <span class="field-label">Weekly hours</span>
              <input v-model="newContract.weekly_hours" type="number" min="0" step="0.5" class="field-input" placeholder="optional" />
            </label>
            <label class="field">
              <span class="field-label">Start date</span>
              <input v-model="newContract.start_date" type="date" class="field-input" required />
            </label>
            <label class="field">
              <span class="field-label">End date</span>
              <input v-model="newContract.end_date" type="date" class="field-input" placeholder="blank = permanent" />
            </label>
            <label class="field">
              <span class="field-label">Status</span>
              <select v-model="newContract.status" class="field-input">
                <option value="draft">Draft</option>
                <option value="pending_signature">Pending signature</option>
                <option value="active">Active</option>
              </select>
            </label>
          </div>
          <label class="marginal-checkbox">
            <input type="checkbox" v-model="newContract.is_marginal" />
            Marginal employment (Geringfügig) — independent of the work time model above
          </label>
          <label class="field notes-field">
            <span class="field-label">Notes</span>
            <textarea v-model="newContract.notes" class="field-input" rows="2"></textarea>
          </label>
          <button type="submit" class="save-button" :disabled="creatingContract">
            {{ creatingContract ? 'Creating…' : 'Create contract' }}
          </button>
        </form>

        <table v-if="contracts.length > 0" class="contracts-table">
          <thead>
            <tr>
              <th>Type</th>
              <th>Model</th>
              <th>Marginal</th>
              <th>Dates</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in contracts" :key="c.id">
              <td>{{ c.contract_type }}</td>
              <td>{{ c.work_time_model }}</td>
              <td>{{ c.is_marginal ? 'Yes' : '—' }}</td>
              <td>{{ c.start_date }} → {{ c.is_permanent ? 'permanent' : c.end_date }}</td>
              <td><span class="status-chip" :class="`status-chip--${c.status}`">{{ c.status }}</span></td>
              <td>
                <select :value="c.status" class="status-select" @change="setContractStatus(c, $event.target.value)">
                  <option value="draft">draft</option>
                  <option value="pending_signature">pending_signature</option>
                  <option value="active">active</option>
                  <option value="expired">expired</option>
                  <option value="terminated">terminated</option>
                  <option value="cancelled">cancelled</option>
                </select>
              </td>
            </tr>
          </tbody>
        </table>
        <p v-else class="empty-note">No contracts yet.</p>
      </section>
    </template>
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
}

.error-banner {
  color: var(--color-danger);
  background: rgba(181, 83, 63, 0.08);
  border: 1px solid rgba(181, 83, 63, 0.25);
  border-radius: 8px;
  padding: 0.75rem 1rem;
}

.loading-note {
  color: var(--color-slate);
}

.two-col {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

@media (max-width: 900px) {
  .two-col {
    grid-template-columns: 1fr;
  }
}

.panel {
  background: #fff;
  border: 1px solid var(--color-line);
  border-radius: 10px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
}

.panel-title {
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 1.02rem;
  margin: 0 0 1rem;
}

.subsection-title {
  font-size: 0.85rem;
  font-weight: 700;
  color: var(--color-slate);
  margin: 1.25rem 0 0.75rem;
}

.field-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 0.9rem;
}

.field {
  display: block;
}

.field-label {
  display: block;
  font-size: 0.76rem;
  font-weight: 600;
  color: var(--color-slate);
  margin-bottom: 0.3rem;
}

.field-input {
  width: 100%;
  padding: 0.5rem 0.65rem;
  font-size: 0.85rem;
  border: 1px solid var(--color-line);
  border-radius: 8px;
  background: #fff;
  outline: none;
  font-family: inherit;
}

.field-input:focus-visible {
  border-color: var(--color-amber);
  box-shadow: 0 0 0 3px rgba(224, 151, 58, 0.25);
}

.notes-field {
  margin-top: 0.9rem;
}

.night-checkbox,
.marginal-checkbox {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.83rem;
  margin-top: 1rem;
}

.save-button {
  margin-top: 1.25rem;
  font-size: 0.85rem;
  font-weight: 600;
  padding: 0.5rem 1rem;
  color: var(--color-ink);
  background: var(--color-amber);
  border: none;
  border-radius: 8px;
  cursor: pointer;
}

.save-button:hover:not(:disabled) {
  background: var(--color-amber-dark);
}

.save-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.contracts-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1.25rem;
}

.active-contract-note {
  font-size: 0.85rem;
  color: var(--color-green);
  margin: 0.3rem 0 0;
}

.no-active-contract-note {
  font-size: 0.85rem;
  color: var(--color-danger);
  margin: 0.3rem 0 0;
}

.add-contract-button {
  flex-shrink: 0;
  font-size: 0.82rem;
  font-weight: 600;
  padding: 0.45rem 0.9rem;
  color: var(--color-ink);
  background: var(--color-paper);
  border: 1px solid var(--color-line);
  border-radius: 8px;
  cursor: pointer;
  white-space: nowrap;
}

.new-contract-form {
  background: var(--color-paper);
  border-radius: 8px;
  padding: 1.25rem;
  margin-bottom: 1.5rem;
}

.contracts-table {
  width: 100%;
  border-collapse: collapse;
}

.contracts-table th {
  text-align: left;
  font-size: 0.74rem;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  color: var(--color-slate);
  padding: 0 0.6rem 0.5rem;
  border-bottom: 1px solid var(--color-line);
}

.contracts-table td {
  padding: 0.6rem;
  border-bottom: 1px solid var(--color-line);
  font-size: 0.85rem;
}

.status-chip {
  font-size: 0.74rem;
  font-weight: 600;
  padding: 0.15rem 0.55rem;
  border-radius: 999px;
  background: rgba(74, 90, 106, 0.12);
  color: var(--color-slate);
}

.status-chip--active {
  background: rgba(76, 139, 108, 0.15);
  color: var(--color-green);
}

.status-chip--terminated,
.status-chip--cancelled,
.status-chip--expired {
  background: rgba(181, 83, 63, 0.12);
  color: var(--color-danger);
}

.status-select {
  font-size: 0.78rem;
  padding: 0.3rem 0.4rem;
  border: 1px solid var(--color-line);
  border-radius: 6px;
}

.empty-note {
  color: var(--color-slate);
  font-style: italic;
  font-size: 0.85rem;
}
</style>
