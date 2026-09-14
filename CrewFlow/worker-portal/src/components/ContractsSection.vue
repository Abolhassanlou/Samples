<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { fetchContracts, signContract, downloadContract } from '@/api/contracts'

const auth = useAuthStore()

const contracts = ref([])
const loading = ref(true)
const signingId = ref(null)

const CONTRACT_TYPE_LABELS = {
  employment_contract: 'Echter Dienstvertrag',
  free_service_contract: 'Freier Dienstvertrag',
  work_contract: 'Werkvertrag',
  assignment_notice: 'Überlassungsmitteilung',
}

async function loadContracts() {
  loading.value = true
  contracts.value = await fetchContracts(auth.user.id)
  loading.value = false
}

onMounted(loadContracts)

async function handleSign(contract) {
  signingId.value = contract.id
  try {
    await signContract(auth.user.id, contract.id)
    await loadContracts()
  } finally {
    signingId.value = null
  }
}

/**
 * Bug fix: a hardcoded ".pdf" broke for any contract file that isn't
 * actually a PDF (the upload form accepts jpg/png too). Pull the real
 * extension from file_path, now exposed on EmploymentContractResource
 * for exactly this reason.
 */
function handleDownload(contract) {
  const extension = contract.file_path?.split('.').pop() || 'pdf'
  downloadContract(auth.user.id, contract.id, `contract-${contract.id}.${extension}`)
}
</script>

<template>
  <div>
    <p v-if="loading" class="coming-soon">Loading…</p>

    <template v-else>
      <div v-for="contract in contracts" :key="contract.id" class="contract-card">
        <div class="contract-top">
          <span class="contract-type">{{ CONTRACT_TYPE_LABELS[contract.contract_type] || contract.contract_type }}</span>
          <span class="contract-status" :class="`contract-status--${contract.status}`">{{ contract.status }}</span>
        </div>
        <p class="contract-dates">
          {{ contract.start_date?.slice(0, 10) }} → {{ contract.is_permanent ? 'ongoing' : contract.end_date?.slice(0, 10) }}
        </p>

        <div class="contract-actions">
          <button v-if="contract.has_file" class="text-action" @click="handleDownload(contract)">
            Download document
          </button>
          <span v-else class="no-file-note">No document attached yet</span>

          <button
            v-if="contract.status === 'pending_signature'"
            class="sign-button"
            :disabled="signingId === contract.id"
            @click="handleSign(contract)"
          >
            {{ signingId === contract.id ? 'Signing…' : 'Sign' }}
          </button>
        </div>
      </div>

      <p v-if="contracts.length === 0" class="coming-soon">No contracts yet.</p>
    </template>
  </div>
</template>

<style scoped>
.coming-soon {
  font-size: 0.82rem;
  color: var(--color-slate);
  margin: 0;
}

.contract-card {
  border: 1px solid var(--color-line);
  border-radius: 8px;
  padding: 0.85rem;
  margin-bottom: 0.6rem;
}

.contract-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.contract-type {
  font-size: 0.85rem;
  font-weight: 600;
}

.contract-status {
  font-size: 0.72rem;
  font-weight: 600;
  padding: 0.12rem 0.5rem;
  border-radius: 999px;
  background: rgba(74, 90, 106, 0.12);
  color: var(--color-slate);
  text-transform: capitalize;
}

.contract-status--active {
  background: rgba(76, 139, 108, 0.15);
  color: var(--color-green);
}

.contract-status--pending_signature {
  background: rgba(224, 151, 58, 0.18);
  color: var(--color-amber-dark);
}

.contract-status--terminated,
.contract-status--cancelled,
.contract-status--expired {
  background: rgba(181, 83, 63, 0.12);
  color: var(--color-danger);
}

.contract-dates {
  font-size: 0.78rem;
  color: var(--color-slate);
  margin: 0.35rem 0 0.6rem;
}

.contract-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.6rem;
}

.text-action {
  background: none;
  border: none;
  padding: 0;
  color: var(--color-amber-dark);
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
}

.no-file-note {
  font-size: 0.78rem;
  color: var(--color-slate);
  font-style: italic;
}

.sign-button {
  font-size: 0.8rem;
  font-weight: 600;
  padding: 0.4rem 0.9rem;
  color: var(--color-ink);
  background: var(--color-amber);
  border: none;
  border-radius: 6px;
  cursor: pointer;
}

.sign-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
