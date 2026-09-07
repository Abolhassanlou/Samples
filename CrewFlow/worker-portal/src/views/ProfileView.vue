<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import AppShell from '@/components/layout/AppShell.vue'
import AccordionItem from '@/components/AccordionItem.vue'
import { fetchMyDocuments, uploadDocument } from '@/api/documents'

const auth = useAuthStore()
const router = useRouter()

const DOCUMENT_TYPES = [
  { value: 'identity_document', label: 'Photo ID' },
  { value: 'residence_permit', label: 'Residence permit' },
  { value: 'work_permit', label: 'Work permit' },
  { value: 'social_security_card', label: 'Social security card' },
  { value: 'driving_license', label: 'Driving license' },
  { value: 'criminal_record', label: 'Criminal record check' },
  { value: 'certificate', label: 'Certificate' },
  { value: 'other', label: 'Other' },
]

const myDocuments = ref([])
const loadingDocuments = ref(true)

const uploadType = ref('identity_document')
const uploadFile = ref(null)
const uploading = ref(false)
const uploadError = ref('')

async function loadDocuments() {
  loadingDocuments.value = true
  try {
    myDocuments.value = await fetchMyDocuments()
  } finally {
    loadingDocuments.value = false
  }
}

onMounted(loadDocuments)

function handleFileChange(event) {
  uploadFile.value = event.target.files[0] || null
}

async function handleUpload() {
  if (!uploadFile.value) return

  uploadError.value = ''
  uploading.value = true
  try {
    await uploadDocument({ documentType: uploadType.value, file: uploadFile.value })
    uploadFile.value = null
    await loadDocuments()
  } catch (error) {
    uploadError.value = error.response?.data?.message || 'Could not upload this document.'
  } finally {
    uploading.value = false
  }
}

function documentTypeLabel(type) {
  return DOCUMENT_TYPES.find((t) => t.value === type)?.label || type
}

function handleLogout() {
  auth.logout()
  router.push('/login')
}
</script>

<template>
  <AppShell>
    <header class="header">
      <h1 class="title">Profile</h1>
    </header>

    <div class="id-card">
      <div class="avatar">{{ auth.user?.name?.charAt(0) }}</div>
      <div class="id-info">
        <span class="id-name">{{ auth.user?.name }}</span>
        <span class="id-company">{{ auth.companyCode }}</span>
      </div>
    </div>

    <div class="accordion-group">
      <AccordionItem label="My info" hint="Personal details, address, skills, bank details">
        <div class="nested-group">
          <AccordionItem label="Personal details" hint="Includes your address" nested>
            <p class="coming-soon">
              Coming soon — these questions are configured per company, which needs a
              company-configurable fields system on the backend first.
            </p>
          </AccordionItem>
          <AccordionItem label="Skills" hint="Size, car, license, experience, and more" nested>
            <p class="coming-soon">
              Coming soon — same as above, skill questions are company-defined.
            </p>
          </AccordionItem>
          <AccordionItem label="Bank details" nested>
            <p class="coming-soon">Coming soon.</p>
          </AccordionItem>
        </div>
      </AccordionItem>

      <AccordionItem label="Accounting" hint="Hours worked this month, per shift/event">
        <p class="coming-soon">Coming soon — needs shift/assignment data wired in.</p>
      </AccordionItem>

      <AccordionItem label="Payroll" hint="Pay calculations for hours worked">
        <p class="coming-soon">
          Coming soon. Priority: event-based pay (the hourly rate set on that event × hours
          worked). Daily-wage entry for full-time/part-time workers comes after, refined
          later.
        </p>
      </AccordionItem>

      <AccordionItem label="Documents" hint="Work contracts, and what you've uploaded">
        <div class="nested-group">
          <AccordionItem label="Work contracts" nested>
            <p class="coming-soon">Coming soon — shows your contract history.</p>
          </AccordionItem>

          <AccordionItem label="My uploads" nested>
            <p v-if="loadingDocuments" class="coming-soon">Loading…</p>

            <template v-else>
              <ul v-if="myDocuments.length > 0" class="doc-list">
                <li v-for="doc in myDocuments" :key="doc.id" class="doc-item">
                  <span class="doc-type">{{ documentTypeLabel(doc.document_type) }}</span>
                  <span class="doc-status" :class="`doc-status--${doc.review_status}`">{{ doc.review_status }}</span>
                </li>
              </ul>
              <p v-else class="coming-soon">No documents uploaded yet.</p>

              <div class="upload-form">
                <select v-model="uploadType" class="upload-select">
                  <option v-for="t in DOCUMENT_TYPES" :key="t.value" :value="t.value">{{ t.label }}</option>
                </select>
                <input type="file" accept=".pdf,.jpg,.jpeg,.png" @change="handleFileChange" class="upload-input" />
                <p v-if="uploadError" class="upload-error">{{ uploadError }}</p>
                <button class="upload-button" :disabled="!uploadFile || uploading" @click="handleUpload">
                  {{ uploading ? 'Uploading…' : 'Upload' }}
                </button>
              </div>
            </template>
          </AccordionItem>
        </div>
      </AccordionItem>

      <AccordionItem label="Share app" hint="Your referral code">
        <p class="coming-soon">Coming soon.</p>
      </AccordionItem>

      <AccordionItem label="Settings">
        <div class="nested-group">
          <AccordionItem label="Language" nested>
            <p class="coming-soon">Coming soon.</p>
          </AccordionItem>
          <AccordionItem label="Company" nested>
            <p class="coming-soon">{{ auth.companyCode }} — switching companies coming soon.</p>
          </AccordionItem>
          <AccordionItem label="Sign out" nested>
            <button class="logout-button" @click="handleLogout">Sign out</button>
          </AccordionItem>
          <AccordionItem label="Delete account" nested>
            <p class="coming-soon">Coming soon.</p>
          </AccordionItem>
        </div>
      </AccordionItem>
    </div>
  </AppShell>
</template>

<style scoped>
.header {
  padding: 1.25rem 1.25rem 0.5rem;
}

.title {
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 1.3rem;
  margin: 0;
  color: var(--color-ink);
}

.id-card {
  display: flex;
  align-items: center;
  gap: 0.9rem;
  margin: 0.75rem 1.25rem 1.25rem;
  padding: 1rem;
  background: #fff;
  border: 1px solid var(--color-line);
  border-radius: 12px;
}

.avatar {
  width: 46px;
  height: 46px;
  border-radius: 50%;
  background: var(--color-ink);
  color: var(--color-paper);
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 1.1rem;
  flex-shrink: 0;
}

.id-info {
  display: flex;
  flex-direction: column;
}

.id-name {
  font-weight: 700;
  font-size: 0.95rem;
}

.id-company {
  font-family: var(--font-mono);
  font-size: 0.78rem;
  color: var(--color-slate);
}

.accordion-group {
  margin: 0 1.25rem 1.5rem;
  background: #fff;
  border: 1px solid var(--color-line);
  border-radius: 12px;
  overflow: hidden;
}

.nested-group {
  border: 1px solid var(--color-line);
  border-radius: 8px;
  overflow: hidden;
}

.coming-soon {
  font-size: 0.82rem;
  color: var(--color-slate);
  line-height: 1.5;
  margin: 0;
}

.doc-list {
  list-style: none;
  padding: 0;
  margin: 0 0 1rem;
}

.doc-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.5rem 0;
  border-bottom: 1px solid var(--color-line);
  font-size: 0.82rem;
}

.doc-item:last-child {
  border-bottom: none;
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

.upload-form {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
  padding-top: 0.75rem;
  border-top: 1px dashed var(--color-line);
}

.upload-select,
.upload-input {
  font-size: 0.82rem;
  padding: 0.5rem 0.6rem;
  border: 1px solid var(--color-line);
  border-radius: 8px;
  background: #fff;
}

.upload-error {
  color: var(--color-danger);
  font-size: 0.78rem;
  margin: 0;
}

.upload-button {
  font-size: 0.85rem;
  font-weight: 600;
  padding: 0.55rem;
  color: var(--color-ink);
  background: var(--color-amber);
  border: none;
  border-radius: 8px;
  cursor: pointer;
}

.upload-button:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.logout-button {
  font-size: 0.85rem;
  font-weight: 600;
  padding: 0.55rem 1rem;
  color: var(--color-danger);
  background: #fff;
  border: 1px solid var(--color-line);
  border-radius: 8px;
  cursor: pointer;
}
</style>
