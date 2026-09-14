<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import AppShell from '@/components/layout/AppShell.vue'
import { useAuthStore } from '@/stores/auth'
import {
  fetchWorker,
  updateWorker,
  fetchEmployment,
  updateEmployment,
  fetchContracts,
  createContract,
  updateContract,
  downloadContractFile,
  viewContractFile,
  fetchBranches,
  fetchWorkerDocuments,
  reviewDocument,
  downloadDocument,
  viewDocument,
} from '@/api/workers'
import { fetchCustomFields, fetchWorkerAnswers } from '@/api/customFields'
import { fetchDocumentTypes } from '@/api/documentTypes'
import { COUNTRIES } from '@/constants/countries'
import { LANGUAGES } from '@/constants/languages'

const LANGUAGE_OPTIONS = ['german', 'english', 'italian', 'french']

const TABS = [
  { key: 'profile', label: 'Profile' },
  { key: 'employment', label: 'Employment' },
  { key: 'skills', label: 'Skills' },
  { key: 'documents', label: 'Documents' },
  { key: 'contracts', label: 'Contracts' },
]
const activeTab = ref('profile')

const route = useRoute()
const userId = route.params.userId
const auth = useAuthStore()

/**
 * This whole page needs users.manage (personal details/employment/
 * contracts) and documents.review (documents + skill answers, same
 * self-or-users.manage rule on the backend) — a Company Admin has both
 * by default; a Dispatcher only gets in if an admin has specifically
 * granted them (see the Roles page).
 */
const hasAccess = computed(() => auth.can('users.manage') && auth.can('documents.review'))

const worker = ref(null)
const employment = ref(null)
const contracts = ref([])
const branches = ref([])
const documents = ref([])
const skillFields = ref([])
const skillAnswers = ref([])
const personalDocTypes = ref([])
const workDocTypes = ref([])

const loading = ref(true)
const errorMessage = ref('')
const savingWorker = ref(false)
const savingEmployment = ref(false)

async function loadAll() {
  if (!hasAccess.value) {
    loading.value = false
    return
  }

  loading.value = true
  errorMessage.value = ''
  try {
    const [w, e, c, b, d, sf, sa, pdt, wdt] = await Promise.all([
      fetchWorker(userId),
      fetchEmployment(userId),
      fetchContracts(userId),
      fetchBranches(),
      fetchWorkerDocuments(userId),
      fetchCustomFields('skill'),
      fetchWorkerAnswers(userId),
      fetchDocumentTypes('personal'),
      fetchDocumentTypes('work'),
    ])
    worker.value = { ...w, date_of_birth: w.date_of_birth?.slice(0, 10) || '' }
    employment.value = e
    contracts.value = c
    branches.value = b
    documents.value = d
    skillFields.value = sf
    skillAnswers.value = sa
    personalDocTypes.value = pdt
    workDocTypes.value = wdt
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
    const updated = await updateWorker(userId, worker.value)
    worker.value = { ...updated, date_of_birth: updated.date_of_birth?.slice(0, 10) || '' }
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

// --- Skills (read-only — these are the worker's own self-reported answers) ---
function skillAnswerFor(fieldId) {
  return skillAnswers.value.find((a) => a.custom_field_definition_id === fieldId)?.value
}

function formatSkillAnswer(field) {
  const raw = skillAnswerFor(field.id)
  if (raw === undefined || raw === null || raw === '') return '—'
  if (field.field_type === 'boolean') return raw === 'true' ? 'Yes' : 'No'
  if (field.field_type === 'multi_select') {
    try {
      const list = JSON.parse(raw)
      return Array.isArray(list) && list.length > 0 ? list.join(', ') : '—'
    } catch {
      return raw
    }
  }
  return raw
}

// --- Documents, split by category ---
const personalDocuments = computed(() =>
  documents.value.filter((d) => personalDocTypes.value.some((t) => t.key === d.document_type))
)
const workDocuments = computed(() =>
  documents.value.filter((d) => workDocTypes.value.some((t) => t.key === d.document_type))
)

function docTypeLabel(key) {
  return [...personalDocTypes.value, ...workDocTypes.value].find((t) => t.key === key)?.label || key
}

function sanitizeForFilename(text) {
  return (text || '').trim().replace(/\s+/g, '_')
}

function workerFileNameBase() {
  return sanitizeForFilename(`${worker.value?.first_name || ''}_${worker.value?.last_name || ''}`)
}

const rejectingId = ref(null)
const rejectionReason = ref('')

async function handleApprove(doc) {
  const updated = await reviewDocument(doc.id, { decision: 'approved' })
  documents.value = documents.value.map((d) => (d.id === doc.id ? updated : d))
}

function startReject(doc) {
  rejectingId.value = doc.id
  rejectionReason.value = ''
}

async function confirmReject(doc) {
  const updated = await reviewDocument(doc.id, {
    decision: 'rejected',
    rejection_reason: rejectionReason.value || 'Not specified',
  })
  documents.value = documents.value.map((d) => (d.id === doc.id ? updated : d))
  rejectingId.value = null
}

function handleDocumentDownload(doc) {
  const extension = doc.file_path?.split('.').pop() || 'bin'
  const date = doc.created_at?.slice(0, 10) || ''
  downloadDocument(doc.id, `${doc.document_type}.${workerFileNameBase()}.${date}.${extension}`)
}

function handleDocumentView(doc) {
  viewDocument(doc.id)
}

// --- Contracts ---
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

function handleContractDownload(contract) {
  const extension = contract.file_path?.split('.').pop() || 'pdf'
  const date = contract.created_at?.slice(0, 10) || ''
  downloadContractFile(userId, contract.id, `${contract.contract_type}.${workerFileNameBase()}.${date}.${extension}`)
}

function handleContractView(contract) {
  viewContractFile(userId, contract.id)
}

const activeContract = computed(() => contracts.value.find((c) => c.status === 'active'))
</script>

<template>
  <AppShell>
    <div v-if="!hasAccess" class="access-denied">
      <h1 class="page-title">Users &amp; documents</h1>
      <p class="access-denied-text">
        You don't have permission to view a worker's full profile. This needs
        <strong>users.manage</strong> and <strong>documents.review</strong> — ask a Company
        Admin to grant these on the Roles page if you need access.
      </p>
    </div>

    <p v-else-if="errorMessage" class="error-banner" role="alert">{{ errorMessage }}</p>
    <p v-else-if="loading" class="loading-note">Loading…</p>

    <template v-else-if="worker">
      <h1 class="page-title">{{ worker.first_name || '' }} {{ worker.last_name || '' }}</h1>
      <p class="page-lead">Personal record, employment relationship, skills, documents, and contracts.</p>

      <div class="tabs">
        <button
          v-for="tab in TABS"
          :key="tab.key"
          class="tab-button"
          :class="{ 'tab-button--active': activeTab === tab.key }"
          @click="activeTab = tab.key"
        >
          {{ tab.label }}
        </button>
      </div>

      <!-- PROFILE -->
      <section v-if="activeTab === 'profile'" class="panel">
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
            <span class="field-label">Gender</span>
            <select v-model="worker.gender" class="field-input">
              <option value="">—</option>
              <option value="female">Female</option>
              <option value="male">Male</option>
              <option value="diverse">Diverse</option>
            </select>
          </label>
          <label class="field">
            <span class="field-label">Marital status</span>
            <select v-model="worker.marital_status" class="field-input">
              <option value="">—</option>
              <option value="single">Single</option>
              <option value="married">Married</option>
              <option value="separated">Separated</option>
              <option value="widowed">Widowed</option>
              <option value="registered_partnership">Registered partnership</option>
            </select>
          </label>
          <label class="field">
            <span class="field-label">Nationality</span>
            <select v-model="worker.nationality" class="field-input">
              <option value="">—</option>
              <option v-for="c in COUNTRIES" :key="c" :value="c">{{ c }}</option>
            </select>
          </label>
          <label class="field">
            <span class="field-label">Native language</span>
            <select v-model="worker.native_language" class="field-input">
              <option value="">—</option>
              <option v-for="l in LANGUAGES" :key="l" :value="l">{{ l }}</option>
            </select>
          </label>
          <label class="field">
            <span class="field-label">Social security number</span>
            <input v-model="worker.social_security_number" type="text" maxlength="10" class="field-input" />
          </label>
          <label class="field">
            <span class="field-label">German level</span>
            <select v-model="worker.german_language_level" class="field-input">
              <option value="">—</option>
              <option value="none">None</option>
              <option value="basic">Basic</option>
              <option value="conversational">Conversational</option>
              <option value="fluent">Fluent</option>
              <option value="native">Native</option>
            </select>
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
        </div>

        <div class="languages-field">
          <span class="field-label">Other languages spoken</span>
          <div class="language-options">
            <label v-for="lang in LANGUAGE_OPTIONS" :key="lang" class="language-option">
              <input type="checkbox" :value="lang" v-model="worker.languages_spoken" />
              {{ lang.charAt(0).toUpperCase() + lang.slice(1) }}
            </label>
          </div>
        </div>

        <h3 class="subsection-title">Address</h3>
        <div class="field-grid">
          <label class="field">
            <span class="field-label">Street</span>
            <input v-model="worker.street" type="text" class="field-input" />
          </label>
          <label class="field">
            <span class="field-label">House number</span>
            <input v-model="worker.house_number" type="text" class="field-input" />
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
            <select v-model="worker.country" class="field-input">
              <option value="">—</option>
              <option v-for="c in COUNTRIES" :key="c" :value="c">{{ c }}</option>
            </select>
          </label>
          <label class="field">
            <span class="field-label">Residence type</span>
            <select v-model="worker.residence_type" class="field-input">
              <option value="">—</option>
              <option value="main">Main (Hauptwohnsitz)</option>
              <option value="secondary">Secondary (Nebenwohnsitz)</option>
            </select>
          </label>
        </div>

        <h3 class="subsection-title">Bank details</h3>
        <div class="field-grid">
          <label class="field">
            <span class="field-label">Bank name</span>
            <input v-model="worker.bank_name" type="text" class="field-input" />
          </label>
          <label class="field">
            <span class="field-label">Account holder</span>
            <input v-model="worker.bank_account_holder_name" type="text" class="field-input" />
          </label>
          <label class="field">
            <span class="field-label">IBAN</span>
            <input v-model="worker.iban" type="text" class="field-input" />
          </label>
          <label class="field">
            <span class="field-label">BIC</span>
            <input v-model="worker.bic" type="text" class="field-input" />
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

      <!-- EMPLOYMENT -->
      <section v-if="activeTab === 'employment'" class="panel">
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

      <!-- SKILLS -->
      <section v-if="activeTab === 'skills'" class="panel">
        <h2 class="panel-title">Skills</h2>
        <p class="panel-sublead">
          The worker's own answers to your company's skill questions — read-only here; they
          edit these from their own profile. Manage the question list itself from the Custom
          Fields page.
        </p>

        <div v-if="skillFields.length > 0" class="skills-list">
          <div v-for="field in skillFields" :key="field.id" class="skill-row">
            <span class="skill-label">{{ field.label }}</span>
            <span class="skill-value">{{ formatSkillAnswer(field) }}</span>
          </div>
        </div>
        <p v-else class="empty-note">No skill questions have been set up for your company yet.</p>
      </section>

      <!-- DOCUMENTS -->
      <section v-if="activeTab === 'documents'" class="panel">
        <h2 class="panel-title">Personal documents</h2>
        <p class="panel-sublead">Identity-type documents — photo, passport, insurance card, etc.</p>
        <div v-if="personalDocuments.length > 0" class="doc-list">
          <div v-for="doc in personalDocuments" :key="doc.id" class="doc-row">
            <div class="doc-row-top">
              <span class="doc-type">{{ docTypeLabel(doc.document_type) }}</span>
              <span class="doc-status" :class="`doc-status--${doc.review_status}`">{{ doc.review_status }}</span>
            </div>
            <div class="doc-row-actions">
              <button class="text-action" @click="handleDocumentView(doc)">View</button>
              <button class="text-action" @click="handleDocumentDownload(doc)">Download</button>
              <template v-if="doc.review_status === 'pending'">
                <button class="text-action text-action--approve" @click="handleApprove(doc)">Approve</button>
                <button class="text-action text-action--reject" @click="startReject(doc)">Reject</button>
              </template>
            </div>
            <div v-if="rejectingId === doc.id" class="reject-form">
              <input v-model="rejectionReason" type="text" class="field-input" placeholder="Reason for rejection" />
              <button class="save-button save-button--small" @click="confirmReject(doc)">Confirm reject</button>
            </div>
            <p v-if="doc.review_status === 'rejected' && doc.rejection_reason" class="rejection-reason">
              {{ doc.rejection_reason }}
            </p>
          </div>
        </div>
        <p v-else class="empty-note">No personal documents uploaded yet.</p>

        <h2 class="panel-title panel-title--spaced">Work documents</h2>
        <p class="panel-sublead">Job/event-related uploads — per-event or end-of-month documents.</p>
        <div v-if="workDocuments.length > 0" class="doc-list">
          <div v-for="doc in workDocuments" :key="doc.id" class="doc-row">
            <div class="doc-row-top">
              <span class="doc-type">{{ docTypeLabel(doc.document_type) }}</span>
              <span class="doc-status" :class="`doc-status--${doc.review_status}`">{{ doc.review_status }}</span>
            </div>
            <div class="doc-row-actions">
              <button class="text-action" @click="handleDocumentView(doc)">View</button>
              <button class="text-action" @click="handleDocumentDownload(doc)">Download</button>
              <template v-if="doc.review_status === 'pending'">
                <button class="text-action text-action--approve" @click="handleApprove(doc)">Approve</button>
                <button class="text-action text-action--reject" @click="startReject(doc)">Reject</button>
              </template>
            </div>
            <div v-if="rejectingId === doc.id" class="reject-form">
              <input v-model="rejectionReason" type="text" class="field-input" placeholder="Reason for rejection" />
              <button class="save-button save-button--small" @click="confirmReject(doc)">Confirm reject</button>
            </div>
            <p v-if="doc.review_status === 'rejected' && doc.rejection_reason" class="rejection-reason">
              {{ doc.rejection_reason }}
            </p>
          </div>
        </div>
        <p v-else class="empty-note">No work documents uploaded yet.</p>
      </section>

      <!-- CONTRACTS -->
      <section v-if="activeTab === 'contracts'" class="panel">
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
                <option value="assignment_notice">Überlassungsmitteilung</option>
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
              <td>
                <template v-if="c.has_file">
                  <button class="text-action" @click="handleContractView(c)">View</button>
                  <button class="text-action" @click="handleContractDownload(c)">Download</button>
                </template>
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

.access-denied {
  max-width: 480px;
}

.access-denied-text {
  color: var(--color-slate);
  font-size: 0.9rem;
  line-height: 1.6;
}

.loading-note {
  color: var(--color-slate);
}

.tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
  border-bottom: 1px solid var(--color-line);
  padding-bottom: 1rem;
}

.tab-button {
  font-size: 0.85rem;
  font-weight: 600;
  padding: 0.5rem 1rem;
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
  margin-bottom: 1.5rem;
  max-width: 900px;
}

.panel-title {
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 1.02rem;
  margin: 0 0 0.4rem;
}

.panel-title--spaced {
  margin-top: 2rem;
}

.panel-sublead {
  font-size: 0.83rem;
  color: var(--color-slate);
  margin: 0 0 1.1rem;
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

.languages-field {
  margin-top: 0.9rem;
  padding-top: 0.75rem;
  border-top: 1px dashed var(--color-line);
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
  font-size: 0.82rem;
  font-weight: 400;
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

.save-button--small {
  margin-top: 0;
  padding: 0.35rem 0.7rem;
  font-size: 0.78rem;
}

.save-button:hover:not(:disabled) {
  background: var(--color-amber-dark);
}

.save-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.skills-list {
  display: flex;
  flex-direction: column;
}

.skill-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.7rem 0;
  border-bottom: 1px solid var(--color-line);
}

.skill-row:last-child {
  border-bottom: none;
}

.skill-label {
  font-size: 0.85rem;
  font-weight: 600;
}

.skill-value {
  font-size: 0.85rem;
  color: var(--color-slate);
}

.doc-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.doc-row {
  border: 1px solid var(--color-line);
  border-radius: 8px;
  padding: 0.7rem 0.85rem;
}

.doc-row-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}

.doc-type {
  font-size: 0.83rem;
  font-weight: 600;
}

.doc-status {
  font-size: 0.72rem;
  font-weight: 600;
  padding: 0.12rem 0.5rem;
  border-radius: 999px;
  background: rgba(74, 90, 106, 0.12);
  color: var(--color-slate);
  text-transform: capitalize;
}

.doc-status--approved {
  background: rgba(76, 139, 108, 0.15);
  color: var(--color-green);
}

.doc-status--rejected {
  background: rgba(181, 83, 63, 0.12);
  color: var(--color-danger);
}

.doc-row-actions {
  display: flex;
  gap: 1rem;
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

.text-action--approve {
  color: var(--color-green);
}

.text-action--reject {
  color: var(--color-danger);
}

.reject-form {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.6rem;
}

.reject-form .field-input {
  flex: 1;
}

.rejection-reason {
  font-size: 0.78rem;
  color: var(--color-danger);
  margin: 0.5rem 0 0;
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
