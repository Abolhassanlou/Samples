<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import AppShell from '@/components/layout/AppShell.vue'
import { fetchWorkers, fetchQualifications, fetchBranches } from '@/api/workers'

const DAY_LABELS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']

const workers = ref([])
const qualifications = ref([])
const branches = ref([])

const loading = ref(true)
const errorMessage = ref('')

const search = ref('')
const qualificationId = ref('')
const branchId = ref('')
const workTimeModel = ref('')
const nightShift = ref(false)
const eligibleOnly = ref(false)
const dayOfWeek = ref('')
const time = ref('')

const hasTimeFilter = computed(() => dayOfWeek.value !== '' && !!time.value)
const timeFilterIncomplete = computed(() => (dayOfWeek.value !== '') !== !!time.value)

async function loadOptions() {
  const [q, b] = await Promise.all([fetchQualifications(), fetchBranches()])
  qualifications.value = q
  branches.value = b
}

async function loadWorkers() {
  loading.value = true
  errorMessage.value = ''
  try {
    workers.value = await fetchWorkers({
      search: search.value,
      qualificationId: qualificationId.value,
      branchId: branchId.value,
      workTimeModel: workTimeModel.value,
      nightShift: nightShift.value,
      eligibleOnly: eligibleOnly.value,
      dayOfWeek: dayOfWeek.value,
      time: hasTimeFilter.value ? time.value : '',
    })
  } catch {
    errorMessage.value = 'Could not load workers. Check your connection and try again.'
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await loadOptions()
  await loadWorkers()
})

// Re-query whenever a filter changes, except while the day/time pair is
// half-filled (avoids firing a request with only one of the two set).
watch(
  [search, qualificationId, branchId, workTimeModel, nightShift, eligibleOnly, dayOfWeek, time],
  () => {
    if (timeFilterIncomplete.value) return
    loadWorkers()
  },
)

function formatTime(value) {
  return value?.slice(0, 5) ?? ''
}
</script>

<template>
  <AppShell>
    <div class="page-header">
      <div>
        <h1 class="page-title">Workers</h1>
        <p class="page-lead">
          Find who's qualified, in the right branch, and free at a given time — for staffing a shift.
        </p>
      </div>
      <RouterLink to="/workers/invite" class="add-button">+ Invite worker</RouterLink>
    </div>

    <section class="panel">
      <div class="filters">
        <input v-model="search" type="search" class="filter-input" placeholder="Search name, email, personnel/employee #…" />

        <select v-model="qualificationId" class="filter-input">
          <option value="">Any qualification</option>
          <option v-for="q in qualifications" :key="q.id" :value="q.id">{{ q.name }}</option>
        </select>

        <select v-model="branchId" class="filter-input">
          <option value="">Any branch</option>
          <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
        </select>

        <select v-model="workTimeModel" class="filter-input">
          <option value="">Any work time model</option>
          <option value="full_time">Vollzeit</option>
          <option value="part_time">Teilzeit</option>
          <option value="casual">Fallweise Beschäftigung</option>
        </select>

        <label class="night-filter">
          <input type="checkbox" v-model="nightShift" />
          Works night shifts
        </label>

        <label class="night-filter">
          <input type="checkbox" v-model="eligibleOnly" />
          Assignable right now
        </label>

        <select v-model="dayOfWeek" class="filter-input">
          <option value="">Any day</option>
          <option v-for="(label, i) in DAY_LABELS" :key="i" :value="i">{{ label }}</option>
        </select>

        <input v-model="time" type="time" class="filter-input" />
      </div>
      <p v-if="timeFilterIncomplete" class="filter-hint">
        Pick both a day and a time to filter by availability.
      </p>
    </section>

    <p v-if="errorMessage" class="error-banner" role="alert">{{ errorMessage }}</p>
    <p v-else-if="loading" class="loading-note">Loading…</p>

    <section v-else class="panel results-panel">
      <p class="result-count">{{ workers.length }} worker{{ workers.length === 1 ? '' : 's' }} found</p>

      <RouterLink
        v-for="worker in workers"
        :key="worker.user_id"
        :to="`/workers/${worker.user_id}`"
        class="worker-card"
      >
        <header class="worker-card-header">
          <div>
            <span class="worker-name">{{ worker.name }}</span>
            <span class="worker-personnel">{{ worker.employee_number || worker.personnel_number }}</span>
            <span v-if="worker.works_night_shifts" class="night-badge">🌙 Night shifts</span>
            <span v-if="worker.status !== 'active'" class="status-badge">{{ worker.status }}</span>
          </div>
          <span class="worker-branch">{{ worker.home_branch_name || 'No branch set' }}</span>
        </header>

        <p class="worker-contact">
          {{ worker.email }} · {{ worker.phone || 'no phone' }}
          <template v-if="worker.active_contract">
            · {{ worker.active_contract.work_time_model }} ({{ worker.active_contract.contract_type }})
            <span v-if="worker.active_contract.is_marginal">· marginal</span>
          </template>
          <span v-else class="no-contract"> · no active contract</span>
        </p>

        <div class="worker-quals">
          <span v-if="worker.qualifications.length === 0" class="no-quals">No qualifications on file</span>
          <span v-for="q in worker.qualifications" :key="q.id" class="qual-chip">{{ q.name }}</span>
        </div>

        <div class="worker-availability">
          <span v-if="worker.availability.length === 0" class="no-avail">No availability on file</span>
          <span v-for="(slot, i) in worker.availability" :key="i" class="avail-chip">
            {{ DAY_LABELS[slot.day_of_week].slice(0, 3) }} {{ formatTime(slot.start_time) }}–{{ formatTime(slot.end_time) }}
          </span>
        </div>
      </RouterLink>

      <p v-if="workers.length === 0" class="empty-note">No workers match these filters.</p>
    </section>
  </AppShell>
</template>

<style scoped>
.page-title {
  font-family: var(--font-display);
  font-weight: 700;
  margin: 0 0 0.35rem;
}

.page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
}

.add-button {
  flex-shrink: 0;
  font-size: 0.85rem;
  font-weight: 600;
  padding: 0.5rem 1rem;
  color: var(--color-ink);
  background: var(--color-amber);
  border-radius: 8px;
  text-decoration: none;
  white-space: nowrap;
}

.add-button:hover {
  background: var(--color-amber-dark);
}

.page-lead {
  color: var(--color-slate);
  margin: 0 0 1.5rem;
  max-width: 60ch;
}

.panel {
  background: #fff;
  border: 1px solid var(--color-line);
  border-radius: 10px;
  padding: 1.25rem 1.5rem;
  margin-bottom: 1.5rem;
}

.filters {
  display: flex;
  flex-wrap: wrap;
  gap: 0.6rem;
}

.filter-input {
  padding: 0.5rem 0.7rem;
  font-size: 0.85rem;
  border: 1px solid var(--color-line);
  border-radius: 8px;
  background: #fff;
  min-width: 150px;
}

.filter-input:focus-visible {
  border-color: var(--color-amber);
  box-shadow: 0 0 0 3px rgba(224, 151, 58, 0.25);
  outline: none;
}

.night-filter {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.85rem;
  padding: 0 0.3rem;
}

.night-badge {
  display: inline-block;
  font-size: 0.72rem;
  font-weight: 600;
  color: var(--color-ink);
  background: rgba(224, 151, 58, 0.25);
  padding: 0.1rem 0.5rem;
  border-radius: 999px;
  margin-left: 0.5rem;
}

.status-badge {
  display: inline-block;
  font-size: 0.72rem;
  font-weight: 600;
  color: var(--color-danger);
  background: rgba(181, 83, 63, 0.12);
  padding: 0.1rem 0.5rem;
  border-radius: 999px;
  margin-left: 0.5rem;
}

.filter-hint {
  margin: 0.6rem 0 0;
  font-size: 0.8rem;
  color: var(--color-amber-dark);
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

.result-count {
  font-size: 0.8rem;
  color: var(--color-slate);
  margin: 0 0 1rem;
}

.worker-card {
  display: block;
  border: 1px solid var(--color-line);
  border-radius: 8px;
  padding: 1rem 1.1rem;
  margin-bottom: 0.75rem;
  text-decoration: none;
  color: inherit;
  transition: border-color 0.15s ease;
}

.worker-card:hover {
  border-color: var(--color-amber);
}

.worker-card-header {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
}

.worker-name {
  font-weight: 700;
  margin-right: 0.5rem;
}

.worker-personnel {
  font-family: var(--font-mono);
  font-size: 0.78rem;
  color: var(--color-slate);
}

.worker-branch {
  font-size: 0.8rem;
  color: var(--color-slate);
}

.worker-contact {
  font-size: 0.82rem;
  color: var(--color-slate);
  margin: 0.3rem 0 0.75rem;
}

.no-contract {
  color: var(--color-danger);
}

.worker-quals,
.worker-availability {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
  margin-top: 0.4rem;
}

.qual-chip {
  background: rgba(224, 151, 58, 0.14);
  color: var(--color-amber-dark);
  font-size: 0.76rem;
  font-weight: 600;
  padding: 0.15rem 0.5rem;
  border-radius: 999px;
}

.avail-chip {
  background: rgba(76, 139, 108, 0.13);
  color: var(--color-green);
  font-size: 0.76rem;
  font-weight: 600;
  padding: 0.15rem 0.5rem;
  border-radius: 999px;
}

.no-quals,
.no-avail {
  font-size: 0.78rem;
  color: var(--color-slate);
  font-style: italic;
}

.empty-note {
  text-align: center;
  color: var(--color-slate);
  font-style: italic;
  padding: 1.5rem 0;
}
</style>
